<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function fetchUserProjects()
    {
        $user = Auth::user();
        // Load projects with members relationship
        $projects = $user->projects()->with('members')->orWhereHas('members', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        return response()->json($projects);
    }

    public function store(Request $request)
    {
        // Validate project data
        $request->validate([
            'name' => 'required|string|max:255|unique:projects',
        ]);

        // Create a new project
        $project = new Project([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'user_id' => auth()->id(), // Assign the authenticated user as the project owner
        ]);
        $project->save();

        return response()->json(['message' => 'Project created successfully', 'project' => $project]);
    }

    public function addStatus(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        // Check if the authenticated user is the project owner
        if ($project->user_id == auth()->id()) {
            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            // Add a status to the project
            $status = new ProjectStatus([
                'name' => $request->input('name'),
                'project_id' => $project->id,
                'user_id' => auth()->id(),
            ]);
            $status->save();

            return response()->json(['message' => 'Status created successfully', 'status' => $status]);
        } else {
            // Handle unauthorized access
            abort(403, 'Unauthorized access');
        }
    }

    public function showProject($id)
    {
        // Fetch the project along with its members, statuses, and tasks
        $project = Project::with(['members.user.profile', 'statuses.tasks' => function ($query) {
                        $query->orderBy('index')->with(['users.profile', 'user.profile', 'comments' => function ($query) {
                            $query->with('user.profile'); // Include user relationship
                        }]);                
                    }])->findOrFail($id);
        
        return response()->json($project);
    }


    public function addTask(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        // Check if the authenticated user is a member of the project
        //$isMember = $project->members()->where('user_id', auth()->id())->exists();
        
        //if ($isMember) {
            // Validate task data
            $request->validate([
                'name' => 'required|string|max:255',
                'status_id' => 'required|exists:project_statuses,id',
            ]);

            // Add a task to the project
            $task = new Task([
                'name' => $request->input('name'),
                'status_id' => $request->input('status_id'),
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'index' => $request->input('index'),
                'priority' => $request->input('priority'),
            ]);
            $task->save();

            return response()->json($task->load('user.profile','comments'));
        /* } else {
            // Handle unauthorized access
            abort(403, 'Unauthorized access');
        } */
    }






}
