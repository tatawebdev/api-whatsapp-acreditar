<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'content', 'sent_by_user', 'from', 'message_id', 'timestamp', 'type', 'status', 'error_data', 'conversation_session_id', 'unique_identifier'];

    protected $table = 'messages';

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
    public function file()
    {
        return $this->hasOne(FileModel::class);
    }
    public function fileby_content()
    {
        return $this->hasOne(FileModel::class, 'file_id', 'content');
    }
    public function conversation_section()
    {
        return $this->hasOne(ConversationSession::class, 'id', 'conversation_session_id');
    }
}
