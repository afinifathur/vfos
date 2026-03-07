<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
    /** @use HasFactory<\Database\Factories\DebtFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'total_amount', 'remaining_amount', 'due_date', 'status', 'notes'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
