<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Testimonial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['quote', 'author_name', 'author_company', 'rating', 'source', 'is_featured'];

    protected $casts = ['is_featured' => 'boolean'];
}
