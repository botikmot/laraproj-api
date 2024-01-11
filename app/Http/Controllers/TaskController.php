<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\TaskComment;

class TaskController extends Controller
{
    public function updateTask(Request $request, $taskId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status_id' => 'required|exists:project_statuses,id', // Assuming you have a 'statuses' table
        ]);

        $task = Task::find($taskId);
        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }
        // Update task fields
        $task->name = $request->input('name');
        $task->status_id = $request->input('status_id');
        $task->index = $request->input('index');
        // Update other fields as needed
        $task->save();

        $tasksData = $request->input('tasks');
        foreach($tasksData as $data){
            $taskData = Task::find($data['id']);
            $taskData->index = $data['index'];
            $taskData->save();
        }

        return response()->json(['message' => 'Task updated successfully']);
    }

    public function removeTask($id)
    {
        $projectTask = Task::find($id);
        if ($projectTask) {
            $projectTask->delete();
            return response()->json(['success' => true, 'message' => 'Task successfully removed']);
        } else {
            // Handle the case where the status with the given ID is not found.
            return response()->json(['success' => false, 'message' => 'Task not found']);
        }
    }

    public function saveComment(Request $request, $taskId)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $task = Task::find($taskId);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $comment = new TaskComment([
            'content' => $request->input('content'),
            'user_id' => auth()->id(),
        ]);

        $task->comments()->save($comment);

        return response()->json(['success' => true, 'message' => 'Comment saved successfully', 'comment' => $comment->load('user.profile')]);
    }

    public function assignedUsers(Request $request, $taskId)
    {
        $task = Task::find($taskId);
        $userIds = $request->input('user_ids');

        if(count($userIds) > 0) {
            $task->users()->attach($userIds);
            return response()->json(['success' => true, 'message' => 'Users assigned successfully', 'task' => $task->load('users.profile') ]);
        }else{
            return response()->json(['success' => false,'message' => 'User id not found'], 404);
        }
    }


}
