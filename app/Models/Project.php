<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'user_id'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function members() {
        return $this->hasMany(ProjectMember::class);
    }

    public function tasks() {
        return $this->hasMany(Task::class);
    }

    public function statuses() {
        return $this->hasMany(ProjectStatus::class);
    }

}
