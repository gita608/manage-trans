<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
