<?php

namespace App\Services\Parsers\Extractor;

use App\DTO\FieldBag;
use App\DTO\NormalizedDocument;
use App\Models\InboxMessage;

/**
 * Extracts structured facts from confirmed production Mandiri notification emails.
 *
 * Supported templates (confirmed production samples only)
 * ───────────────────────────────────────────────────────
 *   MANDIRI_TRANSFER  — Regular bank transfer
 *   MANDIRI_BIFAST    — BI Fast transfer (real-time national payment)
 *   MANDIRI_QRIS      — QRIS payment
 *   MANDIRI_TOPUP     — Top-up (ShopeePay, GoPay, OVO, etc.)
 *
 * Extracted fields (all nullable)
 * ────────────────────────────────
 *   tanggal              — transaction date string
 *   jam                  — transaction time string
 *   nominal              — amount string (raw)
 *   reference            — reference number
 *   source_account       — source account number
 *   destination_account  — destination account number
 *   merchant             — merchant/payee name (QRIS / Top-up target)
 *   counterparty         — human name of sender or recipient
 *   keterangan           — remarks / berita transfer
 *   template             — detected template identifier string
 */
class MandiriDocumentExtractor extends AbstractExtractor
{
    public function supports(InboxMessage $message): bool
    {
        $from    = $message->from ?? '';
        $subject = $message->subject ?? '';

        return (bool) (
            preg_match('/bankmandiri\.co\.id/i', $from) ||
            preg_match('/@mandiri/i', $from)
        );
    }

    public function extract(InboxMessage $message): NormalizedDocument
    {
        $body = $message->plain_body;
        if (empty($body) && !empty($message->html_body)) {
            $body = $this->cleanHtml($message->html_body);
        } else {
            $body = $this->cleanText($body ?? '');
        }

        // ── Aggressive Footer Truncation ──────────────────────────────────────
        $disclaimerMarkers = [
            'Best Regards',
            'PT Bank Central Asia',
            'Please save this email',
            'Simpan email ini',
            'This email is generated automatically',
            'Jika Anda bukan',
            'Terima kasih telah bertransaksi',
        ];
        foreach ($disclaimerMarkers as $kw) {
            $pos = stripos($body, $kw);
            if ($pos !== false) {
                $body = substr($body, 0, $pos);
            }
        }

        $subject = $message->subject ?? '';
        $fields  = [];

        // ── Label stop-list ───────────────────────────────────────────────────
        $labels = 'Tanggal|Waktu|Jam|Nominal|Jumlah|Merchant|Nama\s+Penerima|Nama\s+Pengirim'
            . '|Penerima|Pengirim|Rekening\s+Tujuan|Rekening\s+Asal|No\.?\s*Referensi'
            . '|Keterangan|Berita|Jenis|No\.\s+Rekening|Kode\s+Unik|Waktu\s+Transaksi'
            . '|Tujuan|Dari|Ke|Status|Nama\s+Merchant|Target\s+Dompet|Penyedia\s+Jasa'
            . '|Rekening\s+Sumber|Simpan\s+email\s+ini'
            . '|Merchant\s+PAN|Customer\s+PAN|Pengakuisisi|Terminal\s+ID|Sumber\s+Dana'
            . '|No\.\s+Ref\.?\s+QRIS|No\.\s+Referensi';
        $smashed = 'Tanggal|Jam|Nominal|Jumlah|No\.?\s*Referensi|Merchant\s+PAN|Customer\s+PAN|Pengakuisisi|Terminal\s+ID|Sumber\s+Dana|No\.\s+Ref\.?\s+QRIS|No\.\s+Referensi|Simpan\s+email\s+ini';
        $la = '(?=\s+(?:' . $labels . ')|\b(?:' . $labels . ')|(?:' . $smashed . ')|$)';

        // ── Date & Time ───────────────────────────────────────────────────────
        // Mandiri uses "Tanggal" for date and "Waktu" for time.
        // Some templates use "Waktu Transaksi" for the combined date+time.
        $fields['tanggal'] = $this->extractRegex($body, "/(?:Tanggal|Waktu\s+Transaksi)\s*:?\s*(.*?){$la}/i");
        $fields['jam']     = $this->extractRegex($body, "/Waktu\s*:?\s*(\d{2}:\d{2}(?::\d{2})?)\b/i");

        // ── Amount ────────────────────────────────────────────────────────────
        $nominal = $this->extractRegex($body, "/(?:Nominal|Jumlah)\s*:?\s*(.*?){$la}/i");
        if (!$nominal) {
            $nominal = $this->extractRegex($body, '/Rp\.?\s*([\d.,]+)/i');
        }
        $fields['nominal'] = $nominal;

        // ── Reference ─────────────────────────────────────────────────────────
        $fields['reference'] = $this->extractRegex($body, "/(?:No\.?\s*Referensi|Referensi|No\.\s+Transaksi)\s*:?\s*(.*?){$la}/i");

        // ── Accounts ──────────────────────────────────────────────────────────
        $fields['source_account']      = $this->extractRegex($body, "/Rekening\s+Asal\s*:?\s*(.*?){$la}/i");
        $fields['destination_account'] = $this->extractRegex($body, "/Rekening\s+Tujuan\s*:?\s*(.*?){$la}/i");

        // ── Merchant / Payee (QRIS / Top-up target) ───────────────────────────
        $fields['merchant'] = $this->extractRegex($body, "/(?:Merchant(?!\s+PAN)|Nama\s+Merchant|Target\s+Dompet|Penyedia\s+Jasa)\s*:?\s*(.*?){$la}/i");

        // ── Counterparty (human name) ─────────────────────────────────────────
        $counterparty = $this->extractRegex($body, "/Nama\s+Penerima\s*:?\s*(.*?){$la}/i");
        if (!$counterparty) {
            $counterparty = $this->extractRegex($body, "/Nama\s+Pengirim\s*:?\s*(.*?){$la}/i");
        }
        if (!$counterparty) {
            $counterparty = $this->extractRegex($body, "/(?:Penerima|Pengirim)\s*:?\s*(.*?){$la}/i");
        }
        $fields['counterparty'] = $counterparty;

        // ── Remarks ───────────────────────────────────────────────────────────
        $fields['keterangan'] = $this->extractRegex($body, "/(?:Keterangan|Berita)\s*:?\s*(.*?){$la}/i");

        // ── Template detection ────────────────────────────────────────────────
        $fields['template'] = $this->detectTemplate($subject, $body);

        // ── Clean extracted fields ────────────────────────────────────────────
        foreach ($fields as $key => $val) {
            $fields[$key] = $this->cleanAndNormalizeField($key, $val);
        }

        return new NormalizedDocument(
            subject:      $subject,
            from:         $message->from ?? '',
            to:           $message->to ?? '',
            internalDate: $message->internal_date ?? 0,
            body:         $body,
            fields:       new FieldBag($fields)
        );
    }

    /**
     * Helper to clean, trim, and normalize extracted facts at the parser layer.
     */
    private function cleanAndNormalizeField(string $key, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $val = trim($value);

        // Clean merchant and counterparty fields
        if ($key === 'merchant' || $key === 'counterparty') {
            // Strip trailing bank info, e.g. "Bank Mandiri - 1420014109648"
            $val = preg_replace('/\s+Bank\s+[A-Za-z\s]+(?:\s*[-:]\s*[\d\*x]+)?/i', '', $val);
            // Strip trailing account numbers/masks, e.g. "****2919" or "xxxx5882" or " - 123456"
            $val = preg_replace('/\s*[-:]?\s*[\d\*x]{4,}/i', '', $val);
            // Strip trailing "Save to Beneficiary List..."
            $val = preg_replace('/\s+Save\s+to\s+Beneficiary.*/i', '', $val);
        }

        // Clean empty/placeholder remarks
        if ($key === 'keterangan') {
            if (preg_match('/^[-:\s]*$/', $val)) {
                return null;
            }
        }

        $val = trim($val);
        return $val === '' ? null : $val;
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Detect which confirmed Mandiri template this email matches.
     */
    private function detectTemplate(string $subject, string $body): ?string
    {
        $sl = strtolower($subject);
        $bl = strtolower($body);

        // MANDIRI_QRIS — QRIS payment
        if (str_contains($sl, 'qris') || str_contains($bl, 'qris')) {
            return 'MANDIRI_QRIS';
        }

        // MANDIRI_BIFAST — BI Fast (real-time national transfer)
        if (
            str_contains($sl, 'bi fast') || str_contains($sl, 'bifast') ||
            str_contains($bl, 'bi fast') || str_contains($bl, 'bifast')
        ) {
            return 'MANDIRI_BIFAST';
        }

        // MANDIRI_TOPUP — Top-up / e-wallet reload
        if (
            str_contains($sl, 'top up') || str_contains($sl, 'topup') ||
            str_contains($bl, 'top up') || str_contains($bl, 'topup') ||
            str_contains($bl, 'shopeepay') || str_contains($bl, 'gopay') ||
            str_contains($bl, 'ovo')
        ) {
            return 'MANDIRI_TOPUP';
        }

        // MANDIRI_TRANSFER — Generic bank transfer
        if (
            str_contains($sl, 'transfer') ||
            str_contains($bl, 'rekening tujuan') ||
            str_contains($bl, 'rekening asal')
        ) {
            return 'MANDIRI_TRANSFER';
        }

        return null;
    }
}
