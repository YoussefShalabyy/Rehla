<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'wallet_id',
        'type',
        'amount_cents',
        'description',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
