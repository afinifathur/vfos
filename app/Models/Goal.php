<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasOwner;

class Goal extends Model
{
    use HasOwner;

    protected $fillable = [
        'user_id', 'owner', 'name', 'target_amount', 'target_date', 'color', 'is_completed', 'notes'
    ];

    protected $casts = [
        'target_date' => 'date',
        'is_completed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    // Helper functions for progress computation
    public function getAccountsBalanceAttribute()
    {
        return $this->accounts->sum(function($account) {
            return $account->calculateBalance();
        });
    }

    public function getInvestmentsValueAttribute()
    {
        return $this->investments->sum('market_value');
    }

    public function getCurrentAmountAttribute()
    {
        return $this->accounts_balance + $this->investments_value;
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->target_amount <= 0) return 100;
        $pct = ($this->current_amount / $this->target_amount) * 100;
        return min(max($pct, 0), 100);
    }
}
