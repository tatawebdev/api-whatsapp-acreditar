<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FcmToken extends Model
{
    protected $table = 'phone_token_fcm';

    protected $fillable = ['fcm_token', 'phone_number'];
}
