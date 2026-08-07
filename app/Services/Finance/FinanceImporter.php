<?php

namespace App\Services\Finance;

use App\Models\InboxMessage;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\MerchantAlias;
use App\DTO\ParserResult;
use App\Services\Parsers\ParserManager;
use App\Services\Finance\Results\ImportResult;
use App\Services\Finance\Results\DuplicateResult;
use App\Services\Finance\TransactionDescriptionBuilder;
use App\Services\Finance\MerchantNormalizer;
use App\Services\Finance\Identity\AccountIdentityResolverInterface;
use App\Services\Finance\Identity\AccountProviderResolver;
use App\Services\Finance\Identity\IdentityResolutionStatistics;
use App\Services\Finance\Identity\ImportResolutionTrace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceImporter
{
    public function __construct(
        private readonly ParserManager $parserManager,
        private readonly TransactionDescriptionBuilder $descriptionBuilder,
        private readonly \App\Services\Finance\Kernel\FinancialKernelService $kernelService,
        private readonly AccountIdentityResolverInterface $identityResolver,
    ) {}

    /**
     * Import a parsed message into the financial ledger.
     */
    public function import(InboxMessage $message, ?ParserResult $result = null): ImportResult
    {
        $importStartTime = microtime(true);
        $importResult = $this->executeImport($message, $result);
        
        if (class_exists(\App\Services\Finance\Diagnostics\ImportDiagnostics::class)) {
            $elapsedMs = (microtime(true) - $importStartTime) * 1000.0;
            \App\Services\Finance\Diagnostics\ImportDiagnostics::recordImport($importResult->status, $elapsedMs);
        }
        
        return $importResult;
    }

    /**
     * Execute the raw import flow.
     */
    private function executeImport(InboxMessage $message, ?ParserResult $result = null): ImportResult
    {
        // 1. Run parser if result is not provided
        if (!$result) {
            try {
                $context = new \App\DTO\ParserContext($message->connector);
                $result = $this->parserManager->parse($message, $context);
            } catch (\App\Exceptions\UnsupportedMessageException $e) {
                $message->update(['status' => InboxMessage::STATUS_FAILED]);
                return new ImportResult('FAILED', null, 'Message not supported by any registered parser: ' . $e->getMessage());
            } catch (\Exception $e) {
                $message->update(['status' => InboxMessage::STATUS_FAILED]);
                return new ImportResult('FAILED', null, 'Parser execution failed: ' . $e->getMessage());
            }
        }

        // 2. Validate
        if (!$result->supported || !$result->parsedTransaction) {
            $message->update(['status' => InboxMessage::STATUS_FAILED]);
            return new ImportResult('FAILED', null, 'Message format not supported or contains no transaction.');
        }

        $parsedTx = $result->parsedTransaction;
        $userId = $message->user_id;

        // 3. Duplicate Detection
        $externalId = $message->google_message_id;
        if ($externalId) {
            $existing = Transaction::where('external_id', $externalId)->first();
            if ($existing) {
                $raw = $message->raw_payload ?? [];
                $raw['is_duplicate'] = true;

                $message->update([
                    'status'         => InboxMessage::STATUS_IMPORTED,
                    'transaction_id' => $existing->id,
                    'imported_at'    => now(),
                    'raw_payload'    => $raw,
                ]);
                return ImportResult::duplicate($existing->id);
            }
        }

        // 4. Account Mapping
        $accountNo = ($parsedTx->flow === 'OUT') ? $parsedTx->sourceAccount : $parsedTx->destinationAccount;
        $account = $this->resolveAccount($accountNo, $userId, $parsedTx->bank, $externalId);

        if (!$account) {
            $message->update(['status' => InboxMessage::STATUS_FAILED]);
            return new ImportResult('FAILED', null, 'Mapped financial account not found.');
        }

        // 5. Transfer between own accounts
        $toAccount = null;
        $txType = ($parsedTx->flow === 'OUT') ? 'expense' : 'income';
        $internalTransferCandidate = $this->isInternalTransferCandidate($parsedTx, $userId, $externalId);

        if (strtoupper($parsedTx->type) === 'TRANSFER') {
            $otherAccountNo = ($parsedTx->flow === 'OUT') ? $parsedTx->destinationAccount : $parsedTx->sourceAccount;
            if ($otherAccountNo) {
                $otherAccount = $this->resolveAccount($otherAccountNo, $userId, $parsedTx->bank, $externalId);
                if ($otherAccount && $otherAccount->id !== $account->id) {
                    $toAccount = $otherAccount;
                    $txType = 'transfer';
                    $internalTransferCandidate = true;
                }
            }
        }

        // 6. Category mapping: Lookup merchant_aliases by merchant_key.
        $category = null;
        $subcategory = null;
        $merchantKey = null;
        $rawMerchant = $parsedTx->merchant;

        if (!empty($rawMerchant) && strtolower($rawMerchant) !== 'unknown merchant') {
            $normalizer = app(MerchantNormalizer::class);
            $merchantKey = $normalizer->merchantKey($rawMerchant);

            $alias = MerchantAlias::where('merchant_key', $merchantKey)->first();
            if ($alias) {
                // If it already exists, update usage count and last seen
                $alias->update([
                    'usage_count'  => $alias->usage_count + 1,
                    'last_seen_at' => now(),
                ]);

                if ($alias->category_id) {
                    $resolvedCategory = Category::find($alias->category_id);
                    if ($resolvedCategory) {
                        $category = $resolvedCategory;
                        // Auto categorization succeeded, increment auto_categorized_count
                        $alias->increment('auto_categorized_count');
                        if (class_exists(\App\Services\Finance\Diagnostics\ImportDiagnostics::class)) {
                            \App\Services\Finance\Diagnostics\ImportDiagnostics::increment('auto_categorized');
                        }
                        if ($alias->subcategory_id) {
                            $subcategory = Subcategory::find($alias->subcategory_id);
                        }
                    }
                }
            } else {
                // Create a new alias record to start tracking it (defaults to uncategorized)
                MerchantAlias::create([
                    'merchant_key'   => $merchantKey,
                    'raw_merchant'   => $rawMerchant,
                    'display_name'   => $normalizer->normalizeMerchant($rawMerchant),
                    'category_id'    => null,
                    'subcategory_id' => null,
                    'usage_count'    => 1,
                    'confidence'     => 1.0,
                    'is_locked'      => false,
                    'first_seen_at'  => now(),
                    'last_seen_at'   => now(),
                ]);
                if (class_exists(\App\Services\Finance\Diagnostics\ImportDiagnostics::class)) {
                    \App\Services\Finance\Diagnostics\ImportDiagnostics::increment('merchant_learned');
                }
            }
        }

        if (!$category) {
            $category = Category::firstOrCreate(
                [
                    'name'    => 'Uncategorized',
                    'user_id' => $userId,
                    'type'    => ($txType === 'income') ? 'income' : 'expense',
                ],
                [
                    'owner'     => ($userId === 2) ? 'pacar' : 'afin',
                    'is_active' => true,
                ]
            );
        }

        // 7. Transaction creation & Item creation
        try {
            $transaction = DB::transaction(function () use (
                $externalId,
                $account,
                $toAccount,
                $txType,
                $parsedTx,
                $category,
                $subcategory,
                $merchantKey,
                $rawMerchant
            ) {
                // Build concise, human-readable description from structured fields.
                $description = $this->descriptionBuilder->build($parsedTx);
                $notes       = $this->descriptionBuilder->buildNotes($parsedTx);
                $txDate      = $parsedTx->transactionDate
                    ? $parsedTx->transactionDate->toDateString()
                    : now()->toDateString();

                $metadata = [
                    'category_id'      => $category->id,
                    'subcategory_id'   => $subcategory?->id,
                    'item_description' => $description,
                    'external_id'      => $externalId,
                    'merchant_key'     => $merchantKey,
                    'raw_merchant'     => $rawMerchant,
                ];

                if ($txType === 'income') {
                    $transaction = $this->kernelService->createIncome($account->id, $parsedTx->amount, $txDate, $notes, $metadata);
                } elseif ($txType === 'expense') {
                    $transaction = $this->kernelService->createExpense($account->id, $parsedTx->amount, $txDate, $notes, $metadata);
                } else {
                    // transfer
                    $transaction = $this->kernelService->createTransfer($account->id, $toAccount->id, $parsedTx->amount, $txDate, $notes, $metadata);
                }

                // Update the alias with last_transaction_id if a key exists
                if ($merchantKey) {
                    MerchantAlias::where('merchant_key', $merchantKey)
                        ->update(['last_transaction_id' => $transaction->id]);
                }

                return $transaction;
            });

            $message->update([
                'status'         => InboxMessage::STATUS_IMPORTED,
                'transaction_id' => $transaction->id,
                'imported_at'    => now(),
            ]);

            if ($internalTransferCandidate) {
                $raw = $message->raw_payload ?? [];
                $raw['internal_transfer_candidate'] = true;
                $message->update(['raw_payload' => $raw]);
            }

            return ImportResult::success($transaction->id);

        } catch (\Exception $e) {
            Log::error("Failed to import inbox message {$message->id}: " . $e->getMessage());
            $message->update(['status' => InboxMessage::STATUS_FAILED]);
            return new ImportResult('FAILED', null, 'Database transaction failed: ' . $e->getMessage());
        }
    }

    /**
     * Resolve account through the Identity Resolver or fallback to legacy lookup.
     */
    private function resolveAccount(?string $accountNo, int $userId, string $bankName, ?string $googleMessageId = null): ?Account
    {
        $useResolver = config('finance.use_identity_resolver', false);

        if ($useResolver) {
            $startTime = microtime(true);
            
            $provider = app(AccountProviderResolver::class)->normalizeProvider($bankName);
            
            // Resolve using AccountIdentityResolver
            $result = $this->identityResolver->resolve((string) $accountNo, $provider, $userId, 'STRICT');
            
            $elapsedMs = (microtime(true) - $startTime) * 1000.0;

            if ($result->isSuccess()) {
                // Determine statistics key
                $statKey = 'exact_match';
                if ($result->resolutionMethod === 'virtual_account_match') {
                    $statKey = 'virtual_account_match';
                } elseif ($result->resolutionMethod === 'alias_match') {
                    $statKey = 'alias';
                } elseif ($result->identifierType === 'phone') {
                    $statKey = 'phone';
                } elseif ($result->identifierType === 'alias') {
                    $statKey = 'alias';
                } elseif ($result->identifierType === 'display_name') {
                    $statKey = 'display_name';
                }

                IdentityResolutionStatistics::increment($statKey);
                ImportResolutionTrace::add(
                    $googleMessageId,
                    (string) $accountNo,
                    $result->normalizedIdentifier,
                    $provider,
                    $result->account->id,
                    $result->confidence,
                    $result->resolutionMethod,
                    false,
                    $elapsedMs,
                    $result->trace,
                    null
                );

                if (class_exists(\App\Services\Finance\Diagnostics\ImportDiagnostics::class)) {
                    \App\Services\Finance\Diagnostics\ImportDiagnostics::recordResolution('identity', null, $elapsedMs);
                }

                return $result->account;
            }

            // Fallback to legacy lookup
            $legacyAccount = $this->legacyFindAccount($accountNo, $userId, $bankName);
            $fallbackUsed = ($legacyAccount !== null);
            
            $statKey = $fallbackUsed ? 'legacy_fallback' : 'no_match';
            if ($result->failureCode === 'AMBIGUOUS') {
                $statKey = 'ambiguous';
            }

            IdentityResolutionStatistics::increment($statKey);
            ImportResolutionTrace::add(
                $googleMessageId,
                (string) $accountNo,
                $result->normalizedIdentifier,
                $provider,
                $legacyAccount?->id,
                0.0,
                $result->resolutionMethod,
                $fallbackUsed,
                $elapsedMs,
                $result->trace,
                $result->failureCode
            );

            if (class_exists(\App\Services\Finance\Diagnostics\ImportDiagnostics::class)) {
                $method = $fallbackUsed ? 'legacy_fallback' : 'no_match';
                \App\Services\Finance\Diagnostics\ImportDiagnostics::recordResolution($method, $result->failureCode, $elapsedMs);
            }

            return $legacyAccount;
        }

        // Direct legacy lookup
        return $this->legacyFindAccount($accountNo, $userId, $bankName);
    }

    /**
     * Legacy account lookup implementation (fallback).
     */
    private function legacyFindAccount(?string $accountNo, int $userId, string $bankName): ?Account
    {
        if (empty($accountNo)) {
            return $this->findAccountByBankFallback($userId, $bankName);
        }

        $digits = preg_replace('/[^0-9]/', '', $accountNo);
        if (empty($digits)) {
            return $this->findAccountByBankFallback($userId, $bankName);
        }

        // Priority 1: Match by exact account_number
        $account = Account::where('user_id', $userId)
            ->where('account_number', $digits)
            ->first();

        if ($account) {
            return $account;
        }

        // Try partial match where DB account_number is a suffix of digits
        $accounts = Account::where('user_id', $userId)
            ->whereNotNull('account_number')
            ->get();

        foreach ($accounts as $acc) {
            if (str_ends_with($digits, $acc->account_number) || str_ends_with($acc->account_number, $digits)) {
                return $acc;
            }
        }

        // Priority 2: Fallback by bank name
        return $this->findAccountByBankFallback($userId, $bankName);
    }

    private function findAccountByBankFallback(int $userId, string $bankName): ?Account
    {
        $bank = strtoupper(trim($bankName));
        $query = Account::where('user_id', $userId);

        if ($bank === 'MANDIRI') {
            $query->where('name', 'like', '%MANDIRI%');
        } elseif ($bank === 'BCA') {
            $query->where('name', 'like', '%BCA%');
        } elseif ($bank === 'BNI') {
            $query->where('name', 'like', '%BNI%');
        } else {
            return null;
        }

        return $query->first();
    }

    private function isInternalTransferCandidate(\App\DTO\ParsedTransaction $parsedTx, int $userId, ?string $googleMessageId = null): bool
    {
        if (strtoupper($parsedTx->type) !== 'TRANSFER') {
            return false;
        }

        if ($this->counterpartyMatchesOwnAccount($parsedTx->counterparty, $userId)) {
            return true;
        }

        if (!empty($parsedTx->destinationAccount) && $this->resolveAccount($parsedTx->destinationAccount, $userId, $parsedTx->bank, $googleMessageId)) {
            return true;
        }

        return !empty($parsedTx->metadata['internal_transfer_candidate']);
    }

    private function counterpartyMatchesOwnAccount(?string $counterparty, int $userId): bool
    {
        $value = strtolower(trim((string) $counterparty));
        if ($value === '') {
            return false;
        }

        $accounts = Account::where('user_id', $userId)->get(['name', 'owner']);

        foreach ($accounts as $account) {
            foreach ([$account->name, $account->owner] as $candidate) {
                $candidateValue = strtolower(trim((string) $candidate));
                if ($candidateValue !== '' && $candidateValue === $value) {
                    return true;
                }
            }
        }

        return false;
    }
}
