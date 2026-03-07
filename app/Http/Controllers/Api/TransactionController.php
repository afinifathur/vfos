<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Http\Requests\TransactionStoreRequest;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function store(TransactionStoreRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $transaction = Transaction::create($request->validated());

            foreach ($request->items as $item) {
                $transaction->transactionItems()->create($item);
            }

            return response()->json([
                'message' => 'Transaction created successfully',
                'data' => $transaction->load('transactionItems')
            ], 201);
        });
    }
}
