<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Presentation extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic',
        'background',
        'color',
        'num_of_slides',
        'slides'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
