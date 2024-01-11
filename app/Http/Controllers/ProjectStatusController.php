<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectStatus;

class ProjectStatusController extends Controller
{
    public function removeStatus($id)
    {
        $projectStatus = ProjectStatus::find($id);
        if ($projectStatus) {
            $projectStatus->delete();
            return response()->json(['success' => true, 'message' => 'Status successfully removed']);
        } else {
            // Handle the case where the status with the given ID is not found.
            return response()->json(['success' => false, 'message' => 'Status not found']);
        }
    }
}
