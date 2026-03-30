<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasOwner;

class Investment extends Model
{
    use HasFactory, HasOwner;
    
    protected $fillable = [
        'user_id',
        'owner',
        'name',
        'ticker',
        'asset_class',
        'scraping_url',
        'currency',
        'price_unit',
        'quantity',
        'average_cost',
        'current_price',
        'goal_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity'      => 'decimal:4',
            'average_cost'  => 'decimal:2',
            'current_price' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function goal()
    {
        return $this->belongsTo(Goal::class);
    }

    protected function marketValue(): Attribute
    {
        return Attribute::get(fn () => $this->quantity * $this->current_price);
    }

    protected function totalCost(): Attribute
    {
        return Attribute::get(fn () => $this->quantity * $this->average_cost);
    }

    protected function gainLoss(): Attribute
    {
        return Attribute::get(fn () => $this->market_value - $this->total_cost);
    }

    protected function gainLossPercentage(): Attribute
    {
        return Attribute::get(fn () => $this->total_cost > 0 ? ($this->gain_loss / $this->total_cost) * 100 : 0);
    }
}
