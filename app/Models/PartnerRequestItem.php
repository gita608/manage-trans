<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class PartnerRequestItem extends Model
{
    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'partner_request_id',
        'trip_date',
        'pick_up_time',
        'name',
        'phone',
        'phone_2',
        'address',
        'from_location',
        'to_location',
        'flight_number',
        'remarks',
        'sub_remark',
        'vessel_name_raw',
        'driver_id',
        'vessel_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trip_date' => 'date',
        ];
    }

    /**
     * Get the partner request that owns this item.
     */
    public function request()
    {
        return $this->belongsTo(PartnerRequest::class, 'partner_request_id');
    }

    /**
     * Get the driver assigned to this item (internal review field).
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the vessel selected for this item.
     * Partners may set vessel_id on manual requests; Manage Trans may also adjust it during review.
     */
    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Get activity configuration for this model.
     *
     * @return array
     */
    protected function getActivityConfig(): array
    {
        return [
            'model_name' => 'Partner Request Item',
            'identifier_field' => 'name',
            'field_mappings' => [
                'name' => 'crew name',
                'trip_date' => 'trip date',
                'pick_up_time' => 'pickup time',
            ],
        ];
    }
}
