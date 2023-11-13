<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone_number',
        'bio',
        'profile_photo',
        'address',
        'birthday'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
