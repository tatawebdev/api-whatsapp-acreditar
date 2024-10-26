<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookData extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'celular',
        'conta_id',
        'api_phone_id',
        'api_phone_number',
        'status',
        'status_id',
        'conversation',
        'json',
    ];

    protected $casts = [
        'conversation' => 'array',
        'json' => 'array',
    ];
}
