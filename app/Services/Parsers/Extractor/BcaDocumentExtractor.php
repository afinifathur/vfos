<?php

namespace App\Services\Parsers\Extractor;

use App\DTO\FieldBag;
use App\DTO\NormalizedDocument;
use App\Models\InboxMessage;

/**
 * Extracts raw data fields from BCA notification emails.
 */
class BcaDocumentExtractor extends AbstractExtractor
{
    public function supports(InboxMessage $message): bool
    {
        $from = $message->from ?? '';
        $subject = $message->subject ?? '';

        // Match from headers containing bca@bca.co.id or cba@bca.co.id
        // Or subject containing Transfer, QRIS, Debit, Credit
        return (bool) (
            preg_match('/(bca|cba)@bca\.co\.id/i', $from) ||
            preg_match('/(Transfer|QRIS|Debit|Credit)/i', $subject)
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

        $fields = [];

        // Comprehensive list of possible labels to terminate matching and prevent bleeding (Indonesian and English)
        $labels = 'Tanggal|Jam|Nominal|Sebesar|Jumlah\s+Transfer|Merchant|Nama\s+Penerima|Nama\s+Pengirim|Penerima|Pengirim|Ke\s+Rekening|Dari\s+Rekening|Rekening\s+Debet|Rekening\s+Tujuan|Rekening\s+Sumber|No\.\s*Referensi|Referensi|Ref\s*No|Keterangan|Berita|Jenis|Nomor\s+Kartu'
            . '|Transaction\s+Date|Transfer\s+Type|Amount|Reference\s+No\.?|Beneficiary\s+Account|Beneficiary\s+Name|Source\s+of\s+Fund|Source\s+Currency|Transfer\s+Currency|Transfer\s+Amount|Remarks|Transaction\s+Type|Payment\s+to|Merchant\s+Location|Acquirer|Status'
            . '|Ke\s*:|Dari\s*:';
        $la = '(?=\s+(?:' . $labels . ')|$)';

        // Extract fields using non-greedy matches ending at next label/end of string (with optional colon :?)
        $fields['tanggal'] = $this->extractRegex($body, "/(?:Tanggal|Transaction\s+Date)\s*:?\s*(.*?){$la}/i");
        $fields['jam'] = $this->extractRegex($body, "/(?:Jam)\s*:?\s*(.*?){$la}/i");

        // Split date and time if they are extracted together in 'tanggal' (common in myBCA English format)
        if (preg_match('/^(\d{1,2}\s+[a-zA-Z]+\s+\d{4})\s+(\d{2}:\d{2}:\d{2})$/', trim($fields['tanggal'] ?? ''), $matches)) {
            $fields['tanggal'] = $matches[1];
            $fields['jam'] = $matches[2];
        }

        // Nominal
        $nominal = $this->extractRegex($body, "/(?:Nominal|Sebesar|Amount|Transfer\s+Amount|Jumlah\s+Transfer)\s*:?\s*(.*?){$la}/i");
        if (!$nominal) {
            // Fallback to find any Rp amount
            $nominal = $this->extractRegex($body, '/Rp\.?\s*([\d.,]+)/i');
        }
        if (!$nominal) {
            // Fallback to find any IDR amount
            $nominal = $this->extractRegex($body, '/IDR\s*([\d.,]+)/i');
        }
        $fields['nominal'] = $nominal;

        // Merchant / Payee
        $fields['merchant'] = $this->extractRegex($body, "/(?:Merchant|Nama\s+Penerima|Penerima|Nama\s+Pengirim|Pengirim|Beneficiary\s+Name|Payment\s+to)\s*:?\s*(.*?){$la}|\b(?:Ke)\b\s*:\s*(.*?){$la}/i");

        // Reference
        $fields['reference'] = $this->extractRegex($body, "/(?:No\.\s*Referensi|Referensi|Ref\s*No|Reference\s+No\.?)\s*:?\s*(.*?){$la}/i");

        // Source Account
        $fields['source_account'] = $this->extractRegex($body, "/(?:Dari\s+Rekening|Rekening\s+Debet|Source\s+of\s+Fund|Rekening\s+Sumber)\s*:?\s*(.*?){$la}|\b(?:Dari)\b\s*:\s*(.*?){$la}/i");

        // Destination Account
        $fields['destination_account'] = $this->extractRegex($body, "/(?:Ke\s+Rekening|Rekening\s+Tujuan|Beneficiary\s+Account|BCA\s+Virtual\s+Account\s+No)\s*:?\s*(.*?){$la}|\b(?:Ke)\b\s*:\s*(.*?){$la}/i");

        // Keterangan
        $fields['keterangan'] = $this->extractRegex($body, "/(?:Keterangan|Berita|Remarks)\s*:?\s*(.*?){$la}/i");

        // Clean up extracted fields (trimming whitespaces)
        foreach ($fields as $key => $val) {
            if ($val !== null) {
                $fields[$key] = trim($val);
            }
        }

        return new NormalizedDocument(
            subject: $message->subject ?? '',
            from: $message->from ?? '',
            to: $message->to ?? '',
            internalDate: $message->internal_date ?? 0,
            body: $body,
            fields: new FieldBag($fields)
        );
    }
}
