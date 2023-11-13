<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Message;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'visibility'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'group_user', 'group_id', 'user_id')
            ->withPivot('role', 'read_at');
    }

    /* public function unreadMessages()
    {
        return $this->hasMany(Message::class)->where('is_read', false)->where('user_id', '!=', auth()->user()->id)->count();
    } */

    public function unreadMessagesForUser(User $user)
    {
        return Message::where('group_id', $this->id)
            ->whereDoesntHave('readByUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with('user') // Load the user associated with the message
            ->where('user_id', '!=', $user->id)
            ->get();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }


}
