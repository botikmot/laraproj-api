<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $message;
    public $groupId;
    public $activeUsers;

    public function __construct($message, $groupId, $activeUsers)
    {
        $this->message = $message;
        $this->groupId = $groupId;
        $this->activeUsers = $activeUsers;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new Channel('group.' . $this->groupId);
        /* return [
            new PrivateChannel('channel-name'),
        ]; */
    }

    public function broadcastAs()
    {
        return 'group-message';
    }

    public function broadcastWith()
    {
        // Include the user.profile data for each active user
        $activeUsersWithProfile = $this->activeUsers->map(function ($userGroupActivity) {
            return [
                'user' => $userGroupActivity->user,
                'profile' => $userGroupActivity->user->profile,
                // Add other fields as needed
            ];
        });

        return [
            'activeUsers' => $activeUsersWithProfile,
            'message' => $this->message,
            'groupId' => $this->groupId
        ];
    }

}
