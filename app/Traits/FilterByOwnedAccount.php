<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait FilterByOwnedAccount
{
    protected static function bootFilterByOwnedAccount()
    {
        static::addGlobalScope('rbac_transaction_ownership', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();
                $role = $user->role;

                if ($role === 'partner') {
                    $builder->whereHas('account', function($q) {
                        $q->where('owner', 'pacar');
                    });
                } elseif ($role === 'business') {
                    $builder->whereHas('account', function($q) {
                        $q->where('owner', 'business');
                    });
                }
            }
        });
    }
}
