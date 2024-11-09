<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'content', 'sent_by_user', 'from', 'message_id', 'timestamp', 'type', 'status', 'error_data'];

    protected $table = 'messages';

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
    public function file()
    {
        return $this->hasOne(FileModel::class);
    }

}
