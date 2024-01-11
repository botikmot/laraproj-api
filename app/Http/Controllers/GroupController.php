<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\User;
use App\Models\UserGroupActivity;

class GroupController extends Controller
{
    public function createGroup(Request $request)
    {
        // Validate the request data
        $request->validate([
            'name' => 'required|unique:groups|string|max:255',
        ]);

        // Create the group
        $group = Group::create([
            'name' => $request->input('name'),
            'description' => $request->input('description')
        ]);

        // Attach the current user as the creator and admin of the group
        $group->users()->attach(auth()->user()->id, ['role' => 'creator']);
        //$group->users()->attach(auth()->user()->id, ['role' => 'admin']);

        return response()->json(['message' => 'Group created successfully', 'group' => $group]);
    }

    public function getUserGroups()
    {
        $user = auth()->user();
        // Fetch the groups of the authenticated user with the roles 'member', 'admin', and 'creator'
        $groups = $user->groups()
                    ->with('users.profile', 'latestMessage')
                    ->get();
        
        // Calculate the unread messages for each group
        $groups->each(function ($group) use ($user) {
            $unreadMessages = $group->unreadMessagesForUser($user);
            $group->unread_messages = $unreadMessages;
        });
       
        //$groupCounts = $user->unreadMessagesCountPerGroup();

        return response()->json(['groups' => $groups]);
    }

    public function addMember(Request $request, $groupId)
    {
        // Find the group
        $group = Group::findOrFail($groupId);

        // Check if the current user is an admin or has the appropriate permissions to add members
        $user = auth()->user();
        if (
            !$group->users->contains($user) ||
            (
                $group->users()
                    ->wherePivot('role', 'admin')
                    ->orWhere('role', 'creator')
                    ->where('group_user.user_id', $user->id)
                    ->count() == 0
            )
        ) {
            return response()->json(['error' => 'You do not have permission to add members to this group'], 403);
        }            

        // Find the user to be added
        $userId = $request->input('user_id');
        $userToBeAdded = User::findOrFail($userId);

        // Attach the user to the group with the desired role (e.g., member)
        $group->users()->attach($userToBeAdded->id, ['role' => 'member']);

        return response()->json(['message' => 'User added to the group successfully']);
    }

    public function getAllUsers()
    {
        $currentUser = auth()->user(); // Get the authenticated user
        $users = User::where('id', '!=', $currentUser->id)->get();
        $users->load('profile');
        return response()->json(['users' => $users]);
    }

    public function removeUser(Request $request)
    {
        $userId = $request->input('userId');
        UserGroupActivity::where('user_id', $userId)->delete();
        return response()->json(['message' => 'User removed from group activity successfully']);
    }


}
