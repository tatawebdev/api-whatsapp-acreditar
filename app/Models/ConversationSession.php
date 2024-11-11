<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ConversationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'from',
        'session_start',
        'session_end',
        'whatsapp_conversation_id',
        'whatsapp_origin_type',
        'whatsapp_expiration_timestamp',
        'whatsapp_expiration_datetime',
        'whatsapp_billable',
        'whatsapp_pricing_model',
        'whatsapp_pricing_category',
    ];

    protected $casts = [
        'session_start' => 'datetime',
        'session_end' => 'datetime',
        'whatsapp_expiration_timestamp' => 'integer',
        'whatsapp_billable' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }


    public function message()
    {
        return $this->belongsTo(Message::class, 'id', 'conversation_session_id');
    }

    public static function activeSession($from)
    {

        return self::where('from', $from)
            ->where('session_end', '>', Carbon::now())
            ->first();
    }

    /**
     * Cria uma nova sessão interna de 24 horas
     */
    public static function createNewSession($conversationId, $from)
    {
        $start = Carbon::now();
        $end = $start->copy()->addHours(24);

        return self::create([
            'conversation_id' => $conversationId,
            'from' => $from,
            'session_start' => $start,
            'session_end' => $end,
        ]);
    }
}
