<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasOwner;

class Asset extends Model
{
    use HasFactory, HasOwner;

    protected $fillable = [
        'user_id',
        'owner',
        'name',
        'description',
        'type',
        'purchase_price',
        'current_value'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
