<?php

namespace Tests\Unit;

use App\Models\PartnerRequest;
use App\Models\PartnerRequestItem;
use App\Support\PartnerRequestReviewVersion;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PartnerRequestReviewVersionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('partner_requests', function ($table) {
            $table->id();
            $table->string('request_reference')->nullable();
            $table->string('status');
            $table->timestamp('partner_updated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('partner_request_items', function ($table) {
            $table->id();
            $table->foreignId('partner_request_id');
            $table->date('trip_date')->nullable();
            $table->time('pick_up_time')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_2')->nullable();
            $table->string('address')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->string('flight_number')->nullable();
            $table->text('remarks')->nullable();
            $table->string('sub_remark')->nullable();
            $table->string('vessel_name_raw')->nullable();
            $table->foreignId('vessel_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function ($table) {
            $table->id();
            $table->morphs('loggable');
            $table->string('action');
            $table->foreignId('user_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function test_fingerprint_is_stable_for_unchanged_state(): void
    {
        $request = PartnerRequest::create([
            'status' => PartnerRequest::STATUS_PENDING,
            'partner_updated_at' => now(),
        ]);

        PartnerRequestItem::create([
            'partner_request_id' => $request->id,
            'trip_date' => '2026-08-21',
            'pick_up_time' => '09:00:00',
            'name' => 'Crew',
            'from_location' => 'A',
            'to_location' => 'B',
        ]);

        $first = PartnerRequestReviewVersion::make($request->fresh('items'));
        $second = PartnerRequestReviewVersion::make($request->fresh('items'));

        $this->assertSame($first, $second);
        $this->assertSame(64, strlen($first));
    }

    public function test_fingerprint_changes_when_item_field_changes(): void
    {
        $request = PartnerRequest::create(['status' => PartnerRequest::STATUS_PENDING]);
        $item = PartnerRequestItem::create([
            'partner_request_id' => $request->id,
            'name' => 'Before',
            'from_location' => 'A',
            'to_location' => 'B',
        ]);

        $before = PartnerRequestReviewVersion::make($request->fresh('items'));
        $item->update(['name' => 'After']);
        $after = PartnerRequestReviewVersion::make($request->fresh('items'));

        $this->assertNotSame($before, $after);
    }
}
