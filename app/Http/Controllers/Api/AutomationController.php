<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutomationController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Log::info('Automation Request:', $request->all());

        // Skip failed transaction
        if ($request->status === 'Failed') {
            return response()->json([
                'message' => 'Skipping failed transaction'
            ], 200);
        }

        // =======================
        // ACCOUNT MATCHING (BY BANK)
        // =======================
        $account = null;
        $bank = strtoupper($request->bank ?? '');

        if ($bank === 'MANDIRI') {
            $account = Account::where('name', 'like', '%MANDIRI%')->first();
        } elseif ($bank === 'BCA') {
            $account = Account::where('name', 'like', '%BCA%')->first();
        }

        if (!$account) {
            return response()->json([
                'message' => "Account not found for bank: {$bank}"
            ], 404);
        }

        // =======================
        // DATE PARSING
        // =======================
        try {
            $transactionDate = Carbon::parse($request->date);
        } catch (\Exception $e) {
            $transactionDate = now();
        }

        // =======================
        // DETECT TYPE
        // =======================
        $type = 'expense';

        $requestType = strtolower($request->type ?? '');

        if (
            str_contains($requestType, 'transfer') &&
            str_contains($requestType, 'credit')
        ) {
            $type = 'income';
        }

        // =======================
        // CATEGORY
        // =======================
        $category = Category::firstOrCreate(
            ['name' => 'Uncategorized'],
            ['type' => $type]
        );

        $externalId = $request->external_id;

        // =======================
        // DATABASE TRANSACTION
        // =======================
        return DB::transaction(function () use (
            $request,
            $account,
            $transactionDate,
            $type,
            $category,
            $externalId
        ) {
            $transactionData = [
                'account_id'       => $account->id,
                'type'             => $type,
                'transaction_date' => $transactionDate->toDateString(),
                'total_amount'     => $request->amount,
                'notes'            => "Automated: {$request->merchant} ({$request->type}) - {$request->raw_amount}",
            ];

            $transaction = null;

            // Prevent duplicate if external_id exists
            if ($externalId) {
                $transaction = Transaction::firstOrCreate(
                    ['external_id' => $externalId],
                    $transactionData
                );

                if (!$transaction->wasRecentlyCreated) {
                    return response()->json([
                        'message' => 'Duplicate ignored',
                        'id'      => $transaction->id
                    ], 200);
                }
            } else {
                $transaction = Transaction::create($transactionData);
            }

            // Create transaction item
            $transaction->transactionItems()->create([
                'category_id' => $category->id,
                'description' => "{$request->merchant} - {$request->type}",
                'amount'      => $request->amount,
            ]);

            return response()->json([
                'message' => 'Transaction automated successfully',
                'id'      => $transaction->id
            ], 201);
        });
    }
}