<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marquiz extends Model
{
    use HasFactory;

    protected $table = 'marquiz';

    protected $fillable = [
        'body',
        'phone',
        'email',
        'name',
        'city',
        'roistat',
        'status',
        'lead_id',
        'contact_id',
    ];
}
