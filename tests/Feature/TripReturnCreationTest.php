<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Notification;
use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\PartnerRequestItem;
use App\Models\PartnerUser;
use App\Models\Permission;
use App\Models\Trip;
use App\Models\TripCrew;
use App\Models\User;
use App\Models\Vessel;
use App\Services\FirebaseNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

/**
 * Optional Return Trip creation during Admin Trip store.
 */
class TripReturnCreationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
        $this->mockFirebase();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function mockFirebase(): void
    {
        $this->mock(FirebaseNotificationService::class, function ($mock) {
            $mock->shouldReceive('sendToDriver')->andReturn(true);
            $mock->shouldReceive('sendPushNotification')->andReturn(true);
        });
    }

    public function test_checkbox_absent_creates_no_return_trip(): void
    {
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        $vessel = $this->createVessel();

        $this->actingAs($staff)->post(route('trips.store'), [
            'crews' => [$this->crewPayload($vessel->id, $driver->id)],
        ])->assertRedirect(route('trips.index'));

        $this->assertSame(1, Trip::count());
    }

    public function test_checkbox_false_creates_no_return_trip(): void
    {
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A']);
        $vessel = $this->createVessel();

        $this->actingAs($staff)->post(route('trips.store'), [
            'create_return_trip' => '0',
            'crews' => [$this->crewPayload($vessel->id, $driver->id)],
        ])->assertRedirect(route('trips.index'));

        $this->assertSame(1, Trip::count());
    }

    public function test_checkbox_selected_creates_outbound_and_return(): void
    {
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A']);
        $vessel = $this->createVessel();

        $this->actingAs($staff)->post(route('trips.store'), [
            'create_return_trip' => '1',
            'crews' => [$this->crewPayload($vessel->id, $driver->id)],
        ])->assertRedirect(route('trips.index'));

        $this->assertSame(2, Trip::count());
    }

    public function test_return_has_null_driver_id(): void
    {
        [$outbound, $return] = $this->createOutboundAndReturn();

        $this->assertNotNull($outbound->driver_id);
        $this->assertNull($return->driver_id);
    }

    public function test_return_status_is_unassigned(): void
    {
        [, $return] = $this->createOutboundAndReturn();

        $this->assertSame(TripCrew::STATUS_UNASSIGNED, $return->status);
    }

    public function test_outbound_driver_remains_assigned(): void
    {
        [$outbound] = $this->createOutboundAndReturn();

        $this->assertSame(TripCrew::STATUS_ASSIGNED, $outbound->status);
        $this->assertNotNull($outbound->driver_id);
    }

    public function test_return_from_to_are_reversed(): void
    {
        [, $return] = $this->createOutboundAndReturn([
            'from_location' => 'Port Rashid',
            'to_location' => 'Dubai Airport',
        ]);

        $crew = $return->crews()->first();
        $this->assertSame('Dubai Airport', $crew->from_location);
        $this->assertSame('Port Rashid', $crew->to_location);
    }

    public function test_outbound_route_remains_unchanged(): void
    {
        [$outbound] = $this->createOutboundAndReturn([
            'from_location' => 'Port Rashid',
            'to_location' => 'Dubai Airport',
        ]);

        $crew = $outbound->crews()->first();
        $this->assertSame('Port Rashid', $crew->from_location);
        $this->assertSame('Dubai Airport', $crew->to_location);
    }

    public function test_crew_name_copied_to_return(): void
    {
        [, $return] = $this->createOutboundAndReturn(['name' => 'Mohammed']);

        $this->assertSame('Mohammed', $return->crews()->first()->name);
    }

    public function test_primary_phone_copied_to_return(): void
    {
        [, $return] = $this->createOutboundAndReturn(['phone' => '0501234567']);

        $this->assertSame('0501234567', $return->crews()->first()->phone);
    }

    public function test_secondary_phone_copied_to_return(): void
    {
        [, $return] = $this->createOutboundAndReturn(['phone_2' => '0509876543']);

        $this->assertSame('0509876543', $return->crews()->first()->phone_2);
    }

    public function test_address_copied_to_return(): void
    {
        [, $return] = $this->createOutboundAndReturn(['address' => 'Al Mina Street']);

        $this->assertSame('Al Mina Street', $return->crews()->first()->address);
    }

    public function test_vessel_copied_to_return(): void
    {
        $vessel = $this->createVessel('Ever Given');
        [, $return] = $this->createOutboundAndReturn([], $vessel);

        $this->assertSame($vessel->id, $return->crews()->first()->vessel_id);
    }

    public function test_pickup_time_copied_to_return(): void
    {
        [, $return] = $this->createOutboundAndReturn(['pick_up_time' => '14:30']);

        $this->assertSame('14:30', $return->crews()->first()->pick_up_time);
    }

    public function test_flight_number_copied_to_return(): void
    {
        [, $return] = $this->createOutboundAndReturn(['flight_number' => 'EK202']);

        $this->assertSame('EK202', $return->crews()->first()->flight_number);
    }

    public function test_remarks_copied_to_return(): void
    {
        [, $return] = $this->createOutboundAndReturn(['remarks' => 'Late arrival']);

        $this->assertSame('Late arrival', $return->crews()->first()->remarks);
    }

    public function test_sub_remark_copied_to_return(): void
    {
        [, $return] = $this->createOutboundAndReturn(['sub_remark' => 'Gate change']);

        $this->assertSame('Gate change', $return->crews()->first()->sub_remark);
    }

    public function test_same_trip_date_copied_to_return(): void
    {
        [$outbound, $return] = $this->createOutboundAndReturn(['trip_date' => '2026-08-31']);

        $this->assertSame('2026-08-31', $outbound->trip_date->format('Y-m-d'));
        $this->assertSame('2026-08-31', $return->trip_date->format('Y-m-d'));
    }

    public function test_multiple_crews_stay_together_on_return(): void
    {
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver 1']);
        $vessel = $this->createVessel();

        $this->actingAs($staff)->post(route('trips.store'), [
            'create_return_trip' => '1',
            'crews' => [
                $this->crewPayload($vessel->id, $driver->id, ['name' => 'Crew A']),
                $this->crewPayload($vessel->id, $driver->id, ['name' => 'Crew B']),
            ],
        ]);

        $this->assertSame(2, Trip::count());
        $return = Trip::whereNull('driver_id')->firstOrFail();
        $this->assertCount(2, $return->crews);
        $this->assertEqualsCanonicalizing(['Crew A', 'Crew B'], $return->crews->pluck('name')->all());
    }

    public function test_multiple_outbound_groups_each_get_own_return_trip(): void
    {
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver1 = Driver::create(['name' => 'Driver 1']);
        $driver2 = Driver::create(['name' => 'Driver 2']);
        $vessel = $this->createVessel();

        $this->actingAs($staff)->post(route('trips.store'), [
            'create_return_trip' => '1',
            'crews' => [
                $this->crewPayload($vessel->id, $driver1->id, ['name' => 'Crew A', 'trip_date' => '2026-08-31']),
                $this->crewPayload($vessel->id, $driver1->id, ['name' => 'Crew B', 'trip_date' => '2026-08-31']),
                $this->crewPayload($vessel->id, $driver2->id, ['name' => 'Crew C', 'trip_date' => '2026-08-31']),
                $this->crewPayload($vessel->id, $driver2->id, ['name' => 'Crew D', 'trip_date' => '2026-09-01']),
            ],
        ]);

        $this->assertSame(6, Trip::count());
        $this->assertSame(3, Trip::whereNotNull('driver_id')->count());
        $this->assertSame(3, Trip::whereNull('driver_id')->count());

        $returnWithTwoCrews = Trip::whereNull('driver_id')
            ->whereDate('trip_date', '2026-08-31')
            ->get()
            ->filter(fn ($trip) => $trip->crews()->count() === 2);

        $this->assertCount(1, $returnWithTwoCrews);
    }

    public function test_unassigned_return_trips_are_not_merged(): void
    {
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver1 = Driver::create(['name' => 'Driver 1']);
        $driver2 = Driver::create(['name' => 'Driver 2']);
        $vessel = $this->createVessel();

        $this->actingAs($staff)->post(route('trips.store'), [
            'create_return_trip' => '1',
            'crews' => [
                $this->crewPayload($vessel->id, $driver1->id, ['name' => 'Crew A', 'trip_date' => '2026-08-31']),
                $this->crewPayload($vessel->id, $driver2->id, ['name' => 'Crew B', 'trip_date' => '2026-08-31']),
            ],
        ]);

        $returns = Trip::whereNull('driver_id')->whereDate('trip_date', '2026-08-31')->get();
        $this->assertCount(2, $returns);
        $this->assertNotSame($returns[0]->id, $returns[1]->id);
    }

    public function test_partner_copied_to_return(): void
    {
        $partner = Partner::create(['title' => 'ABC Shipping']);
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A']);
        $vessel = $this->createVessel();

        $this->actingAs($staff)->post(route('trips.store'), [
            'create_return_trip' => '1',
            'partner_id' => $partner->id,
            'crews' => [$this->crewPayload($vessel->id, $driver->id)],
        ]);

        [, $return] = $this->orderedTrips();
        $this->assertSame($partner->id, $return->partner_id);
    }

    public function test_partner_request_id_copied_to_return(): void
    {
        [$partner, $partnerUser, $request] = $this->createApprovedPartnerRequest();
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A']);
        $vessel = $this->createVessel();
        $item = $request->items->first();

        $this->actingAs($staff)->post(route('trips.store'), [
            'create_return_trip' => '1',
            'partner_request_id' => $request->id,
            'partner_id' => $partner->id,
            'crews' => [$this->tripCrewPayloadFromItem($item, $driver->id, $vessel->id)],
        ]);

        $trips = Trip::where('partner_request_id', $request->id)->get();
        $this->assertCount(2, $trips);
        $this->assertTrue($trips->every(fn ($trip) => $trip->partner_request_id === $request->id));
    }

    public function test_partner_request_processing_unchecked_creates_only_outbound(): void
    {
        [$partner, , $request] = $this->createApprovedPartnerRequest();
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A']);
        $vessel = $this->createVessel();
        $item = $request->items->first();

        $this->actingAs($staff)->post(route('trips.store'), [
            'partner_request_id' => $request->id,
            'partner_id' => $partner->id,
            'crews' => [$this->tripCrewPayloadFromItem($item, $driver->id, $vessel->id)],
        ]);

        $this->assertSame(1, Trip::where('partner_request_id', $request->id)->count());
    }

    public function test_partner_request_processing_checked_creates_outbound_and_return(): void
    {
        [$partner, , $request] = $this->createApprovedPartnerRequest();
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A']);
        $vessel = $this->createVessel();
        $item = $request->items->first();

        $this->actingAs($staff)->post(route('trips.store'), [
            'create_return_trip' => '1',
            'partner_request_id' => $request->id,
            'partner_id' => $partner->id,
            'crews' => [$this->tripCrewPayloadFromItem($item, $driver->id, $vessel->id)],
        ]);

        $this->assertSame(2, Trip::where('partner_request_id', $request->id)->count());
    }

    public function test_partner_request_duplicate_conversion_protection_still_works(): void
    {
        [$partner, , $request] = $this->createApprovedPartnerRequest();
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A']);
        $vessel = $this->createVessel();
        $item = $request->items->first();
        $payload = [
            'create_return_trip' => '1',
            'partner_request_id' => $request->id,
            'partner_id' => $partner->id,
            'crews' => [$this->tripCrewPayloadFromItem($item, $driver->id, $vessel->id)],
        ];

        $this->actingAs($staff)->post(route('trips.store'), $payload);
        $this->actingAs($staff)->post(route('trips.store'), $payload)
            ->assertRedirect(route('partner-requests.show', $request))
            ->assertSessionHas('error');

        $this->assertSame(2, Trip::where('partner_request_id', $request->id)->count());
    }

    public function test_return_gets_own_trip_reference(): void
    {
        [$outbound, $return] = $this->createOutboundAndReturn();

        $this->assertNotNull($outbound->trip_reference);
        $this->assertNotNull($return->trip_reference);
        $this->assertNotSame($outbound->trip_reference, $return->trip_reference);
    }

    public function test_return_gets_normal_generated_title(): void
    {
        [, $return] = $this->createOutboundAndReturn();

        $this->assertMatchesRegularExpression('/^Trip \d+$/', $return->title);
    }

    public function test_assigned_outbound_sends_assignment_notification(): void
    {
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        $vessel = $this->createVessel();

        $this->actingAs($staff)->post(route('trips.store'), [
            'create_return_trip' => '1',
            'crews' => [$this->crewPayload($vessel->id, $driver->id)],
        ]);

        $this->assertSame(1, Notification::where('driver_id', $driver->id)->count());
        $this->assertSame('New Trip Assigned', Notification::where('driver_id', $driver->id)->value('title'));
    }

    public function test_return_sends_no_notification_at_creation(): void
    {
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        $vessel = $this->createVessel();

        $this->actingAs($staff)->post(route('trips.store'), [
            'create_return_trip' => '1',
            'crews' => [$this->crewPayload($vessel->id, $driver->id)],
        ]);

        $this->assertSame(1, Notification::count());
    }

    public function test_return_creation_failure_rolls_back_outbound(): void
    {
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A']);
        $vessel = $this->createVessel();
        $attempts = 0;

        TripCrew::creating(function () use (&$attempts) {
            $attempts++;
            if ($attempts === 2) {
                throw new \RuntimeException('Simulated return crew failure');
            }
        });

        $this->actingAs($staff)->post(route('trips.store'), [
            'create_return_trip' => '1',
            'crews' => [$this->crewPayload($vessel->id, $driver->id)],
        ]);

        $this->assertSame(0, Trip::count());
    }

    public function test_return_appears_as_unassigned_in_trips_list(): void
    {
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A']);
        $vessel = $this->createVessel();
        $date = today()->format('Y-m-d');

        $this->actingAs($staff)->post(route('trips.store'), [
            'create_return_trip' => '1',
            'crews' => [$this->crewPayload($vessel->id, $driver->id, ['trip_date' => $date])],
        ]);

        [, $return] = $this->orderedTrips();

        $response = $this->actingAs($staff)->get(route('trips.index'));
        $response->assertOk();
        $response->assertSee($return->trip_reference, false);
        $response->assertSee('Unassigned', false);
    }

    public function test_assign_driver_workflow_can_assign_return_later(): void
    {
        $staff = $this->createStaff(['create_trips', 'view_trips', 'edit_trips']);
        $outboundDriver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        $returnDriver = Driver::create(['name' => 'Driver B', 'notification_token' => 'token-b']);
        $vessel = $this->createVessel();

        $this->actingAs($staff)->post(route('trips.store'), [
            'create_return_trip' => '1',
            'crews' => [$this->crewPayload($vessel->id, $outboundDriver->id)],
        ]);

        [, $return] = $this->orderedTrips();

        $this->actingAs($staff)->patch(route('trips.assign-driver', $return), [
            'driver_id' => $returnDriver->id,
        ]);

        $return->refresh();
        $this->assertSame($returnDriver->id, $return->driver_id);
        $this->assertSame(TripCrew::STATUS_ASSIGNED, $return->status);
        $this->assertSame(1, Notification::where('driver_id', $returnDriver->id)->count());
        $this->assertSame('New Trip Assigned', Notification::where('driver_id', $returnDriver->id)->value('title'));
    }

    public function test_universal_search_finds_generated_return_trip(): void
    {
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A']);
        $vessel = $this->createVessel();
        $date = today()->format('Y-m-d');

        $this->actingAs($staff)->post(route('trips.store'), [
            'create_return_trip' => '1',
            'crews' => [$this->crewPayload($vessel->id, $driver->id, [
                'trip_date' => $date,
                'from_location' => 'Port Rashid',
                'to_location' => 'Dubai Airport',
            ])],
        ]);

        [, $return] = $this->orderedTrips();

        $response = $this->actingAs($staff)->get(route('trips.index', [
            'search' => 'Dubai Airport',
        ]));

        $response->assertOk();
        $response->assertSee($return->trip_reference, false);
    }

    /**
     * @return array{0: Trip, 1: Trip}
     */
    protected function createOutboundAndReturn(array $crewOverrides = [], ?Vessel $vessel = null): array
    {
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        $vessel ??= $this->createVessel();

        $this->actingAs($staff)->post(route('trips.store'), [
            'create_return_trip' => '1',
            'crews' => [$this->crewPayload($vessel->id, $driver->id, $crewOverrides)],
        ]);

        return $this->orderedTrips();
    }

    /**
     * @return array{0: Trip, 1: Trip}
     */
    protected function orderedTrips(): array
    {
        $trips = Trip::orderBy('id')->get();

        return [$trips[0], $trips[1]];
    }

    protected function crewPayload(int $vesselId, ?int $driverId, array $overrides = []): array
    {
        return array_merge([
            'driver_id' => $driverId,
            'trip_date' => '2026-08-30',
            'vessel_id' => $vesselId,
            'pick_up_time' => '09:00',
            'from_location' => 'Port Rashid',
            'to_location' => 'Dubai Airport',
            'name' => 'Mohammed',
            'phone' => '0501234567',
            'phone_2' => '0509876543',
            'address' => 'Al Mina Street',
            'flight_number' => 'EK202',
            'remarks' => 'Late arrival',
            'sub_remark' => 'Gate change',
        ], $overrides);
    }

    protected function tripCrewPayloadFromItem(PartnerRequestItem $item, ?int $driverId, int $vesselId): array
    {
        return [
            'driver_id' => $driverId,
            'trip_date' => $item->trip_date?->format('Y-m-d') ?? '2026-08-30',
            'vessel_id' => $vesselId,
            'pick_up_time' => '09:00',
            'from_location' => $item->from_location ?? 'A',
            'to_location' => $item->to_location ?? 'B',
            'name' => $item->name ?? 'Crew',
        ];
    }

    /**
     * @return array{0: Partner, 1: PartnerUser, 2: PartnerRequest}
     */
    protected function createApprovedPartnerRequest(): array
    {
        $partner = Partner::create(['title' => 'Partner A']);
        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'Partner User',
            'email' => 'partner@example.com',
            'password' => Hash::make('password'),
        ]);
        $vessel = $this->createVessel();
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);
        PartnerRequestItem::create([
            'partner_request_id' => $request->id,
            'trip_date' => '2026-08-30',
            'name' => 'Crew From Request',
            'from_location' => 'Port Rashid',
            'to_location' => 'Dubai Airport',
            'vessel_id' => $vessel->id,
        ]);

        return [$partner, $partnerUser, $request->fresh('items')];
    }

    protected function createVessel(string $name = 'Vessel A'): Vessel
    {
        return Vessel::withoutEvents(fn () => Vessel::create(['name' => $name]));
    }

    protected function createStaff(array $permissions = []): User
    {
        static $counter = 0;
        $counter++;

        $user = User::create([
            'name' => 'Staff User '.$counter,
            'email' => 'staff'.$counter.'@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_STAFF,
        ]);

        foreach ($permissions as $permission) {
            $user->grantPermission($permission);
        }

        return $user;
    }

    protected function createSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'personal_access_tokens', 'trip_expenses', 'trip_expense_types',
            'trip_issues', 'trip_issue_types', 'trip_crew_removals', 'trip_crews', 'trips',
            'partner_request_items', 'partner_requests', 'partner_users',
            'partners', 'vessels', 'drivers', 'activity_logs',
            'user_permissions', 'role_permissions', 'permissions',
            'notifications', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('category');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('role');
            $table->foreignId('permission_id');
            $table->timestamps();
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('permission_id');
            $table->boolean('granted')->default(true);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->string('type')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('role')->default(2);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('type')->default(1);
            $table->string('photo')->nullable();
            $table->string('notification_token')->nullable();
            $table->timestamps();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('partner_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('partner_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_reference')->nullable();
            $table->foreignId('partner_id');
            $table->foreignId('partner_user_id')->nullable();
            $table->string('submission_method');
            $table->string('status');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('partner_request_items', function (Blueprint $table) {
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
            $table->foreignId('driver_id')->nullable();
            $table->foreignId('vessel_id')->nullable();
            $table->timestamps();
        });

        Schema::create('vessels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('trips', function (Blueprint $table) {
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

        Schema::create('trip_crews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id');
            $table->string('name')->nullable();
            $table->foreignId('vessel_id')->nullable();
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

        Schema::create('trip_crew_removals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id');
            $table->unsignedBigInteger('trip_crew_id')->nullable();
            $table->string('crew_name');
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
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

        foreach (['view_trips', 'create_trips', 'edit_trips'] as $name) {
            Permission::create([
                'name' => $name,
                'display_name' => $name,
                'category' => 'trips',
            ]);
        }
    }
}
