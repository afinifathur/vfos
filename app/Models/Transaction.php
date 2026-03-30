<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Account;
use App\Models\TransactionItem;

use App\Traits\FilterByOwnedAccount;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory, FilterByOwnedAccount;

    protected $fillable = ['account_id', 'to_account_id', 'type', 'transaction_date', 'total_amount', 'notes'];

    protected static function booted()
    {
        static::saved(function ($transaction) {
            $transaction->refreshAccountBalances();
        });

        static::deleted(function ($transaction) {
            $transaction->refreshAccountBalances();
        });
    }

    public function refreshAccountBalances()
    {
        if ($this->account) {
            $this->account->total_balance = $this->account->calculateBalance();
            $this->account->save();
        }

        if ($this->type === 'transfer' && $this->toAccount) {
            $this->toAccount->total_balance = $this->toAccount->calculateBalance();
            $this->toAccount->save();
        }
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class);
    }
}
