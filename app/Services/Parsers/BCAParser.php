<?php

namespace App\Services\Parsers;

use App\Contracts\ParserInterface;
use App\DTO\NormalizedDocument;
use App\DTO\ParsedMessage;
use App\DTO\ParsedTransaction;
use App\DTO\ParserContext;
use App\DTO\ParserResult;
use App\Enums\ConfidenceLevel;
use Carbon\Carbon;

/**
 * Concrete parser for Bank Central Asia (BCA) notifications.
 */
class BCAParser extends AbstractParser implements ParserInterface
{
    /**
     * Map of Indonesian month names and abbreviations to English equivalents.
     */
    private const MONTH_MAP = [
        'januari'   => 'January',
        'februari'  => 'February',
        'maret'     => 'March',
        'april'     => 'April',
        'mei'       => 'May',
        'juni'      => 'June',
        'juli'      => 'July',
        'agustus'   => 'August',
        'september' => 'September',
        'oktober'   => 'October',
        'november'  => 'November',
        'desember'  => 'December',
        'jan'       => 'Jan',
        'feb'       => 'Feb',
        'mar'       => 'Mar',
        'apr'       => 'Apr',
        'jun'       => 'Jun',
        'jul'       => 'Jul',
        'agu'       => 'Aug',
        'sep'       => 'Sep',
        'okt'       => 'Oct',
        'nov'       => 'Nov',
        'des'       => 'Dec',
    ];

    public function supports(NormalizedDocument $document, ParserContext $context): bool
    {
        // Check sender address
        $fromMatch = (bool) preg_match('/(bca|cba)@bca\.co\.id/i', $document->from);

        // Check subject keywords
        $subjectMatch = (bool) preg_match('/(Transfer|QRIS|Debit|Credit)/i', $document->subject);

        return $fromMatch || $subjectMatch;
    }

    public function parse(NormalizedDocument $document, ParserContext $context): ParserResult
    {
        $fields = $document->fields;

        // 1. Parse Date and Time
        $rawDate = $fields->get('tanggal');
        $rawTime = $fields->get('jam');
        $parsedDate = $this->interpretDate($rawDate, $rawTime);

        // 2. Parse Amount (Integer Rupiah, Revision 3)
        $rawNominal = $fields->get('nominal');
        $amount = 0;
        if ($rawNominal) {
            $floatAmount = $this->cleanCurrency($rawNominal);
            $amount = (int) round($floatAmount);
        }

        // 3. Parse Reference
        $reference = $fields->get('reference');

        // 4. Parse Accounts
        $sourceAccount = $fields->get('source_account');
        $destinationAccount = $fields->get('destination_account');

        // 5. Determine Flow
        $flow = $this->interpretFlow($document->subject, $document->body);

        // 6. Determine Type
        $type = $this->interpretType($document->subject, $document->body);

        // 7. Parse Merchant / Payee
        $merchant = $fields->get('merchant');
        if (empty($merchant)) {
            // Fallback: use type-specific descriptions
            if ($type === 'QRIS') {
                $merchant = 'QRIS Merchant';
            } elseif ($flow === 'IN') {
                $merchant = $sourceAccount ? "Transfer from {$sourceAccount}" : 'BCA Sender';
            } else {
                $merchant = $destinationAccount ? "Transfer to {$destinationAccount}" : 'BCA Recipient';
            }
        }

        $bank = 'BCA';

        // 8. Confidence calculation (Revision 4)
        // Fields check list: merchant, amount, reference, date, flow, bank
        $validCount = 0;
        if (!empty($merchant) && !str_contains(strtolower($merchant), 'unknown')) $validCount++;
        if ($amount > 0) $validCount++;
        if (!empty($reference)) $validCount++;
        if ($parsedDate !== null) $validCount++;
        if (!empty($flow)) $validCount++;
        if (!empty($bank)) $validCount++; // Always present

        if ($validCount === 6) {
            $confidence = ConfidenceLevel::HIGH;
        } elseif ($validCount === 5) {
            $confidence = ConfidenceLevel::MEDIUM;
        } else {
            $confidence = ConfidenceLevel::LOW;
        }

        $notes = $fields->get('keterangan');

        // 9. Build DTOs
        $parsedMessage = new ParsedMessage(
            provider: 'BCA',
            intent: 'TRANSACTION',
            confidence: $confidence,
            rawFields: $fields->all()
        );

        $parsedTransaction = null;
        if ($amount > 0 && $parsedDate !== null) {
            $parsedTransaction = new ParsedTransaction(
                merchant: $merchant,
                amount: $amount,
                transactionDate: $parsedDate,
                reference: $reference,
                bank: $bank,
                flow: $flow,
                type: $type,
                sourceAccount: $sourceAccount,
                destinationAccount: $destinationAccount,
                rawAmount: $rawNominal ?? '',
                status: 'SUCCESS',
                confidence: $confidence,
                notes: $notes
            );
        }

        return new ParserResult(
            supported: true,
            warnings: [],
            errors: [],
            confidence: $confidence,
            parsedMessage: $parsedMessage,
            parsedTransaction: $parsedTransaction
        );
    }

    /**
     * Interpret raw date and time strings into a Carbon instance.
     */
    private function interpretDate(?string $rawDate, ?string $rawTime): ?Carbon
    {
        if (empty($rawDate)) {
            return null;
        }

        // Standardize separators and lower case
        $cleanDate = strtolower(trim($rawDate));

        // Replace Indonesian months with English months
        foreach (self::MONTH_MAP as $id => $en) {
            $cleanDate = str_replace($id, $en, $cleanDate);
        }

        // Clean time format
        $timeStr = $rawTime ? trim($rawTime) : '00:00:00';
        if (preg_match('/^\d{2}:\d{2}$/', $timeStr)) {
            $timeStr .= ':00';
        }

        $formats = [
            'd/m/Y',
            'd M Y',
            'd-M-Y',
            'j M Y',
            'd F Y',
            'j F Y',
        ];

        foreach ($formats as $fmt) {
            // Try parsing with time first
            $dtStr = $cleanDate . ' ' . $timeStr;
            $parsed = $this->parseDate($dtStr, $fmt . ' H:i:s');
            if ($parsed) {
                return $parsed;
            }

            // Try parsing date only
            $parsed = $this->parseDate($cleanDate, $fmt);
            if ($parsed) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * Determine transaction flow direction.
     */
    private function interpretFlow(string $subject, string $body): string
    {
        $subjectLower = strtolower($subject);
        $bodyLower = strtolower($body);

        if (
            str_contains($subjectLower, 'masuk') ||
            str_contains($subjectLower, 'diterima') ||
            str_contains($subjectLower, 'credit') ||
            str_contains($bodyLower, 'transfer masuk') ||
            str_contains($bodyLower, 'telah diterima') ||
            str_contains($bodyLower, 'dana masuk')
        ) {
            return 'IN';
        }

        return 'OUT';
    }

    /**
     * Determine transaction type (QRIS, CREDIT_CARD, DEBIT_CARD, TRANSFER).
     */
    private function interpretType(string $subject, string $body): string
    {
        $subjectLower = strtolower($subject);
        $bodyLower = strtolower($body);

        if (str_contains($subjectLower, 'qris') || str_contains($bodyLower, 'qris')) {
            return 'QRIS';
        }

        if (str_contains($subjectLower, 'credit') || str_contains($bodyLower, 'kartu kredit')) {
            return 'CREDIT_CARD';
        }

        if (str_contains($subjectLower, 'debit') || str_contains($bodyLower, 'kartu debit') || str_contains($bodyLower, 'debet')) {
            return 'DEBIT_CARD';
        }

        return 'TRANSFER';
    }
}
