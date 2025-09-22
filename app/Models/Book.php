<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

class Book extends Model
{
    use HasFactory;
    
    protected $fillable = ['isbn', 'title', 'author'];

    protected $casts = [
        'isbn' => 'integer',
        'title' => 'string',
        'author' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
