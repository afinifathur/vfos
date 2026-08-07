<?php

namespace App\Services\Parsers\Extractor;

use App\DTO\FieldBag;
use App\DTO\NormalizedDocument;
use App\Models\InboxMessage;

/**
 * Extracts structured facts from confirmed production BCA notification emails.
 *
 * Supported templates (confirmed production samples only)
 * ───────────────────────────────────────────────────────
 *   BCA_QRIS          — QRIS payment via M-BCA / myBCA
 *   BCA_TRANSFER      — Transfer to/from BCA account (M-BCA format)
 *   BCA_VIRTUAL_ACCOUNT — Transfer to BCA Virtual Account
 *   BCA_CASH_DEPOSIT  — Cardless cash deposit (Setor Tunai)
 *   BCA_CASH_WITHDRAW — Cardless cash withdrawal (Tarik Tunai)
 *
 * Extracted fields (all nullable)
 * ────────────────────────────────
 *   tanggal              — transaction date string
 *   jam                  — transaction time string
 *   nominal              — amount string (raw, e.g. "Rp. 50.000,00")
 *   reference            — reference/transaction number
 *   source_account       — source account number
 *   destination_account  — destination account number
 *   merchant             — merchant name (QRIS / debit card)
 *   counterparty         — human name of sender or recipient
 *   company              — VA company/product name (Virtual Account)
 *   keterangan           — remarks / transaction note
 *   template             — detected template identifier string
 */
class BcaDocumentExtractor extends AbstractExtractor
{
    public function supports(InboxMessage $message): bool
    {
        $from    = $message->from ?? '';
        $subject = $message->subject ?? '';

        // Primary: BCA sender address is the strongest signal
        if (preg_match('/(bca|cba)@bca\.co\.id/i', $from)) {
            return true;
        }

        // Reject known non-BCA senders early — do not let subject keywords override
        if (preg_match('/bankmandiri\.co\.id|@mandiri/i', $from)) {
            return false;
        }

        // Fallback: subject contains BCA-specific keywords AND no other bank sender is detected
        return (bool) preg_match('/(Transfer|QRIS|Debit|Credit|Setor|Tarik)/i', $subject);
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

        // ── Label stop-list (prevents field values from bleeding into the next field) ──
        $labels = 'Tanggal|Jam|Nominal|Sebesar|Jumlah\s+Transfer|Merchant|Nama\s+Penerima|Nama\s+Pengirim'
            . '|Penerima|Pengirim|Ke\s+Rekening|Dari\s+Rekening|Rekening\s+Debet|Rekening\s+Tujuan'
            . '|Rekening\s+Sumber|No\.?\s*Referensi|Referensi|Ref\s*No|Keterangan|Berita|Jenis'
            . '|Nomor\s+Kartu|Transaction\s+Date|Transfer\s+Type|Amount|Reference\s+No\.?'
            . '|Beneficiary\s+Account|Beneficiary\s+Name|Source\s+of\s+Fund|Source\s+Currency'
            . '|Transfer\s+Currency|Transfer\s+Amount|Remarks|Transaction\s+Type|Payment\s+to'
            . '|Merchant\s+Location|Acquirer|Status|Ke\s*:|Dari\s*:|Nama\s+VA|Perusahaan'
            . '|Company\/Product\s+Name|Name|billdesc|Bill\s+Total|Total\s+Payment|Description'
            . '|Save\s+to\s+Beneficiary\s+List|Save\s+to';
        $la = '(?=\s+(?:' . $labels . ')|$)';

        // ── Date & Time ───────────────────────────────────────────────────────
        $fields['tanggal'] = $this->extractRegex($body, "/(?:Tanggal|Transaction\s+Date)\s*:?\s*(.*?){$la}/i");
        $fields['jam']     = $this->extractRegex($body, "/(?:Jam)\s*:?\s*(.*?){$la}/i");

        // Split combined date+time (myBCA English format: "02 August 2026 14:30:00")
        if (preg_match('/^(\d{1,2}\s+[a-zA-Z]+\s+\d{4})\s+(\d{2}:\d{2}:\d{2})$/', trim($fields['tanggal'] ?? ''), $m)) {
            $fields['tanggal'] = $m[1];
            $fields['jam']     = $m[2];
        }

        // ── Amount ────────────────────────────────────────────────────────────
        $nominal = $this->extractRegex($body, "/(?:Nominal|Sebesar|Amount|Transfer\s+Amount|Jumlah\s+Transfer)\s*:?\s*(.*?){$la}/i");
        if (!$nominal) {
            $nominal = $this->extractRegex($body, '/Rp\.?\s*([\d.,]+)/i');
        }
        if (!$nominal) {
            $nominal = $this->extractRegex($body, '/IDR\s*([\d.,]+)/i');
        }
        $fields['nominal'] = $nominal;

        // ── Reference ─────────────────────────────────────────────────────────
        $fields['reference'] = $this->extractRegex($body, "/(?:No\.?\s*Referensi|Referensi|Ref\s*No|Reference\s+No\.?)\s*:?\s*(.*?){$la}/i");

        // ── Accounts ──────────────────────────────────────────────────────────
        $fields['source_account']      = $this->extractRegex($body, "/(?:Dari\s+Rekening|Rekening\s+Debet|Source\s+of\s+Fund|Rekening\s+Sumber)\s*:?\s*(.*?){$la}|\b(?:Dari)\b\s*:\s*(.*?){$la}/i");
        $fields['destination_account'] = $this->extractRegex($body, "/(?:Ke\s+Rekening|Rekening\s+Tujuan|Beneficiary\s+Account|BCA\s+Virtual\s+Account\s+No)\s*:?\s*(.*?){$la}|\b(?:Ke)\b\s*:\s*(.*?){$la}/i");

        // ── Merchant (QRIS / debit card merchant name) ────────────────────────
        $fields['merchant'] = $this->extractRegex($body, "/(?:Merchant|Payment\s+to)\s*:?\s*(.*?){$la}/i");

        // ── Counterparty (human name of sender or recipient) ──────────────────
        // BCA uses "Nama Penerima" for outgoing and "Nama Pengirim" for incoming.
        // We prefer the specific fields; fall back to the generic Penerima/Pengirim.
        $counterparty = $this->extractRegex($body, "/Nama\s+Penerima\s*:?\s*(.*?){$la}/i");
        if (!$counterparty) {
            $counterparty = $this->extractRegex($body, "/Nama\s+Pengirim\s*:?\s*(.*?){$la}/i");
        }
        if (!$counterparty) {
            $counterparty = $this->extractRegex($body, "/(?:Penerima|Pengirim|Beneficiary\s+Name)\s*:?\s*(.*?){$la}/i");
        }
        if (!$counterparty) {
            $counterparty = $this->extractRegex($body, "/\bName\b\s*:?\s*(.*?){$la}/i");
        }
        $fields['counterparty'] = $counterparty;

        // ── Virtual Account company / product name ────────────────────────────
        // BCA VA emails expose the merchant/company name in a dedicated field.
        $fields['company'] = $this->extractRegex($body, "/(?:Nama\s+VA|Perusahaan|Company\/Product\s+Name)\s*:?\s*(.*?){$la}/i");

        // ── Remarks / Keterangan ──────────────────────────────────────────────
        $fields['keterangan'] = $this->extractRegex($body, "/(?:Keterangan|Berita|Remarks)\s*:?\s*(.*?){$la}/i");

        // ── Template detection ────────────────────────────────────────────────
        $fields['template'] = $this->detectTemplate($subject, $body);

        // ── Clean extracted fields ────────────────────────────────────────────
        foreach ($fields as $key => $val) {
            $fields[$key] = $this->cleanAndNormalizeField($key, $val);
        }

        return new NormalizedDocument(
            subject: $subject,
            from:    $message->from ?? '',
            to:      $message->to ?? '',
            internalDate: $message->internal_date ?? 0,
            body:    $body,
            fields:  new FieldBag($fields)
        );
    }

    private function cleanAndNormalizeField(string $key, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $val = trim($value);

        // Normalize PT FINNET INDONESIA / BY.U -> by.U
        if ($key === 'company' || $key === 'merchant') {
            if (str_contains(strtolower($val), 'finnet') && str_contains(strtolower($val), 'by.u')) {
                return 'by.U';
            }
        }

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
     * Detect which confirmed BCA template this email matches.
     *
     * Returns one of the BCA_* template constants or null if unrecognized.
     */
    private function detectTemplate(string $subject, string $body): ?string
    {
        $sl = strtolower($subject);
        $bl = strtolower($body);

        // BCA_CASH_DEPOSIT — Cardless cash deposit (Setor Tunai)
        if (
            str_contains($sl, 'setor tunai') ||
            str_contains($bl, 'setor tunai') ||
            str_contains($bl, 'cardless deposit') ||
            str_contains($sl, 'deposit cash') ||
            str_contains($sl, 'cash deposit') ||
            str_contains($sl, 'deposit successful')
        ) {
            return 'BCA_CASH_DEPOSIT';
        }

        // BCA_CASH_WITHDRAW — Cardless cash withdrawal (Tarik Tunai)
        if (
            str_contains($sl, 'tarik tunai') ||
            str_contains($bl, 'tarik tunai') ||
            str_contains($bl, 'cardless withdraw') ||
            str_contains($sl, 'withdraw cash') ||
            str_contains($sl, 'cash withdrawal') ||
            str_contains($sl, 'withdrawal successful')
        ) {
            return 'BCA_CASH_WITHDRAW';
        }

        // BCA_QRIS — QRIS payment
        if (str_contains($sl, 'qris') || str_contains($bl, 'qris')) {
            return 'BCA_QRIS';
        }

        // BCA_VIRTUAL_ACCOUNT — Transfer to BCA Virtual Account
        if (
            str_contains($sl, 'virtual account') ||
            str_contains($bl, 'virtual account') ||
            str_contains($bl, 'bca va')
        ) {
            return 'BCA_VIRTUAL_ACCOUNT';
        }

        // BCA_TRANSFER — Generic transfer (in or out)
        if (
            str_contains($sl, 'transfer') ||
            str_contains($bl, 'transfer') ||
            str_contains($bl, 'dari rekening') ||
            str_contains($bl, 'ke rekening') ||
            str_contains($bl, 'm-bca')
        ) {
            return 'BCA_TRANSFER';
        }

        return null;
    }
}
