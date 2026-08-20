<?php

namespace Tests\Unit;

use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\PartnerRequestItem;
use App\Models\Trip;
use App\Models\User;
use App\Support\PartnerRequestReviewVersion;
use App\Services\PartnerRequestApprovalService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PartnerRequestApprovalServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('role')->default(1);
            $table->timestamps();
        });

        Schema::create('partners', function ($table) {
            $table->id();
            $table->string('title');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('partner_requests', function ($table) {
            $table->id();
            $table->string('request_reference')->nullable();
            $table->foreignId('partner_id');
            $table->string('submission_method');
            $table->string('status');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamps();
        });

        Schema::create('partner_request_items', function ($table) {
            $table->id();
            $table->foreignId('partner_request_id');
            $table->date('trip_date')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('trips', function ($table) {
            $table->id();
            $table->string('trip_reference')->nullable();
            $table->foreignId('partner_request_id')->nullable();
            $table->foreignId('partner_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->date('trip_date')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->default('unassigned');
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

    public function test_approval_sets_status_without_creating_trips(): void
    {
        $partner = Partner::withoutEvents(fn () => Partner::create(['title' => 'Test Partner']));
        $user = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'secret', 'role' => 1]);

        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_PENDING,
        ]);

        PartnerRequestItem::create([
            'partner_request_id' => $request->id,
            'trip_date' => '2026-08-21',
            'name' => 'Crew 1',
        ]);

        $service = new PartnerRequestApprovalService();
        $result = $service->approve(
            $request->fresh('items'),
            $user->id,
            PartnerRequestReviewVersion::make($request->fresh('items'))
        );

        $this->assertTrue($result['success']);
        $this->assertSame(PartnerRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertSame(0, Trip::count());
    }

    public function test_approval_succeeds_with_incomplete_operational_fields(): void
    {
        $partner = Partner::withoutEvents(fn () => Partner::create(['title' => 'Test Partner']));
        $user = User::create(['name' => 'Admin', 'email' => 'admin2@test.com', 'password' => 'secret', 'role' => 1]);

        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_PENDING,
        ]);

        PartnerRequestItem::create([
            'partner_request_id' => $request->id,
            'trip_date' => '2026-08-21',
            'name' => 'Only Name',
        ]);

        $service = new PartnerRequestApprovalService();
        $result = $service->approve(
            $request->fresh('items'),
            $user->id,
            PartnerRequestReviewVersion::make($request->fresh('items'))
        );

        $this->assertTrue($result['success']);
        $this->assertSame(0, Trip::count());
    }
}
