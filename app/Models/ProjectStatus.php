<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'project_id', 'user_id'];

    public function tasks() {
        return $this->hasMany(Task::class, 'status_id', 'id');
    }
}
