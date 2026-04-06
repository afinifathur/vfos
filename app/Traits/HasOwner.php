<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasOwner
{
    protected static function bootHasOwner()
    {
        static::addGlobalScope('rbac_ownership', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();
                $role = $user->role;

                if ($role === 'partner') {
                    $builder->where($builder->getQuery()->from . '.owner', 'pacar');
                } elseif ($role === 'business') {
                    $builder->where($builder->getQuery()->from . '.owner', 'business');
                } else {
                    $builder->where($builder->getQuery()->from . '.owner', 'afin');
                }
                // Admin (afin) can see all owners if they had a super role, but to stop mixing, we default to afin
            }
        });

        static::creating(function ($model) {
            if (Auth::check() && empty($model->owner)) {
                $user = Auth::user();
                if ($user->role === 'partner') {
                    $model->owner = 'pacar';
                } elseif ($user->role === 'business') {
                    $model->owner = 'business';
                } else {
                    $model->owner = 'afin';
                }
            }
        });
    }
}
