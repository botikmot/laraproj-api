<?php

namespace App\Http\Controllers\UserProfile;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Events\UserInactive;

class UserProfileController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png|max:2048', // Validate and limit file types and size
        ]);

        
        $user = auth()->user();
        // Check if the user has a profile; create one if not
        if (!$user->profile) {
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('profile_photos', 'public');
            }
            $user->profile()->create([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'address' => $request->input('address'),
                'phone_number' => $request->input('phone_number'),
                'birthday' => $request->input('birthday'),
                'bio' => $request->input('bio'),
                'profile_photo' => $photoPath ? '/' . $photoPath : null,
            ]);
        } else {
            // Handle file upload (if a new photo is provided)
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('profile_photos', 'public');
                $user->profile->update([
                    'first_name' => $request->input('first_name'),
                    'last_name' => $request->input('last_name'),
                    'address' => $request->input('address'),
                    'phone_number' => $request->input('phone_number'),
                    'birthday' => $request->input('birthday'),
                    'bio' => $request->input('bio'),
                    'profile_photo' => $photoPath ? '/' . $photoPath : null,
                ]);
            } else {
                // No new photo provided, update other profile data
                $user->profile->update([
                    'first_name' => $request->input('first_name'),
                    'last_name' => $request->input('last_name'),
                    'address' => $request->input('address'),
                    'phone_number' => $request->input('phone_number'),
                    'birthday' => $request->input('birthday'),
                    'bio' => $request->input('bio'),
                ]);
            }
        }

        $user = $user->load('profile');
    
        return response()->json(['message' => 'Profile updated successfully', 'user' => $user]);
    }

    public function userHeartbeat(Request $request)
    {
        $user = auth()->user();
        $prevStatus = false;

        // Check if the user was previously marked as offline
        if (!$user->online) {
            $prevStatus = true;
        }

        $user->update(['last_seen' => Carbon::now()->toDateTimeString(), 'online' => true]);
        
        if($prevStatus){
             // User has gone back to heartbeat, execute the event
            $user->load('profile');
            event(new UserInactive($user, 'online'));
        }

        return response()->json(['message' => 'Heartbeat received']);
    }

}
