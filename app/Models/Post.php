<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // App\Models\Post.php
    public function scopeApproved($query)
    {
        return $query->where('post_status', 'approved');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }
}
