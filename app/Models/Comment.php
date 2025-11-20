<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    // Comment.php
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // কোন পোস্টের কমেন্ট
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    // কমেন্ট এর অনেক রিপ্লাই
    public function replies()
    {
        return $this->hasMany(Replay::class, 'comment_id');
    }
}
