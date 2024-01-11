<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id', 'old_status_id', 'new_status_id', 'user_id',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function oldStatus()
    {
        return $this->belongsTo(ProjectStatus::class, 'old_status_id');
    }

    public function newStatus()
    {
        return $this->belongsTo(ProjectStatus::class, 'new_status_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function status()
    {
        return $this->belongsTo(ProjectStatus::class);
    }
}
