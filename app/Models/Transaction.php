<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'type', 
        'subtype', 
        'amount', 
        'currency', 
        'from_account', 
        'to_account', 
        'exchange_rate',
        'description', 
        'date',
    ];

    /**
     * Define relationship to the User model.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
