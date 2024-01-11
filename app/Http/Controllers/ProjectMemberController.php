<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectMember;
use App\Models\Project;

class ProjectMemberController extends Controller
{
    public function addMember(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);
        // Check if the authenticated user is the project owner
        if ($project->user_id == auth()->id()) {
            // Validate member data
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            // Add a member to the project
            $member = new ProjectMember([
                'user_id' => $request->input('user_id'),
                'project_id' => $project->id,
            ]);
            $member->save();

            return response()->json(['success' => true, 'message' => 'User successfully added']);
        } else {
            // Handle unauthorized access
            abort(403, 'Unauthorized access');
        }
    }

}
