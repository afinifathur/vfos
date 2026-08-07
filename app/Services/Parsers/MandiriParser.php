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
 * Concrete parser for Bank Mandiri notifications.
 *
 * Philosophy — same as BCAParser:
 *   Extract structured facts only. No human-readable label building.
 *   TransactionDescriptionBuilder generates all user-facing text.
 *
 * Supported templates (confirmed production samples only)
 * ───────────────────────────────────────────────────────
 *   MANDIRI_TRANSFER  — Regular bank transfer
 *   MANDIRI_BIFAST    — BI Fast real-time transfer
 *   MANDIRI_QRIS      — QRIS payment
 *   MANDIRI_TOPUP     — Top-up (ShopeePay, GoPay, OVO, etc.)
 */
class MandiriParser extends AbstractParser implements ParserInterface
{
    /**
     * Map of Indonesian month names to English equivalents.
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
        return (bool) preg_match('/bankmandiri\.co\.id|@mandiri/i', $document->from);
    }

    public function parse(NormalizedDocument $document, ParserContext $context): ParserResult
    {
        $fields = $document->fields;

        // 1. Date & Time
        $parsedDate = $this->interpretDate(
            $fields->get('tanggal'),
            $fields->get('jam')
        );

        // 2. Amount
        $rawNominal = $fields->get('nominal');
        $amount = 0;
        if ($rawNominal) {
            $amount = (int) round($this->cleanCurrency($rawNominal));
        }

        // 3. Structured facts
        $reference          = $fields->get('reference');
        $sourceAccount      = $fields->get('source_account');
        $destinationAccount = $fields->get('destination_account');
        $merchant           = $fields->get('merchant');
        $counterparty       = $fields->get('counterparty');
        $keterangan         = $fields->get('keterangan');
        $template           = $fields->get('template');

        // 4. Flow & Type
        $flow = $this->interpretFlow($document->subject, $document->body, $template);
        $type = $this->interpretType($template);

        // 5. Merchant resolution
        $resolvedMerchant = $this->resolveMerchant(
            template:           $template,
            merchant:           $merchant,
            counterparty:       $counterparty,
            flow:               $flow,
            sourceAccount:      $sourceAccount,
            destinationAccount: $destinationAccount,
        );

        $bank = 'MANDIRI';

        // 6. Confidence scoring
        $validCount = 0;
        if (!empty($resolvedMerchant) && !str_contains(strtolower($resolvedMerchant), 'unknown')) $validCount++;
        if ($amount > 0)          $validCount++;
        if (!empty($reference))   $validCount++;
        if ($parsedDate !== null) $validCount++;
        if (!empty($flow))        $validCount++;
        if (!empty($bank))        $validCount++;

        $confidence = match (true) {
            $validCount === 6 => ConfidenceLevel::HIGH,
            $validCount === 5 => ConfidenceLevel::MEDIUM,
            default           => ConfidenceLevel::LOW,
        };

        // 7. Build DTOs
        $parsedMessage = new ParsedMessage(
            provider:   'MANDIRI',
            intent:     'TRANSACTION',
            confidence: $confidence,
            rawFields:  $fields->all()
        );

        $parsedTransaction = null;
        if ($amount > 0 && $parsedDate !== null) {
            $parsedTransaction = new ParsedTransaction(
                merchant:           $resolvedMerchant,
                amount:             $amount,
                transactionDate:    $parsedDate,
                reference:          $reference,
                bank:               $bank,
                flow:               $flow,
                type:               $type,
                sourceAccount:      $sourceAccount,
                destinationAccount: $destinationAccount,
                rawAmount:          $rawNominal ?? '',
                status:             'SUCCESS',
                confidence:         $confidence,
                notes:              $keterangan,
                template:           $template,
                counterparty:       $counterparty,
                metadata:           [
                    'keterangan' => $keterangan,
                ],
            );
        }

        return new ParserResult(
            supported:         true,
            warnings:          [],
            errors:            [],
            confidence:        $confidence,
            parsedMessage:     $parsedMessage,
            parsedTransaction: $parsedTransaction
        );
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function resolveMerchant(
        ?string $template,
        ?string $merchant,
        ?string $counterparty,
        string  $flow,
        ?string $sourceAccount,
        ?string $destinationAccount
    ): ?string {
        return match ($template) {
            'MANDIRI_QRIS'   => $merchant ?: $counterparty ?: 'Unknown Merchant',
            'MANDIRI_TOPUP'  => $merchant ?: 'Unknown Merchant',
            'MANDIRI_TRANSFER',
            'MANDIRI_BIFAST',
            null             => $counterparty
                             ?: ($flow === 'OUT' ? $destinationAccount : $sourceAccount)
                             ?: 'Unknown Merchant',
            default          => $counterparty ?: $merchant ?: 'Unknown Merchant',
        };
    }

    private function interpretDate(?string $rawDate, ?string $rawTime): ?Carbon
    {
        if (empty($rawDate)) {
            return null;
        }

        $cleanDate = strtolower(trim($rawDate));

        foreach (self::MONTH_MAP as $id => $en) {
            $cleanDate = str_replace($id, $en, $cleanDate);
        }

        $timeStr = $rawTime ? trim($rawTime) : '00:00:00';
        if (preg_match('/^\d{2}:\d{2}$/', $timeStr)) {
            $timeStr .= ':00';
        }

        $formats = ['d/m/Y', 'd M Y', 'd-M-Y', 'j M Y', 'd F Y', 'j F Y', 'Y-m-d'];

        foreach ($formats as $fmt) {
            $parsed = $this->parseDate($cleanDate . ' ' . $timeStr, $fmt . ' H:i:s');
            if ($parsed) return $parsed;

            $parsed = $this->parseDate($cleanDate, $fmt);
            if ($parsed) return $parsed;
        }

        return null;
    }

    private function interpretFlow(string $subject, string $body, ?string $template): string
    {
        $sl = strtolower($subject);
        $bl = strtolower($body);

        if (
            str_contains($sl, 'masuk') ||
            str_contains($sl, 'diterima') ||
            str_contains($sl, 'credit') ||
            str_contains($bl, 'transfer masuk') ||
            str_contains($bl, 'telah diterima') ||
            str_contains($bl, 'dana masuk') ||
            str_contains($bl, 'berhasil menerima')
        ) {
            return 'IN';
        }

        return 'OUT';
    }

    private function interpretType(?string $template): string
    {
        return match ($template) {
            'MANDIRI_QRIS'   => 'QRIS',
            'MANDIRI_BIFAST' => 'TRANSFER',
            'MANDIRI_TOPUP'  => 'TOPUP',
            default          => 'TRANSFER',
        };
    }
}
