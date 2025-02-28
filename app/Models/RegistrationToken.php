<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationToken extends Model
{
    protected $table = 'registration_tokens';

    protected $fillable = [
        'token',
        'created_at',
        'expires_at',
    ];

    public $timestamps = false;

}
