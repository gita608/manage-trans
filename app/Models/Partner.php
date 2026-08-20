<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title',
        'is_default',
        'allow_manual_submission',
        'allow_image_submission',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'allow_manual_submission' => 'boolean',
        'allow_image_submission' => 'boolean',
    ];

    /**
     * Get the partner users for the partner.
     */
    public function partnerUsers()
    {
        return $this->hasMany(PartnerUser::class);
    }

    /**
     * Get the partner requests for the partner.
     */
    public function requests()
    {
        return $this->hasMany(PartnerRequest::class);
    }

    /**
     * Get activity configuration for this model.
     *
     * @return array
     */
    protected function getActivityConfig(): array
    {
        return [
            'model_name' => 'Partner',
            'identifier_field' => 'title',
            'field_mappings' => [
                'title' => 'title',
                'is_default' => 'is_default',
                'allow_manual_submission' => 'allow manual submission',
                'allow_image_submission' => 'allow image submission',
            ],
        ];
    }
}
