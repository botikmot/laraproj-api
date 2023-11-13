<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MessageRead extends Model
{
    use HasFactory;

    protected $fillable = ['message_id', 'user_id', 'read_at'];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function latestReadByUsersInGroup($groupId)
    {
        return MessageRead::select('user_id', DB::raw('MAX(message_id) as latest_message_id'), DB::raw('MAX(read_at) as latest_read_at'))
            ->with('user.profile') // Load the user relationship
            ->whereHas('message', function ($query) use ($groupId) {
                $query->where('group_id', $groupId);
            })
            ->groupBy('user_id');
    }


}
