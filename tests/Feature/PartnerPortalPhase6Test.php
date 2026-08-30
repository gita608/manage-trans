<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Driver;
use App\Models\Notification;
use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\PartnerRequestItem;
use App\Models\PartnerUser;
use App\Models\Permission;
use App\Models\Trip;
use App\Models\TripCrew;
use App\Models\TripExpense;
use App\Models\TripExpenseType;
use App\Models\TripIssue;
use App\Models\TripIssueType;
use App\Models\User;
use App\Models\Vessel;
use App\Services\FirebaseNotificationService;
use App\Services\TripAssignmentNotificationService;
use App\Services\TripLifecyclePresenter;
use App\Support\PartnerRequestReviewVersion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class PartnerPortalPhase6Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
        $this->seedPermissions();
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

    protected function createSchema(): void
    {
        $tables = [
            'personal_access_tokens', 'trip_expenses', 'trip_expense_types',
            'trip_issues', 'trip_issue_types', 'trip_crew_removals', 'trip_crews', 'trips',
            'partner_request_items', 'partner_requests', 'partner_users',
            'partners', 'drivers', 'vessels', 'activity_logs',
            'user_permissions', 'role_permissions', 'permissions', 'notifications', 'users',
        ];

        Schema::disableForeignKeyConstraints();
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('permissions', function ($table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('category');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function ($table) {
            $table->id();
            $table->unsignedTinyInteger('role');
            $table->foreignId('permission_id');
            $table->timestamps();
        });

        Schema::create('user_permissions', function ($table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('permission_id');
            $table->boolean('granted')->default(true);
            $table->timestamps();
        });

        Schema::create('notifications', function ($table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->string('type')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('role')->default(2);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('partners', function ($table) {
            $table->id();
            $table->string('title');
            $table->boolean('is_default')->default(false);
            $table->boolean('allow_manual_submission')->default(true);
            $table->boolean('allow_image_submission')->default(true);
            $table->timestamps();
        });

        Schema::create('partner_users', function ($table) {
            $table->id();
            $table->foreignId('partner_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('partner_requests', function ($table) {
            $table->id();
            $table->string('request_reference')->nullable();
            $table->foreignId('partner_id');
            $table->foreignId('partner_user_id')->nullable();
            $table->string('submission_method');
            $table->string('status');
            $table->string('source_image_path')->nullable();
            $table->string('extraction_status')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('partner_updated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->foreignId('declined_by')->nullable();
            $table->text('decline_reason')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
        });

        Schema::create('partner_request_items', function ($table) {
            $table->id();
            $table->foreignId('partner_request_id');
            $table->date('trip_date')->nullable();
            $table->time('pick_up_time')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->foreignId('vessel_id')->nullable();
            $table->timestamps();
        });

        Schema::create('drivers', function ($table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('type')->default(1);
            $table->string('notification_token')->nullable();
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

        Schema::create('trip_crew_removals', function ($table) {
            $table->id();
            $table->foreignId('trip_id');
            $table->unsignedBigInteger('trip_crew_id')->nullable();
            $table->string('crew_name');
            $table->string('phone')->nullable();
            $table->string('phone_2')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('vessel_id')->nullable();
            $table->string('vessel_name')->nullable();
            $table->time('pick_up_time')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->string('flight_number')->nullable();
            $table->text('remarks')->nullable();
            $table->string('sub_remark')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->string('driver_name')->nullable();
            $table->foreignId('removed_by')->nullable();
            $table->timestamp('removed_at');
            $table->text('removal_remark')->nullable();
            $table->timestamps();
        });

        Schema::create('trip_issue_types', function ($table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('trip_issues', function ($table) {
            $table->id();
            $table->foreignId('trip_id');
            $table->foreignId('driver_id');
            $table->foreignId('issue_type_id');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('trip_expense_types', function ($table) {
            $table->id();
            $table->string('title');
            $table->json('input_types')->nullable();
            $table->timestamps();
        });

        Schema::create('trip_expenses', function ($table) {
            $table->id();
            $table->foreignId('trip_id');
            $table->foreignId('driver_id');
            $table->foreignId('expense_type_id');
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('hours', 8, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('receipt')->nullable();
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

        Schema::create('personal_access_tokens', function ($table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    protected function seedPermissions(): void
    {
        foreach (['view_trips', 'create_trips', 'edit_trips', 'delete_trips', 'view_reports'] as $name) {
            Permission::create([
                'name' => $name,
                'display_name' => $name,
                'category' => 'trips',
            ]);
        }
    }

    protected function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
        ]);
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

    protected function createPartnerContext(): array
    {
        $partner = Partner::create(['title' => 'ZAKHER MARINE']);
        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'John Partner',
            'email' => 'john@partner.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        return [$partner, $partnerUser];
    }

    protected function createVessel(): Vessel
    {
        return Vessel::withoutEvents(fn () => Vessel::create(['name' => 'Vessel A']));
    }

    protected function createPendingRequest(Partner $partner, PartnerUser $partnerUser, ?Driver $driver = null): PartnerRequest
    {
        $vessel = $this->createVessel();
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_PENDING,
            'submitted_at' => now()->subHour(),
        ]);

        PartnerRequestItem::create([
            'partner_request_id' => $request->id,
            'trip_date' => now()->toDateString(),
            'pick_up_time' => '09:00:00',
            'name' => 'Crew Member',
            'from_location' => 'Port',
            'to_location' => 'Airport',
            'driver_id' => $driver?->id,
            'vessel_id' => $vessel->id,
        ]);

        return $request->fresh(['items']);
    }

    protected function approveRequest(PartnerRequest $request, User $staff): Trip
    {
        $this->actingAs($staff)->post(route('partner-requests.approve', $request), [
            'request_version' => PartnerRequestReviewVersion::make($request->fresh('items')),
        ])->assertRedirect(route('trips.create-from-partner-request', $request));

        $request->refresh();
        $item = $request->items->first();
        $vesselId = $item->vessel_id ?? $this->createVessel()->id;

        $this->actingAs($staff)->post(route('trips.store'), [
            'partner_request_id' => $request->id,
            'partner_id' => $request->partner_id,
            'crews' => [[
                'driver_id' => $item->driver_id,
                'trip_date' => $item->trip_date instanceof Carbon
                    ? $item->trip_date->format('Y-m-d')
                    : Carbon::parse($item->trip_date)->format('Y-m-d'),
                'vessel_id' => $vesselId,
                'pick_up_time' => '09:00',
                'from_location' => $item->from_location ?? 'Port',
                'to_location' => $item->to_location ?? 'Airport',
                'name' => $item->name ?? 'Crew',
            ]],
        ])->assertRedirect(route('partner-requests.show', $request));

        return Trip::where('partner_request_id', $request->id)->firstOrFail();
    }

    protected function createTripsFromApprovedRequest(PartnerRequest $request, User $staff, array $crews): void
    {
        $this->actingAs($staff)->post(route('partner-requests.approve', $request), [
            'request_version' => PartnerRequestReviewVersion::make($request->fresh('items')),
        ])->assertRedirect(route('trips.create-from-partner-request', $request));

        $this->actingAs($staff)->post(route('trips.store'), [
            'partner_request_id' => $request->id,
            'partner_id' => $request->partner_id,
            'crews' => $crews,
        ])->assertRedirect(route('partner-requests.show', $request));
    }

    protected function crewUpdatePayload(Trip $trip, array $overrides = []): array
    {
        $crew = $trip->crews->first();
        $base = [
            'driver_id' => $trip->driver_id,
            'partner_id' => $trip->partner_id,
            'title' => $trip->title,
            'crews' => [
                array_merge([
                    'driver_id' => $trip->driver_id,
                    'trip_date' => $trip->trip_date instanceof Carbon
                        ? $trip->trip_date->format('Y-m-d')
                        : Carbon::parse($trip->trip_date)->format('Y-m-d'),
                    'vessel_id' => $crew->vessel_id,
                    'pick_up_time' => '09:00',
                    'from_location' => $crew->from_location ?? 'Port',
                    'to_location' => $crew->to_location ?? 'Airport',
                    'name' => $crew->name ?? 'Crew',
                ], $overrides),
            ],
        ];

        return $base;
    }

    public function test_partner_trip_shows_trp_and_req_source_internally(): void
    {
        $admin = $this->createAdmin();
        [$partner, $partnerUser] = $this->createPartnerContext();
        $driver = Driver::create(['name' => 'Driver A']);
        $request = $this->createPendingRequest($partner, $partnerUser, $driver);
        $staff = $this->createStaff(['view_trips', 'edit_trips', 'create_trips']);
        $trip = $this->approveRequest($request, $staff);

        $this->actingAs($admin)
            ->get(route('trips.show', $trip))
            ->assertOk()
            ->assertSee($trip->trip_reference)
            ->assertSee($request->fresh()->request_reference)
            ->assertSee('Source Request');

        $internalTrip = Trip::create([
            'driver_id' => $driver->id,
            'trip_date' => now()->toDateString(),
            'title' => 'Internal Trip',
            'status' => 'assigned',
        ]);

        $this->actingAs($admin)
            ->get(route('trips.show', $internalTrip))
            ->assertOk()
            ->assertSee($internalTrip->trip_reference)
            ->assertDontSee('Source Request');
    }

    public function test_trip_index_search_by_trp_and_req_reference(): void
    {
        $admin = $this->createAdmin();
        [$partner, $partnerUser] = $this->createPartnerContext();
        $driver = Driver::create(['name' => 'Driver A']);
        $request = $this->createPendingRequest($partner, $partnerUser, $driver);
        $staff = $this->createStaff(['view_trips', 'edit_trips', 'create_trips']);
        $trip = $this->approveRequest($request, $staff);

        $this->actingAs($admin)
            ->get(route('trips.index', [
                'search' => $trip->trip_reference,
                'date_range' => 'this_month',
            ]))
            ->assertOk()
            ->assertSee($trip->trip_reference);

        $this->actingAs($admin)
            ->get(route('trips.index', [
                'search' => $request->fresh()->request_reference,
                'date_range' => 'this_month',
            ]))
            ->assertOk()
            ->assertSee($trip->trip_reference)
            ->assertSee($request->fresh()->request_reference);
    }

    public function test_partner_portal_shows_trp_status_without_driver_or_internal_notes(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $driver = Driver::create(['name' => 'Secret Driver']);
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
            'approved_at' => now(),
            'decline_reason' => 'Should not show on approved',
        ]);

        $trip = Trip::create([
            'partner_request_id' => $request->id,
            'partner_id' => $partner->id,
            'driver_id' => $driver->id,
            'trip_date' => '2026-08-21',
            'title' => 'Trip 1',
            'status' => 'completed',
        ]);

        $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.show', $request))
            ->assertOk()
            ->assertSee($trip->fresh()->trip_reference)
            ->assertSee('Aug 21, 2026')
            ->assertSee('Completed')
            ->assertDontSee('Secret Driver')
            ->assertDontSee('Should not show on approved');
    }

    public function test_partner_trip_partner_id_is_immutable_on_update(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $otherPartner = Partner::create(['title' => 'Other Partner']);
        $driver = Driver::create(['name' => 'Driver A']);
        $request = $this->createPendingRequest($partner, $partnerUser, $driver);
        $staff = $this->createStaff(['view_trips', 'edit_trips', 'create_trips']);
        $trip = $this->approveRequest($request, $staff);

        $payload = $this->crewUpdatePayload($trip, [
            'partner_id' => $otherPartner->id,
        ]);
        $payload['partner_id'] = $otherPartner->id;

        $this->actingAs($staff)
            ->put(route('trips.update', $trip), $payload)
            ->assertRedirect(route('trips.index'));

        $trip->refresh();
        $this->assertSame($partner->id, $trip->partner_id);
        $this->assertSame($request->id, $trip->partner_request_id);
    }

    public function test_split_partner_trip_preserves_partner_request_lineage(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $driverA = Driver::create(['name' => 'Driver A']);
        $driverB = Driver::create(['name' => 'Driver B']);
        $vessel = $this->createVessel();
        $request = $this->createPendingRequest($partner, $partnerUser, $driverA);
        $staff = $this->createStaff(['view_trips', 'edit_trips', 'create_trips']);
        $trip = $this->approveRequest($request, $staff);

        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Crew 2',
            'from_location' => 'Port',
            'to_location' => 'Airport',
            'pick_up_time' => '10:00:00',
        ]);
        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Crew 3',
            'from_location' => 'Port',
            'to_location' => 'Airport',
            'pick_up_time' => '11:00:00',
        ]);
        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Crew 4',
            'from_location' => 'Port',
            'to_location' => 'Airport',
            'pick_up_time' => '12:00:00',
        ]);

        $tripDate = $trip->trip_date instanceof Carbon
            ? $trip->trip_date->format('Y-m-d')
            : Carbon::parse($trip->trip_date)->format('Y-m-d');

        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driverA->id,
            'partner_id' => $partner->id,
            'title' => $trip->title,
            'crews' => [
                [
                    'driver_id' => $driverA->id,
                    'trip_date' => $tripDate,
                    'vessel_id' => $vessel->id,
                    'pick_up_time' => '09:00',
                    'from_location' => 'Port',
                    'to_location' => 'Airport',
                    'name' => 'Crew 1',
                ],
                [
                    'driver_id' => $driverA->id,
                    'trip_date' => $tripDate,
                    'vessel_id' => $vessel->id,
                    'pick_up_time' => '10:00',
                    'from_location' => 'Port',
                    'to_location' => 'Airport',
                    'name' => 'Crew 2',
                ],
                [
                    'driver_id' => $driverB->id,
                    'trip_date' => '2026-08-22',
                    'vessel_id' => $vessel->id,
                    'pick_up_time' => '11:00',
                    'from_location' => 'Port',
                    'to_location' => 'Airport',
                    'name' => 'Crew 3',
                ],
                [
                    'driver_id' => $driverB->id,
                    'trip_date' => '2026-08-22',
                    'vessel_id' => $vessel->id,
                    'pick_up_time' => '12:00',
                    'from_location' => 'Port',
                    'to_location' => 'Airport',
                    'name' => 'Crew 4',
                ],
            ],
        ])->assertRedirect(route('trips.index'));

        $trips = Trip::where('partner_request_id', $request->id)->get();
        $this->assertCount(2, $trips);
        $this->assertTrue($trips->every(fn (Trip $t) => $t->partner_id === $partner->id));
    }

    public function test_split_internal_trip_keeps_null_partner_request_id(): void
    {
        $staff = $this->createStaff(['view_trips', 'edit_trips']);
        $driverA = Driver::create(['name' => 'Driver A']);
        $driverB = Driver::create(['name' => 'Driver B']);
        $vessel = $this->createVessel();

        $trip = Trip::create([
            'driver_id' => $driverA->id,
            'trip_date' => now()->toDateString(),
            'title' => 'Internal',
            'status' => 'assigned',
        ]);

        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Crew 1',
            'from_location' => 'A',
            'to_location' => 'B',
            'pick_up_time' => '09:00:00',
        ]);
        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Crew 2',
            'from_location' => 'A',
            'to_location' => 'B',
            'pick_up_time' => '10:00:00',
        ]);

        $tripDate = now()->toDateString();
        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driverA->id,
            'crews' => [
                [
                    'driver_id' => $driverA->id,
                    'trip_date' => $tripDate,
                    'vessel_id' => $vessel->id,
                    'pick_up_time' => '09:00',
                    'from_location' => 'A',
                    'to_location' => 'B',
                    'name' => 'Crew 1',
                ],
                [
                    'driver_id' => $driverB->id,
                    'trip_date' => '2026-08-22',
                    'vessel_id' => $vessel->id,
                    'pick_up_time' => '10:00',
                    'from_location' => 'A',
                    'to_location' => 'B',
                    'name' => 'Crew 2',
                ],
            ],
        ]);

        $this->assertSame(2, Trip::count());
        $this->assertTrue(Trip::whereNull('partner_request_id')->count() === 2);
    }

    public function test_partner_trip_hard_delete_blocked_but_cancel_allowed(): void
    {
        $staff = $this->createStaff(['view_trips', 'edit_trips', 'create_trips', 'delete_trips']);
        [$partner, $partnerUser] = $this->createPartnerContext();
        $driver = Driver::create(['name' => 'Driver A']);
        $request = $this->createPendingRequest($partner, $partnerUser, $driver);
        $trip = $this->approveRequest($request, $staff);

        $this->actingAs($staff)
            ->delete(route('trips.destroy', $trip))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('trips', ['id' => $trip->id]);

        $this->actingAs($staff)
            ->post(route('trips.cancel', $trip))
            ->assertRedirect();

        $trip->refresh();
        $this->assertSame('cancelled', $trip->status);
        $this->assertSame(PartnerRequest::STATUS_APPROVED, $request->fresh()->status);
    }

    public function test_reassignment_preserves_partner_request_link(): void
    {
        $staff = $this->createStaff(['view_trips', 'edit_trips', 'create_trips']);
        [$partner, $partnerUser] = $this->createPartnerContext();
        $driverA = Driver::create(['name' => 'Driver A']);
        $driverB = Driver::create(['name' => 'Driver B']);
        $request = $this->createPendingRequest($partner, $partnerUser, $driverA);
        $trip = $this->approveRequest($request, $staff);
        $originalItemDriverId = $request->items->first()->driver_id;

        $payload = $this->crewUpdatePayload($trip->fresh(['crews']), [
            'driver_id' => $driverB->id,
        ]);
        $payload['driver_id'] = $driverB->id;
        $payload['crews'][0]['driver_id'] = $driverB->id;

        $this->actingAs($staff)->put(route('trips.update', $trip), $payload);

        $trip->refresh();
        $this->assertSame($driverB->id, $trip->driver_id);
        $this->assertSame($request->id, $trip->partner_request_id);
        $this->assertSame($originalItemDriverId, $request->items()->first()->driver_id);
    }

    public function test_approval_with_assigned_driver_sends_no_notification_until_trip_creation(): void
    {
        $staff = $this->createStaff(['view_trips', 'create_trips']);
        [$partner, $partnerUser] = $this->createPartnerContext();
        $driver = Driver::create([
            'name' => 'Driver A',
            'notification_token' => 'token-abc',
        ]);
        $request = $this->createPendingRequest($partner, $partnerUser, $driver);

        $this->actingAs($staff)->post(route('partner-requests.approve', $request), [
            'request_version' => PartnerRequestReviewVersion::make($request->fresh('items')),
        ])->assertRedirect(route('trips.create-from-partner-request', $request));

        $this->assertSame(0, Notification::where('driver_id', $driver->id)->count());
        $this->assertSame(0, Trip::where('partner_request_id', $request->id)->count());

        $item = $request->fresh('items')->items->first();
        $this->actingAs($staff)->post(route('trips.store'), [
            'partner_request_id' => $request->id,
            'partner_id' => $request->partner_id,
            'crews' => [[
                'driver_id' => $driver->id,
                'trip_date' => $item->trip_date instanceof Carbon
                    ? $item->trip_date->format('Y-m-d')
                    : Carbon::parse($item->trip_date)->format('Y-m-d'),
                'vessel_id' => $item->vessel_id,
                'pick_up_time' => '09:00',
                'from_location' => $item->from_location ?? 'Port',
                'to_location' => $item->to_location ?? 'Airport',
                'name' => $item->name ?? 'Crew',
            ]],
        ]);

        $this->assertSame(1, Notification::where('driver_id', $driver->id)->count());
        $this->assertDatabaseHas('trips', ['partner_request_id' => $request->id, 'driver_id' => $driver->id]);
    }

    public function test_approval_without_driver_sends_no_notification_until_assigned(): void
    {
        $staff = $this->createStaff(['view_trips', 'edit_trips', 'create_trips']);
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser, null);
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token']);

        $this->actingAs($staff)->post(route('partner-requests.approve', $request), [
            'request_version' => PartnerRequestReviewVersion::make($request->fresh('items')),
        ])->assertRedirect(route('trips.create-from-partner-request', $request));

        $this->assertSame(0, Trip::where('partner_request_id', $request->id)->count());
        $this->assertSame(0, Notification::count());

        $item = $request->fresh('items')->items->first();
        $this->actingAs($staff)->post(route('trips.store'), [
            'partner_request_id' => $request->id,
            'partner_id' => $request->partner_id,
            'crews' => [[
                'trip_date' => $item->trip_date instanceof Carbon
                    ? $item->trip_date->format('Y-m-d')
                    : Carbon::parse($item->trip_date)->format('Y-m-d'),
                'vessel_id' => $item->vessel_id,
                'pick_up_time' => '09:00',
                'from_location' => $item->from_location ?? 'Port',
                'to_location' => $item->to_location ?? 'Airport',
                'name' => $item->name ?? 'Crew',
            ]],
        ]);

        $trip = Trip::where('partner_request_id', $request->id)->firstOrFail();
        $this->assertNull($trip->driver_id);
        $this->assertSame('unassigned', $trip->status);
        $this->assertSame(0, Notification::count());

        $this->actingAs($staff)->patch(route('trips.assign-driver', $trip), [
            'driver_id' => $driver->id,
        ]);

        $this->assertSame(1, Notification::where('driver_id', $driver->id)->count());
    }

    public function test_push_failure_does_not_roll_back_trip_creation_from_approved_request(): void
    {
        $this->mock(FirebaseNotificationService::class, function ($mock) {
            $mock->shouldReceive('sendToDriver')->andThrow(new \RuntimeException('FCM down'));
        });

        $staff = $this->createStaff(['view_trips', 'create_trips']);
        [$partner, $partnerUser] = $this->createPartnerContext();
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token']);
        $request = $this->createPendingRequest($partner, $partnerUser, $driver);

        $trip = $this->approveRequest($request, $staff);

        $this->assertSame(PartnerRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertSame(1, Trip::where('partner_request_id', $request->id)->count());
        $this->assertSame(1, Notification::where('driver_id', $driver->id)->count());
        $this->assertSame($trip->id, Trip::where('partner_request_id', $request->id)->value('id'));
    }

    public function test_two_assigned_trps_produce_two_notifications_after_trip_creation(): void
    {
        $staff = $this->createStaff(['view_trips', 'create_trips']);
        [$partner, $partnerUser] = $this->createPartnerContext();
        $driverA = Driver::create(['name' => 'Driver A', 'notification_token' => 'a']);
        $driverB = Driver::create(['name' => 'Driver B', 'notification_token' => 'b']);
        $vessel = $this->createVessel();

        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        foreach ([
            [$driverA->id, '2026-08-21'],
            [$driverB->id, '2026-08-22'],
        ] as [$driverId, $date]) {
            PartnerRequestItem::create([
                'partner_request_id' => $request->id,
                'driver_id' => $driverId,
                'trip_date' => $date,
                'name' => 'Crew',
                'from_location' => 'A',
                'to_location' => 'B',
                'vessel_id' => $vessel->id,
            ]);
        }

        $this->createTripsFromApprovedRequest($request->fresh('items'), $staff, [
            [
                'driver_id' => $driverA->id,
                'trip_date' => '2026-08-21',
                'vessel_id' => $vessel->id,
                'pick_up_time' => '09:00',
                'from_location' => 'A',
                'to_location' => 'B',
                'name' => 'Crew A',
            ],
            [
                'driver_id' => $driverB->id,
                'trip_date' => '2026-08-22',
                'vessel_id' => $vessel->id,
                'pick_up_time' => '09:00',
                'from_location' => 'A',
                'to_location' => 'B',
                'name' => 'Crew B',
            ],
        ]);

        $this->assertSame(2, Trip::where('partner_request_id', $request->id)->count());
        $this->assertSame(1, Notification::where('driver_id', $driverA->id)->count());
        $this->assertSame(1, Notification::where('driver_id', $driverB->id)->count());
    }

    public function test_driver_schedule_home_and_trips_include_partner_trip_with_trip_reference(): void
    {
        [$partner] = $this->createPartnerContext();
        $driver = Driver::create(['name' => 'Driver A']);
        $otherDriver = Driver::create(['name' => 'Driver B']);
        $vessel = $this->createVessel();
        $today = now()->toDateString();

        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $assignedTrip = Trip::create([
            'partner_request_id' => $request->id,
            'partner_id' => $partner->id,
            'driver_id' => $driver->id,
            'trip_date' => $today,
            'title' => 'Assigned Trip',
            'status' => 'assigned',
        ]);
        TripCrew::create([
            'trip_id' => $assignedTrip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Crew',
            'from_location' => 'Port',
            'to_location' => 'Airport',
            'pick_up_time' => '09:00:00',
        ]);

        Trip::create([
            'partner_request_id' => $request->id,
            'partner_id' => $partner->id,
            'driver_id' => null,
            'trip_date' => $today,
            'title' => 'Unassigned Trip',
            'status' => 'unassigned',
        ]);

        Trip::create([
            'partner_request_id' => $request->id,
            'partner_id' => $partner->id,
            'driver_id' => $otherDriver->id,
            'trip_date' => $today,
            'title' => 'Other Driver Trip',
            'status' => 'assigned',
        ]);

        Sanctum::actingAs($driver);

        $this->getJson(route('api.driver.schedule'))
            ->assertOk()
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.tasks.pending.0.trip_reference', $assignedTrip->fresh()->trip_reference);

        $this->getJson(route('api.driver.home'))
            ->assertOk()
            ->assertJsonPath('data.statistics.total_trips', 1)
            ->assertJsonPath('data.next_trip.trip_reference', $assignedTrip->fresh()->trip_reference);

        $tripsResponse = $this->getJson(route('api.driver.trips'))
            ->assertOk()
            ->assertJsonPath('total', 1);

        $tripPayload = $tripsResponse->json('trips.0');
        $this->assertSame($assignedTrip->fresh()->trip_reference, $tripPayload['trip_reference']);
        $this->assertSame('Port', $tripPayload['from_location']);
        $this->assertSame('Airport', $tripPayload['to_location']);
        $this->assertSame('Vessel A', $tripPayload['vessel']['name']);
        $this->assertCount(1, $tripPayload['crews']);
    }

    public function test_driver_lifecycle_issues_and_expenses_for_partner_trip(): void
    {
        [$partner] = $this->createPartnerContext();
        $driver = Driver::create(['name' => 'Driver A']);
        $vessel = $this->createVessel();
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $trip = Trip::create([
            'partner_request_id' => $request->id,
            'partner_id' => $partner->id,
            'driver_id' => $driver->id,
            'trip_date' => now()->toDateString(),
            'title' => 'Trip 1',
            'status' => 'assigned',
        ]);
        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Crew',
            'from_location' => 'Port',
            'to_location' => 'Airport',
            'pick_up_time' => '09:00:00',
        ]);

        $lifecycle = app(TripLifecyclePresenter::class)->present($trip->fresh());
        $this->assertNotEmpty($lifecycle['steps']);

        Sanctum::actingAs($driver);
        $this->putJson(route('api.driver.trip.update-status', $trip->id), ['status' => 'in_progress'])->assertOk();
        $this->putJson(route('api.driver.trip.update-status', $trip->id), ['status' => 'completed'])->assertOk();

        $this->assertTrue(
            ActivityLog::where('loggable_type', Trip::class)
                ->where('loggable_id', $trip->id)
                ->where('driver_id', $driver->id)
                ->exists()
        );

        $issueType = TripIssueType::create(['title' => 'Delay']);
        $expenseType = TripExpenseType::create(['title' => 'Fuel', 'input_types' => ['amount', 'hours']]);

        $this->postJson(route('api.driver.trip.submit-issue', $trip->id), [
            'issue_type_id' => $issueType->id,
            'description' => 'Late',
        ])->assertCreated();

        $this->postJson(route('api.driver.trip.submit-expense', $trip->id), [
            'expense_type_id' => $expenseType->id,
            'amount' => 10,
            'hours' => 2,
        ])->assertCreated();

        $this->assertSame(1, TripIssue::where('trip_id', $trip->id)->count());
        $this->assertSame(1, TripExpense::where('trip_id', $trip->id)->count());
        $this->assertSame(PartnerRequest::STATUS_APPROVED, $request->fresh()->status);
    }

    public function test_reports_include_partner_trip_and_exclude_pending_requests(): void
    {
        $admin = $this->createAdmin();
        [$partner, $partnerUser] = $this->createPartnerContext();
        $driver = Driver::create(['name' => 'Driver A', 'type' => 1]);
        $vessel = $this->createVessel();
        $date = now()->toDateString();

        $approvedRequest = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $trip = Trip::create([
            'partner_request_id' => $approvedRequest->id,
            'partner_id' => $partner->id,
            'driver_id' => $driver->id,
            'trip_date' => $date,
            'title' => 'Partner Trip',
            'status' => 'completed',
        ]);
        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Crew',
            'from_location' => 'A',
            'to_location' => 'B',
            'pick_up_time' => '09:00:00',
        ]);

        $expenseType = TripExpenseType::create(['title' => 'Fuel', 'input_types' => ['amount']]);
        TripExpense::create([
            'trip_id' => $trip->id,
            'driver_id' => $driver->id,
            'expense_type_id' => $expenseType->id,
            'amount' => 50,
        ]);

        foreach ([
            PartnerRequest::STATUS_PENDING,
            PartnerRequest::STATUS_DECLINED,
            PartnerRequest::STATUS_WITHDRAWN,
        ] as $status) {
            PartnerRequest::create([
                'partner_id' => $partner->id,
                'partner_user_id' => $partnerUser->id,
                'submission_method' => PartnerRequest::METHOD_MANUAL,
                'status' => $status,
                'submitted_at' => now(),
            ]);
        }

        $filters = [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
            'partner_id' => $partner->id,
        ];

        $this->actingAs($admin)
            ->get(route('reports.trip-summary', $filters))
            ->assertOk()
            ->assertSee('CREW');

        $this->actingAs($admin)
            ->get(route('reports.driver-performance', $filters))
            ->assertOk()
            ->assertSee(strtoupper($driver->name));

        $this->actingAs($admin)
            ->get(route('reports.trip-expenses', $filters))
            ->assertOk()
            ->assertSee('50');

        $this->assertSame(1, Trip::where('partner_id', $partner->id)->count());
    }

    public function test_trip_assignment_notification_service_is_reused_for_internal_store(): void
    {
        $staff = $this->createStaff(['view_trips', 'create_trips']);
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token']);
        $vessel = $this->createVessel();

        $this->mock(TripAssignmentNotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyDriverAssigned')->once();
        });

        $this->actingAs($staff)->post(route('trips.store'), [
            'driver_id' => $driver->id,
            'crews' => [[
                'trip_date' => now()->toDateString(),
                'vessel_id' => $vessel->id,
                'pick_up_time' => '09:00',
                'from_location' => 'A',
                'to_location' => 'B',
                'name' => 'Crew',
            ]],
        ])->assertRedirect(route('trips.index'));
    }

    public function test_store_transaction_rollback_sends_no_assignment_notifications(): void
    {
        $firebase = Mockery::mock(FirebaseNotificationService::class);
        $firebase->shouldReceive('sendToDriver')->never();
        $this->instance(FirebaseNotificationService::class, $firebase);

        $staff = $this->createStaff(['create_trips']);
        $driverA = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        $driverB = Driver::create(['name' => 'Driver B', 'notification_token' => 'token-b']);
        $vessel = $this->createVessel();
        $tripDate = now()->toDateString();

        $createCount = 0;
        Trip::creating(function () use (&$createCount) {
            $createCount++;
            if ($createCount === 2) {
                throw new \RuntimeException('Simulated store failure');
            }
        });

        $this->actingAs($staff)->post(route('trips.store'), [
            'crews' => [
                [
                    'driver_id' => $driverA->id,
                    'trip_date' => $tripDate,
                    'vessel_id' => $vessel->id,
                    'pick_up_time' => '09:00',
                    'from_location' => 'A',
                    'to_location' => 'B',
                    'name' => 'Crew 1',
                ],
                [
                    'driver_id' => $driverB->id,
                    'trip_date' => '2026-08-22',
                    'vessel_id' => $vessel->id,
                    'pick_up_time' => '10:00',
                    'from_location' => 'A',
                    'to_location' => 'B',
                    'name' => 'Crew 2',
                ],
            ],
        ])->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, Trip::count());
        $this->assertSame(0, Notification::count());
    }
}
