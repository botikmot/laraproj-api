<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Message;
use App\Events\GroupMessageSent;
use App\Events\UserRead;
use App\Models\MessageRead;
use App\Models\UserGroupActivity;
use DB;

class MessageController extends Controller
{
    public function sendMessageToGroup(Request $request, $groupId)
    {
        // Validate the request data
        $request->validate([
            'content' => 'required|string',
            'attachments.*' => 'nullable|max:5048'
        ]);

        // Check if the user is a member of the group
        $group = Group::findOrFail($groupId);
        $user = auth()->user();
        if (!$group->users->contains($user)) {
            return response()->json(['error' => 'You are not a member of this group'], 403);
        }

        // Create a new message for the group
        $message = new Message();
        $message->user_id = $user->id;
        $message->group_id = $groupId;
        $message->content = $request->input('content');
        $message->save();

        if ($request->hasFile('attachments')) {
            // store the attachments and attach them to the post
            $attachments = $request->file('attachments');
            foreach ($attachments as $attachment) {
                $path = $attachment->store('group_attachments', 'public');
                $filename = $attachment->getClientOriginalName();
                $message->attachments()->create(['path' => $path, 'filename' => $filename]);
            }
            //$message->load('attachments');
        }

        $active_users = UserGroupActivity::with('user.profile')->where('group_id', $groupId)->get();
        // Broadcast the GroupMessageSent event
        $message = $message->load('user.profile', 'attachments');
        $message->latest_read = $active_users;

        event(new GroupMessageSent($message, $groupId, $active_users));

        return response()->json(['message' => 'Message sent successfully', 'data' => $message]);
    }

    public function getGroupMessages($groupId)
    {
        $group = Group::findOrFail($groupId);

        // Check if the user is a member of the group.
        $user = auth()->user();
        if (!$group->users->contains($user)) {
            return response()->json(['error' => 'You are not a member of this group'], 403);
        }
        // Fetch all messages for the group
        $messages = Message::where('group_id', $groupId)->orderBy('created_at', 'desc')->paginate(20);

         // Update user activity in the group
        UserGroupActivity::updateOrCreate(
            ['user_id' => $user->id],
            ['group_id' => $groupId, 'updated_at' => now()]
        );

        event(new UserRead($groupId, $user->load('profile')));

        // Mark all unread messages in the group chat as read for the user
        $unreadMessages = $group->unreadMessagesForUser($user);
        foreach ($unreadMessages as $message) {
            $message->markAsReadByUser($user);
        }

        $messageRead = new MessageRead();
        $latestReadMessages = $messageRead->latestReadByUsersInGroup($groupId)->get();

        // Process the messages to include the latest read data
        $messages->getCollection()->transform(function ($message) use ($latestReadMessages) {
            $latestRead = $latestReadMessages->filter(function ($item) use ($message) {
                return $item->latest_message_id === $message->id;
            });
        
            // Convert object to array if it's not already an array
            $message->latest_read = $latestRead->values()->all(); // Forces the structure to be an array
            return $message;
        });    
        
        $messages->load('user.profile', 'attachments');
        
        return response()->json(['messages' => $messages, 'latest_message_read' => $latestReadMessages]);
    }

    public function markMessagesAsRead(Request $request)
    {
        $user = auth()->user();
        $messageIds = $request->input('message_ids');

        if (!empty($messageIds)) {
            // Find the messages with the provided message IDs
            $messages = Message::whereIn('id', $messageIds)->get();

            foreach ($messages as $message) {
                // Use the updateExistingPivot method to mark the message as read for the user
                $message->users()->updateExistingPivot($user->id, ['is_read' => true]);
            }

            return response()->json(['message' => 'Messages marked as read']);
        }

        return response()->json(['error' => 'No message IDs provided'], 400);
    }

    public function countUnreadMessages($groupId)
    {
        $user = auth()->user();
        
        $unreadCount = Message::where('group_id', $groupId)
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('is_read', false);
            })
            ->count();
        
        return response()->json(['unread_count' => $unreadCount]);
    }

    public function markAllMessagesAsRead()
    {
        $user = auth()->user();

        // Get all messages that belong to the user and are unread
        $userMessages = $user->messages()->wherePivot('is_read', false)->get();

        // Mark each message as read
        foreach ($userMessages as $message) {
            $message->users()->updateExistingPivot($user->id, ['is_read' => true]);
        }

        return response()->json(['message' => 'All messages marked as read']);
    }

    public function markAllGroupMessagesAsRead($groupId)
    {
        $user = auth()->user();

        // Get all messages in the specified group that belong to the user and are unread
        $groupMessages = Message::where('group_id', $groupId)
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('is_read', false);
            })
            ->get();

        // Mark each message as read
        foreach ($groupMessages as $message) {
            $message->users()->updateExistingPivot($user->id, ['is_read' => true]);
        }

        return response()->json(['message' => 'All group messages marked as read']);
    }



}
