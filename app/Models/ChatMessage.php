<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','content'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function seenBy()
    {
        return $this->belongsToMany(User::class, 'chat_user', 'last_read_message_id', 'user_id')
            ->withPivot('last_read_message_id')->with(['profile']);
    }

    public function markAsSeenBy(User $user, Chat $chat)
    {
        $user->chats()->updateExistingPivot($chat, ['last_read_message_id' => $this->id]);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

}
