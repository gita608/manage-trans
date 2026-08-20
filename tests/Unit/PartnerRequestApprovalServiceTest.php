<?php

namespace Tests\Unit;

use App\Models\Driver;
use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\PartnerRequestItem;
use App\Models\Trip;
use App\Models\TripCrew;
use App\Models\User;
use App\Models\Vessel;
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
            $table->time('pick_up_time')->nullable();
            $table->string('name')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->foreignId('vessel_id')->nullable();
            $table->timestamps();
        });

        Schema::create('drivers', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('vessels', function ($table) {
            $table->id();
            $table->string('name');
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

        Schema::create('trip_crews', function ($table) {
            $table->id();
            $table->foreignId('trip_id');
            $table->foreignId('vessel_id')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_2')->nullable();
            $table->string('address')->nullable();
            $table->time('pick_up_time')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->string('flight_number')->nullable();
            $table->text('remarks')->nullable();
            $table->string('sub_remark')->nullable();
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

    public function test_group_items_by_driver_and_date(): void
    {
        $driverA = Driver::withoutEvents(fn () => Driver::create(['name' => 'Driver A']));
        $driverB = Driver::withoutEvents(fn () => Driver::create(['name' => 'Driver B']));

        $items = collect([
            new PartnerRequestItem(['driver_id' => $driverA->id, 'trip_date' => '2026-08-21']),
            new PartnerRequestItem(['driver_id' => $driverA->id, 'trip_date' => '2026-08-21']),
            new PartnerRequestItem(['driver_id' => $driverB->id, 'trip_date' => '2026-08-22']),
            new PartnerRequestItem(['driver_id' => null, 'trip_date' => '2026-08-21']),
        ]);

        $service = new PartnerRequestApprovalService();
        $groups = $service->groupItemsByDriverAndDate($items);

        $this->assertCount(3, $groups);
    }

    public function test_validate_items_for_approval_requires_operational_fields(): void
    {
        $service = new PartnerRequestApprovalService();
        $errors = $service->validateItemsForApproval(collect([
            new PartnerRequestItem([
                'trip_date' => null,
                'pick_up_time' => null,
                'name' => '',
                'from_location' => '',
                'to_location' => '',
                'vessel_id' => null,
            ]),
        ]));

        $this->assertNotEmpty($errors);
    }

    public function test_approval_creates_trips_grouped_by_driver_and_date(): void
    {
        $partner = Partner::withoutEvents(fn () => Partner::create(['title' => 'Test Partner']));
        $driverA = Driver::withoutEvents(fn () => Driver::create(['name' => 'Driver A']));
        $driverB = Driver::withoutEvents(fn () => Driver::create(['name' => 'Driver B']));
        $vessel = Vessel::withoutEvents(fn () => Vessel::create(['name' => 'Vessel 1']));
        $user = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'secret', 'role' => 1]);

        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_PENDING,
        ]);

        foreach ([
            [$driverA->id, '2026-08-21', 'Crew 1'],
            [$driverA->id, '2026-08-21', 'Crew 2'],
            [$driverB->id, '2026-08-22', 'Crew 3'],
            [$driverB->id, '2026-08-22', 'Crew 4'],
        ] as [$driverId, $date, $name]) {
            PartnerRequestItem::create([
                'partner_request_id' => $request->id,
                'driver_id' => $driverId,
                'trip_date' => $date,
                'pick_up_time' => '09:00:00',
                'name' => $name,
                'from_location' => 'A',
                'to_location' => 'B',
                'vessel_id' => $vessel->id,
            ]);
        }

        $service = new PartnerRequestApprovalService();
        $result = $service->approve($request->fresh('items'), $user->id, PartnerRequestReviewVersion::make($request->fresh('items')));

        $this->assertTrue($result['success']);
        $this->assertCount(2, Trip::all());
        $this->assertSame(PartnerRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertSame(4, TripCrew::count());
        $this->assertTrue(Trip::where('partner_request_id', $request->id)->count() === 2);
    }
}
