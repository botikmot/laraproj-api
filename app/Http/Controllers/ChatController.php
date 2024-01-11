<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\User;
use App\Models\ChatMessage;
use App\Events\ChatMessageEvent;
use App\Events\UserRead;
use Illuminate\Support\Facades\Auth;
use DB;

class ChatController extends Controller
{
    public function createPrivateChat(User $user1, User $user2)
    {
        // Check if a chat already exists between the two users
        $existingPrivateChat = Chat::whereHas('users', function ($query) use ($user1, $user2) {
            $query->whereIn('user_id', [$user1->id, $user2->id]);
        }, '=', 2)
        ->whereDoesntHave('users', function ($query) use ($user1, $user2) {
            $query->where('user_id', '!=', $user1->id)->where('user_id', '!=', $user2->id);
        })
        ->first();

        if ($existingPrivateChat) {
            return response()->json(['message' => 'Chat already exists between the users', 'chat' => $existingPrivateChat]);
        }
    
        // If no existing chat, create a new one
        $chat = Chat::create();
        $chat->users()->attach([$user1->id, $user2->id]);

        return response()->json(['message' => 'Chat created successfully', 'chat' => $chat]);
    }

    public function createGroupChat(Request $request)
    {
        // Include the authenticated user ID in the list of user IDs
        $userIds = $request->input('user_ids');
        $userIds[] = auth()->id(); // Add the authenticated user ID to the array

        $chat = Chat::create([
            'name' => $request->input('name'),
            'description' => $request->input('description')
        ]);
        $chat->users()->attach($userIds);

        return response()->json(['message' => 'Group created successfully', 'group' => $chat]);
    }

    public function getUserChats()
    {
        $user = Auth::user();
        
        $chats = $user->chats()
                ->with('users.profile')
                ->withCount(['messages as unread_messages_count' => function ($query) use ($user) {
                    $query->where('user_id', '<>', $user->id)
                        ->whereNotExists(function ($subQuery) use ($user) {
                            $subQuery->select(DB::raw(1))
                                ->from('chat_user')
                                ->whereRaw('chat_user.chat_id = chats.id')
                                ->where('user_id', $user->id)
                                ->where('last_read_message_id', '>=', DB::raw('chat_messages.id'));
                        })
                        ->whereDoesntHave('seenBy', function ($subQuery) use ($user) {
                            $subQuery->where('user_id', $user->id);
                        });
                }])
                ->get();

        return response()->json($chats);
    }

    public function seenMessage(Chat $chat, ChatMessage $message)
    {
        $user = Auth::user();
        $message->markAsSeenBy($user, $chat);
        $message->seenBy;

        event(new UserRead($message, $user->load('profile')));
        return response()->json(['success' => true, 'message' => $message]);
    }

    public function sendMessage(Chat $chat, Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'attachments.*' => 'nullable|max:5048'
        ]);

        $user = Auth::user();
        $message = new ChatMessage([
            'content' => $request->input('content'),
            'user_id' => $user->id,
        ]);
        $chat->messages()->save($message);

        if ($request->hasFile('attachments')) {
            // store the attachments and attach them to the post
            $attachments = $request->file('attachments');
            foreach ($attachments as $attachment) {
                $path = $attachment->store('chat_attachments', 'public');
                $filename = $attachment->getClientOriginalName();
                $message->attachments()->create(['path' => $path, 'filename' => $filename]);
            }
        }

        $message->load('user.profile', 'attachments');

        $message->markAsSeenBy($user, $chat);
    
        $message->seenBy;
        event(new ChatMessageEvent($message));

        return response()->json(['success' => true, 'chat' => $chat, 'message' => $message]);
    }

    public function getChatMessages(Chat $chat)
    {
        $page = request()->input('page', 1);

        $messages = $chat->messages()->with('user.profile', 'attachments')->orderBy('created_at', 'desc')->paginate(10);
        $user = Auth::user();
        
        if ($page == 1) {
            $messages[0]->markAsSeenBy($user, $chat);
            event(new UserRead($messages[0], $user->load('profile')));
        }

        $usersWhoReadLastMessage = [];
        foreach ($messages as $message) {
            $usersWhoReadLastMessage = $message->seenBy; 
        }

        // Retrieve the last message in the chat
        $lastMessage = $messages->first();

        // If there's a last message, get the users who have seen it
        if ($lastMessage) {
            $usersWhoReadLastMessage = $lastMessage->seenBy->toArray();
        }

        // Use array_unique to remove duplicate users
        $usersWhoReadLastMessage = array_unique($usersWhoReadLastMessage, SORT_REGULAR);

        return response()->json([
            'messages' => $messages,
            'active_users' => $usersWhoReadLastMessage,
        ]);
    
    }

}
