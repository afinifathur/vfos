<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Transaction;

use App\Traits\HasOwner;

class Account extends Model
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use HasFactory, HasOwner;

    protected $fillable = ['user_id', 'name', 'type', 'owner', 'is_active', 'goal_id', 'icon_path', 'initial_balance', 'total_balance'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function goal()
    {
        return $this->belongsTo(Goal::class);
    }

    public function calculateBalance()
    {
        $income = $this->transactions()->where('type', 'income')->sum('total_amount');
        $expense = $this->transactions()->where('type', 'expense')->sum('total_amount');
        
        // Transfers outgoing (where this account is account_id)
        $outgoingTransfers = $this->transactions()->where('type', 'transfer')->sum('total_amount');
        
        // Transfers incoming (where this account is to_account_id)
        $incomingTransfers = Transaction::where('to_account_id', $this->id)
            ->where('type', 'transfer')
            ->sum('total_amount');

        // Withdrawals (where this account is account_id)
        $withdrawals = $this->transactions()->where('type', 'withdrawal')->sum('total_amount');
        
        return $this->initial_balance + $income + $incomingTransfers - $expense - $outgoingTransfers - $withdrawals;
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
