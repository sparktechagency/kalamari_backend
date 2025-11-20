<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Replay extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    // কোন কমেন্টের রিপ্লাই
    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // // (Optional) যদি রিপ্লাই এর লাইক থাকে তাহলে
    // public function likes()
    // {
    //     return $this->hasMany(Like::class, 'post_comment_replay_id');
    // }
}
