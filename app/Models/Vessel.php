<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vessel extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'contact_number',
    ];

    /**
     * Get the trips for the vessel.
     */
    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Get activity configuration for this model.
     *
     * @return array
     */
    protected function getActivityConfig(): array
    {
        return [
            'model_name' => 'Vessel',
            'identifier_field' => 'name',
            'field_mappings' => [
                'name' => 'name',
                'contact_number' => 'contact number',
            ],
        ];
    }
}
