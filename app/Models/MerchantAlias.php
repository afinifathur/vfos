<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantAlias extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_key',
        'raw_merchant',
        'display_name',
        'category_id',
        'subcategory_id',
        'usage_count',
        'auto_categorized_count',
        'confidence',
        'is_locked',
        'last_transaction_id',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'confidence' => 'float',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function lastTransaction()
    {
        return $this->belongsTo(Transaction::class, 'last_transaction_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'merchant_key', 'merchant_key');
    }
}
