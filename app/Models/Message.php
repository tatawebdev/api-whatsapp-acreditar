<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'content', 'sent_by_user'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
