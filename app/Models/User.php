<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // Relationships
    public function accounts()      { return $this->hasMany(Account::class); }
    public function categories()    { return $this->hasMany(Category::class); }
    public function budgets()       { return $this->hasMany(Budget::class); }
    public function debts()         { return $this->hasMany(Debt::class); }
    public function receivables()   { return $this->hasMany(Receivable::class); }
    public function investments()   { return $this->hasMany(Investment::class); }
    public function assets()        { return $this->hasMany(Asset::class); }
}
