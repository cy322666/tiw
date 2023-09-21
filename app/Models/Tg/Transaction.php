<?php

namespace App\Models\Tg;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'wait',
        'msg_id',
        'user_id',
        'is_bot',
        'first_name',
        'username',
    ];
}
