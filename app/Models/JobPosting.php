<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPosting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['title', 'department', 'location', 'description', 'apply_url', 'is_open'];

    protected $casts = ['is_open' => 'boolean'];
}
