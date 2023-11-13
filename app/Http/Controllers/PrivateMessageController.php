<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PrivateMessage;
use App\Models\User;

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

        return response()->json(['message' => 'Private message sent successfully', 'privateMessage' => $privateMessage]);
    }

    public function getPrivateMessages(Request $request)
    {
        $user = auth()->user();

        // Fetch all private messages sent by or received by the user
        $privateMessages = PrivateMessage::where('sender_id', $user->id)
            ->orWhere('recipient_id', $user->id)
            ->get();

        return response()->json(['privateMessages' => $privateMessages]);
    }

}
