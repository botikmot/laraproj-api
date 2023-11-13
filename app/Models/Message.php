<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class Message extends Model
{
    use HasFactory;
    protected $fillable = ['content'];

    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function readByUsers()
    {
        return $this->hasMany(MessageRead::class)->select('message_id','user_id')->distinct();
    }

    public function markAsReadByUser(User $user)
    {
        $this->readByUsers()->updateOrCreate(
            ['user_id' => $user->id],
            ['read_at' => now()]
        );
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }


}
