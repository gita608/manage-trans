<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\Trip;
use App\Models\TripCrew;
use App\Models\TripCrewRemoval;
use App\Models\User;
use App\Models\Vessel;
use App\Services\FirebaseNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

/**
 * Minimal schema — full RefreshDatabase fails on historical SQLite trip migrations.
 */
class TripCrewRemovalTest extends TestCase
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

    public function test_removing_existing_crew_creates_removal_history_with_snapshots(): void
    {
        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver Rabil', 'notification_token' => 'token-rabil']);
        $vessel = $this->createVessel('Vessel Alpha');
        [$trip, $crewA, $crewB] = $this->createAssignedTripWithTwoCrews($driver, $vessel);

        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driver->id,
            'crews' => [
                $this->crewPayload($vessel->id, $driver->id, [
                    'id' => $crewA->id,
                    'name' => 'Crew A',
                ]),
            ],
            'removed_crews' => [
                $crewB->id => ['removal_remark' => 'Passenger cancelled'],
            ],
        ])->assertRedirect(route('trips.index'));

        $this->assertSame(1, $trip->fresh()->crews()->count());
        $this->assertSame('Crew A', $trip->fresh()->crews()->first()->name);
        $this->assertSame(1, TripCrewRemoval::count());

        $removal = TripCrewRemoval::first();
        $this->assertSame($trip->id, $removal->trip_id);
        $this->assertSame($crewB->id, $removal->trip_crew_id);
        $this->assertSame('Crew B', $removal->crew_name);
        $this->assertSame('555-0002', $removal->phone);
        $this->assertSame('555-0003', $removal->phone_2);
        $this->assertSame('Dock Road 12', $removal->address);
        $this->assertSame($vessel->id, $removal->vessel_id);
        $this->assertSame('Vessel Alpha', $removal->vessel_name);
        $this->assertSame('Port B', $removal->from_location);
        $this->assertSame('Airport B', $removal->to_location);
        $this->assertSame('FL100', $removal->flight_number);
        $this->assertSame('Original remarks', $removal->remarks);
        $this->assertSame('Sub note', $removal->sub_remark);
        $this->assertSame($driver->id, $removal->driver_id);
        $this->assertSame('Driver Rabil', $removal->driver_name);
        $this->assertSame($staff->id, $removal->removed_by);
        $this->assertSame('Passenger cancelled', $removal->removal_remark);
        $this->assertNotNull($removal->removed_at);
        $this->assertSame(TripCrew::STATUS_ASSIGNED, $trip->fresh()->status);
    }

    public function test_removal_remark_can_be_null(): void
    {
        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        $vessel = $this->createVessel();
        [$trip, $crewA, $crewB] = $this->createAssignedTripWithTwoCrews($driver, $vessel);

        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driver->id,
            'crews' => [
                $this->crewPayload($vessel->id, $driver->id, [
                    'id' => $crewA->id,
                    'name' => 'Crew A',
                ]),
            ],
            'removed_crews' => [
                $crewB->id => ['removal_remark' => ''],
            ],
        ])->assertRedirect(route('trips.index'));

        $this->assertNull(TripCrewRemoval::first()->removal_remark);
    }

    public function test_newly_added_unsaved_crew_does_not_create_removal_history(): void
    {
        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        $vessel = $this->createVessel();
        [$trip, $crewA] = $this->createAssignedTrip($driver, $vessel);

        // Only the original crew is submitted — a brand-new unsaved row never existed in DB.
        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driver->id,
            'crews' => [
                $this->crewPayload($vessel->id, $driver->id, [
                    'id' => $crewA->id,
                    'name' => 'Crew',
                ]),
            ],
        ])->assertRedirect(route('trips.index'));

        $this->assertSame(0, TripCrewRemoval::count());
        $this->assertSame(1, $trip->fresh()->crews()->count());
    }

    public function test_existing_crew_recreated_on_update_does_not_create_false_removal_history(): void
    {
        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        $vessel = $this->createVessel();
        [$trip, $crew] = $this->createAssignedTrip($driver, $vessel);

        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driver->id,
            'crews' => [
                $this->crewPayload($vessel->id, $driver->id, [
                    'id' => $crew->id,
                    'name' => 'Crew',
                    'from_location' => 'Updated Port',
                    'remarks' => 'Still present',
                ]),
            ],
        ])->assertRedirect(route('trips.index'));

        $this->assertSame(0, TripCrewRemoval::count());
        $this->assertSame(1, $trip->fresh()->crews()->count());
        $this->assertSame('Updated Port', $trip->fresh()->crews()->first()->from_location);
    }

    public function test_crew_moved_to_split_trip_does_not_create_removal_history(): void
    {
        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $driverA = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        $driverB = Driver::create(['name' => 'Driver B', 'notification_token' => 'token-b']);
        $vessel = $this->createVessel();
        [$trip, $crewA, $crewB] = $this->createAssignedTripWithTwoCrews($driverA, $vessel);

        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driverA->id,
            'crews' => [
                $this->crewPayload($vessel->id, $driverA->id, [
                    'id' => $crewA->id,
                    'name' => 'Crew A',
                ]),
                $this->crewPayload($vessel->id, $driverB->id, [
                    'id' => $crewB->id,
                    'name' => 'Crew B',
                    'trip_date' => '2026-08-31',
                    'pick_up_time' => '10:00',
                ]),
            ],
        ])->assertRedirect(route('trips.index'));

        $this->assertSame(0, TripCrewRemoval::count());
        $this->assertSame(2, Trip::count());
        $this->assertSame(1, $trip->fresh()->crews()->count());
    }

    public function test_failed_update_does_not_create_removal_history(): void
    {
        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        $vessel = $this->createVessel();
        [$trip, $crewA, $crewB] = $this->createAssignedTripWithTwoCrews($driver, $vessel);

        TripCrew::creating(function () {
            throw new \RuntimeException('Simulated update failure');
        });

        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driver->id,
            'crews' => [
                $this->crewPayload($vessel->id, $driver->id, [
                    'id' => $crewA->id,
                    'name' => 'Crew A',
                ]),
            ],
            'removed_crews' => [
                $crewB->id => ['removal_remark' => 'Should not persist'],
            ],
        ]);

        $this->assertSame(0, TripCrewRemoval::count());
        $this->assertSame(2, $trip->fresh()->crews()->count());
    }

    public function test_validation_failure_does_not_create_removal_history(): void
    {
        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        $vessel = $this->createVessel();
        [$trip, $crewA, $crewB] = $this->createAssignedTripWithTwoCrews($driver, $vessel);

        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driver->id,
            'crews' => [
                $this->crewPayload($vessel->id, $driver->id, [
                    'id' => $crewA->id,
                    'name' => '', // invalid
                ]),
            ],
            'removed_crews' => [
                $crewB->id => ['removal_remark' => 'Should not persist'],
            ],
        ])->assertSessionHasErrors();

        $this->assertSame(0, TripCrewRemoval::count());
        $this->assertSame(2, $trip->fresh()->crews()->count());
    }

    public function test_assigned_trip_allows_crew_removal_and_sends_one_trip_updated_notification(): void
    {
        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver Rabil', 'notification_token' => 'token-rabil']);
        $vessel = $this->createVessel();
        [$trip, $crewA, $crewB] = $this->createAssignedTripWithTwoCrews($driver, $vessel);

        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driver->id,
            'crews' => [
                $this->crewPayload($vessel->id, $driver->id, [
                    'id' => $crewA->id,
                    'name' => 'Crew A',
                ]),
            ],
            'removed_crews' => [
                $crewB->id => ['removal_remark' => 'No longer needed'],
            ],
        ])->assertRedirect(route('trips.index'));

        $this->assertSame(TripCrew::STATUS_ASSIGNED, $trip->fresh()->status);
        $this->assertSame(1, TripCrewRemoval::count());

        $notifications = Notification::where('driver_id', $driver->id)->get();
        $this->assertCount(1, $notifications);
        $this->assertSame('Trip Updated', $notifications->first()->title);
        $this->assertSame(0, Notification::where('title', 'New Trip Assigned')->count());
    }

    public function test_trip_details_shows_removed_crew_history(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $driver = Driver::create(['name' => 'Driver Rabil']);
        $vessel = $this->createVessel('Vessel Alpha');
        [$trip] = $this->createAssignedTrip($driver, $vessel);

        TripCrewRemoval::create([
            'trip_id' => $trip->id,
            'trip_crew_id' => 99,
            'crew_name' => 'Removed Person',
            'phone' => '111',
            'phone_2' => '222',
            'address' => 'Somewhere',
            'vessel_id' => $vessel->id,
            'vessel_name' => 'Vessel Alpha',
            'pick_up_time' => '09:30:00',
            'from_location' => 'Dock',
            'to_location' => 'Gate',
            'flight_number' => 'XY1',
            'remarks' => 'Note',
            'sub_remark' => 'Sub',
            'driver_id' => $driver->id,
            'driver_name' => 'Driver Rabil',
            'removed_by' => $staff->id,
            'removed_at' => now(),
            'removal_remark' => 'Client request',
        ]);

        $response = $this->actingAs($staff)->get(route('trips.show', $trip));
        $response->assertOk();
        $response->assertSee('Removed Crew', false);
        $response->assertSee('Removed Person', false);
        $response->assertSee('Driver Rabil', false);
        $response->assertSee('Client request', false);
        $response->assertSee('Dock', false);
        $response->assertSee('Gate', false);
        $response->assertDontSee('No crew members have been removed from this trip.', false);
    }

    public function test_trip_details_shows_empty_state_when_no_removals(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $driver = Driver::create(['name' => 'Driver A']);
        [$trip] = $this->createAssignedTrip($driver);

        $response = $this->actingAs($staff)->get(route('trips.show', $trip));
        $response->assertOk();
        $response->assertSee('No crew members have been removed from this trip.', false);
    }

    public function test_trip_delete_confirmation_markup_remains_unchanged(): void
    {
        $staff = $this->createStaff(['view_trips', 'delete_trips']);
        $driver = Driver::create(['name' => 'Driver A']);
        [$trip] = $this->createAssignedTrip($driver);
        $trip->update(['trip_date' => today()->toDateString()]);

        $show = $this->actingAs($staff)->get(route('trips.show', $trip));
        $show->assertOk();
        $show->assertSee("return confirm('Are you sure you want to delete this trip?');", false);

        $index = $this->actingAs($staff)->get(route('trips.index'));
        $index->assertOk();
        $index->assertSee("return confirm('Are you sure you want to delete this trip?');", false);
    }

    public function test_trip_crew_removals_migration_is_reversible(): void
    {
        Schema::dropIfExists('trip_crew_removals');

        $migration = require database_path('migrations/2026_08_30_120000_create_trip_crew_removals_table.php');
        $migration->up();
        $this->assertTrue(Schema::hasTable('trip_crew_removals'));

        $migration->down();
        $this->assertFalse(Schema::hasTable('trip_crew_removals'));

        // Restore for subsequent assertions if any run after (none expected).
        $migration->up();
    }

    public function test_driver_snapshot_remains_after_driver_rename(): void
    {
        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Original Driver Name', 'notification_token' => 'token-a']);
        $vessel = $this->createVessel();
        [$trip, $crewA, $crewB] = $this->createAssignedTripWithTwoCrews($driver, $vessel);

        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driver->id,
            'crews' => [
                $this->crewPayload($vessel->id, $driver->id, [
                    'id' => $crewA->id,
                    'name' => 'Crew A',
                ]),
            ],
            'removed_crews' => [
                $crewB->id => ['removal_remark' => null],
            ],
        ])->assertRedirect(route('trips.index'));

        $driver->update(['name' => 'Renamed Driver']);

        $removal = TripCrewRemoval::first();
        $this->assertSame('Original Driver Name', $removal->driver_name);
        $this->assertSame($driver->id, $removal->driver_id);
    }

    protected function crewPayload(int $vesselId, ?int $driverId, array $overrides = []): array
    {
        return array_merge([
            'driver_id' => $driverId,
            'trip_date' => '2026-08-30',
            'vessel_id' => $vesselId,
            'pick_up_time' => '09:00',
            'from_location' => 'Port',
            'to_location' => 'Airport',
            'name' => 'Crew',
        ], $overrides);
    }

    protected function createAssignedTrip(Driver $driver, ?Vessel $vessel = null): array
    {
        $vessel = $vessel ?: $this->createVessel();
        $trip = Trip::create([
            'driver_id' => $driver->id,
            'trip_date' => '2026-08-30',
            'title' => 'Trip 1',
            'status' => TripCrew::STATUS_ASSIGNED,
        ]);
        $crew = TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Crew',
            'from_location' => 'Port',
            'to_location' => 'Airport',
            'pick_up_time' => '09:00:00',
        ]);

        return [$trip, $crew, $vessel];
    }

    protected function createAssignedTripWithTwoCrews(Driver $driver, Vessel $vessel): array
    {
        $trip = Trip::create([
            'driver_id' => $driver->id,
            'trip_date' => '2026-08-30',
            'title' => 'Trip 1',
            'status' => TripCrew::STATUS_ASSIGNED,
        ]);

        $crewA = TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Crew A',
            'phone' => '555-0001',
            'from_location' => 'Port A',
            'to_location' => 'Airport A',
            'pick_up_time' => '09:00:00',
        ]);

        $crewB = TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Crew B',
            'phone' => '555-0002',
            'phone_2' => '555-0003',
            'address' => 'Dock Road 12',
            'from_location' => 'Port B',
            'to_location' => 'Airport B',
            'pick_up_time' => '10:00:00',
            'flight_number' => 'FL100',
            'remarks' => 'Original remarks',
            'sub_remark' => 'Sub note',
        ]);

        return [$trip, $crewA, $crewB];
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

        Schema::create('trip_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id');
            $table->foreignId('driver_id')->nullable();
            $table->foreignId('issue_type_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('trip_issue_types', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('trip_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id');
            $table->foreignId('driver_id')->nullable();
            $table->foreignId('expense_type_id')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('hours', 8, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('receipt')->nullable();
            $table->timestamps();
        });

        Schema::create('trip_expense_types', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        foreach (['view_trips', 'create_trips', 'edit_trips', 'delete_trips'] as $name) {
            Permission::create([
                'name' => $name,
                'display_name' => $name,
                'category' => 'trips',
            ]);
        }
    }
}
