<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PrivateMessage;
use App\Events\PrivateMessageSent;
use App\Models\User;
use App\Models\UserProfile;
use DB;

class PrivateMessageController extends Controller
{
    public function sendPrivateMessage(Request $request, $recipientId)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        //$recipient = User::findOrFail($recipientId);

        // Check if the sender is a member of the group and the recipient is a member of the same group.
       // $groupId = $request->input('group_id');
        //$group = Group::findOrFail($groupId);
        $sender = auth()->user();
        
        /* if (!$group->users->contains($sender) || !$group->users->contains($recipient)) {
            return response()->json(['error' => 'Sender or recipient is not a member of the group'], 403);
        } */

        // Create a new private message
        $privateMessage = new PrivateMessage();
        $privateMessage->sender_id = $sender->id;
        $privateMessage->recipient_id = $recipientId; //$recipient->id;
        $privateMessage->content = $request->input('content');
        $privateMessage->save();

        $privateMessage = $privateMessage->load('sender.profile', 'attachments');

        event(new PrivateMessageSent($privateMessage, $recipientId));

        return response()->json(['message' => 'Private message sent successfully', 'privateMessage' => $privateMessage]);
    }

    public function getPrivateMessages(Request $request)
    {
        $user = auth()->user();

        $privateMessages = PrivateMessage::with([
            'sender:id,name',    // Replace 'name' with the actual field in the User model
            'recipient:id,name', // Replace 'name' with the actual field in the User model
        ])
        ->where(function ($query) use ($user) {
            $query->where('sender_id', $user->id)
                ->orWhere('recipient_id', $user->id);
        })
        ->orderBy('created_at', 'desc')
        ->get();
    
        // Collect unique users with their latest messages
        $usersWithLatestMessages = collect([]);
        foreach ($privateMessages as $message) {
            $otherUser = ($message->sender_id == $user->id) ? $message->recipient : $message->sender;

            // Skip adding the authenticated user to the collection
            if ($otherUser->id == $user->id) {
                continue;
            }

            // Ensure we only include each user once
            if (!$usersWithLatestMessages->contains('id', $otherUser->id)) {
                $usersWithLatestMessages->push($otherUser);
                $otherUser->latest_message = $message->content;
            }
        }

        // Fetch profiles for the users
        $userIds = $usersWithLatestMessages->pluck('id');
        $profiles = UserProfile::whereIn('user_id', $userIds)->get();

        // Merge the profiles into the $usersWithLatestMessages collection
        $usersWithLatestMessages = $usersWithLatestMessages->map(function ($user) use ($profiles) {
            $matchedProfile = $profiles->where('user_id', $user->id)->first();

            // Check if a profile was found
            if ($matchedProfile) {
                $user->profile = $matchedProfile;
            } else {
                $user->profile = null; // Set profile to null if not found
            }

            return $user;
        });

        return response()->json(['privateMessages' => $usersWithLatestMessages]);

    }

    public function getRecipientMessages($recipientId)
    {
        $user = auth()->user();
        // Fetch all private messages sent by or received by the user
        $privateMessages = PrivateMessage::with(['sender.profile', 'recipient.profile', 'attachments'])
            ->where(function ($query) use ($user, $recipientId) {
                $query->where('sender_id', $user->id)
                    ->where('recipient_id', $recipientId)
                    ->orWhere('sender_id', $recipientId)
                    ->where('recipient_id', $user->id);
            })
            ->orderBy('created_at', 'desc')->paginate(20);

        return response()->json(['recipientMessages' => $privateMessages]);
    }


    public function getRecipients()
    {
        // Get the authenticated user
        $authUser = auth()->user();

        // Retrieve the private messages sent by the authenticated user
        $privateMessagesSent = $authUser->privateMessagesSent;

        // Collect unique recipient users from the sent messages
        $recipients = $privateMessagesSent->map(function ($message) {
            $recipient = $message->recipient->load('profile'); // Assuming 'profile' is the relationship name
        
            // Add the latest message to the recipient
            $latestMessage = PrivateMessage::where('sender_id', $message->sender_id)
                ->where('recipient_id', $message->recipient_id)
                ->latest()
                ->first();
        
            $recipient->latest_message = $latestMessage;
        
            return $recipient;
        })->unique();

        return response()->json(['recipients' => $recipients]);

    }

}
