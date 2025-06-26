<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePageButtonModal extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $casts = [
        'fields' => 'array',
    ];

    public function button()
    {
        return $this->belongsTo(ServicePageButton::class, 'service_page_button_id');
    }
}
