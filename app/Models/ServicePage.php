<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePage extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $casts = [
        'fields' => 'array', // Laravel নিজেই JSON থেকে array বানিয়ে ফেলবে
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function fields()
    {
        return $this->hasMany(ServicePageField::class);
    }

    public function buttons()
    {
        return $this->hasMany(ServicePageButton::class);
    }

    public function selections()
    {
        return $this->hasMany(ServicePageSelection::class);
    }
}