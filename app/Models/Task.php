<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'project_id', 'user_id', 'status_id', 'index', 'priority'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function users() {
        return $this->belongsToMany(User::class);
    }

    public function project() {
        return $this->belongsTo(Project::class);
    }

    public function comments() {
        return $this->hasMany(TaskComment::class);
    }

    public function status() {
        return $this->belongsTo(ProjectStatus::class);
    }

   /*  public function statusHistory()
    {
        return $this->hasMany(TaskStatusHistory::class)->with('status')->orderBy('created_at', 'desc');
    } */

    public function statusHistory()
    {
        return $this->hasMany(TaskStatusHistory::class)->with('oldStatus', 'newStatus', 'user')->orderBy('created_at', 'desc');
    }
}
