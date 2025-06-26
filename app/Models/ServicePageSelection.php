<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePageSelection extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function servicePage()
    {
        return $this->belongsTo(ServicePage::class);
    }

    public function items()
    {
        return $this->hasMany(ServicePageSelectionItem::class);
    }
}
