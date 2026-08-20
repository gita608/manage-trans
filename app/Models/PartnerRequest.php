<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class PartnerRequest extends Model
{
    use LogsActivity;

    /**
     * Submission method constants
     */
    const METHOD_MANUAL = 'manual';
    const METHOD_IMAGE = 'image';

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_DECLINED = 'declined';
    const STATUS_WITHDRAWN = 'withdrawn';

    /**
     * Extraction status constants
     */
    const EXTRACTION_PENDING = 'pending';
    const EXTRACTION_PROCESSING = 'processing';
    const EXTRACTION_COMPLETED = 'completed';
    const EXTRACTION_FAILED = 'failed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'request_reference',
        'partner_id',
        'partner_user_id',
        'submission_method',
        'status',
        'source_image_path',
        'extraction_status',
        'submitted_at',
        'partner_updated_at',
        'approved_at',
        'approved_by',
        'declined_at',
        'declined_by',
        'decline_reason',
        'withdrawn_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'partner_updated_at' => 'datetime',
            'approved_at' => 'datetime',
            'declined_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($request) {
            if (empty($request->request_reference)) {
                $request->request_reference = sprintf('REQ-%06d', $request->id);
                $request->saveQuietly();
            }
        });
    }

    /**
     * Get the partner that owns the request.
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the partner user that created the request.
     */
    public function partnerUser()
    {
        return $this->belongsTo(PartnerUser::class);
    }

    /**
     * Get the items for this request.
     */
    public function items()
    {
        return $this->hasMany(PartnerRequestItem::class);
    }

    /**
     * Get the trips created from this request.
     */
    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Get the user who approved the request.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who declined the request.
     */
    public function declinedBy()
    {
        return $this->belongsTo(User::class, 'declined_by');
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the request is declined.
     */
    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    /**
     * Check if the request is withdrawn.
     */
    public function isWithdrawn(): bool
    {
        return $this->status === self::STATUS_WITHDRAWN;
    }

    /**
     * Check if the partner can edit this request.
     */
    public function canPartnerEdit(): bool
    {
        return $this->isPending();
    }

    /**
     * Get activity configuration for this model.
     *
     * @return array
     */
    protected function getActivityConfig(): array
    {
        return [
            'model_name' => 'Partner Request',
            'identifier_field' => 'request_reference',
            'field_mappings' => [
                'request_reference' => 'reference',
                'status' => 'status',
                'submission_method' => 'submission method',
            ],
        ];
    }
}
