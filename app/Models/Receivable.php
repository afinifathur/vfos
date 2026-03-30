<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasOwner;

class Receivable extends Model
{
    /** @use HasFactory<\Database\Factories\ReceivableFactory> */
    use HasFactory, HasOwner;

    protected $fillable = ['user_id', 'owner', 'name', 'total_amount', 'remaining_amount', 'due_date', 'status', 'notes'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
