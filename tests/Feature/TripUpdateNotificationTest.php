<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Notification;
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
 * Minimal schema — full RefreshDatabase fails on historical SQLite trip migrations.
 */
class TripUpdateNotificationTest extends TestCase
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

    public function test_creating_assigned_trip_sends_assignment_notification(): void
    {
        $staff = $this->createStaff(['create_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        $vessel = $this->createVessel();

        $this->actingAs($staff)->post(route('trips.store'), [
            'driver_id' => $driver->id,
            'crews' => [$this->crewPayload($vessel->id, $driver->id)],
        ])->assertRedirect(route('trips.index'));

        $this->assertSame(1, Notification::where('driver_id', $driver->id)->count());
        $this->assertSame('New Trip Assigned', Notification::where('driver_id', $driver->id)->value('title'));
    }

    public function test_updating_trip_with_same_driver_sends_one_trip_updated_notification(): void
    {
        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        [$trip, $vessel] = $this->createAssignedTrip($driver);

        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driver->id,
            'crews' => [$this->crewPayload($vessel->id, $driver->id, [
                'from_location' => 'New Port',
                'to_location' => 'New Airport',
                'remarks' => 'Updated remarks',
            ])],
        ])->assertRedirect(route('trips.index'));

        $notifications = Notification::where('driver_id', $driver->id)->get();
        $this->assertCount(1, $notifications);
        $this->assertSame('Trip Updated', $notifications->first()->title);
        $this->assertStringContainsString('has been updated', $notifications->first()->message);
        $this->assertStringContainsString($trip->fresh()->trip_reference, $notifications->first()->message);
        $this->assertSame(0, Notification::where('title', 'New Trip Assigned')->count());
    }

    public function test_changing_driver_a_to_b_sends_one_assignment_notification_to_b(): void
    {
        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $driverA = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        $driverB = Driver::create(['name' => 'Driver B', 'notification_token' => 'token-b']);
        [$trip, $vessel] = $this->createAssignedTrip($driverA);

        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driverB->id,
            'crews' => [$this->crewPayload($vessel->id, $driverB->id)],
        ])->assertRedirect(route('trips.index'));

        $this->assertSame(0, Notification::where('driver_id', $driverA->id)->count());
        $this->assertSame(1, Notification::where('driver_id', $driverB->id)->count());
        $this->assertSame('New Trip Assigned', Notification::where('driver_id', $driverB->id)->value('title'));
        $this->assertSame(0, Notification::where('title', 'Trip Updated')->count());
    }

    public function test_assigning_driver_to_previously_unassigned_trip_sends_assignment_notification(): void
    {
        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $driverB = Driver::create(['name' => 'Driver B', 'notification_token' => 'token-b']);
        $vessel = $this->createVessel();
        $trip = Trip::create([
            'driver_id' => null,
            'trip_date' => '2026-08-30',
            'title' => 'Unassigned Trip',
            'status' => TripCrew::STATUS_UNASSIGNED,
        ]);
        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Crew',
            'from_location' => 'Port',
            'to_location' => 'Airport',
            'pick_up_time' => '09:00:00',
        ]);

        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driverB->id,
            'crews' => [$this->crewPayload($vessel->id, $driverB->id)],
        ])->assertRedirect(route('trips.index'));

        $this->assertSame(1, Notification::where('driver_id', $driverB->id)->count());
        $this->assertSame('New Trip Assigned', Notification::where('driver_id', $driverB->id)->value('title'));
        $this->assertSame(0, Notification::where('title', 'Trip Updated')->count());
    }

    public function test_updating_unassigned_trip_sends_no_driver_notification(): void
    {
        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $vessel = $this->createVessel();
        $trip = Trip::create([
            'driver_id' => null,
            'trip_date' => '2026-08-30',
            'title' => 'Unassigned Trip',
            'status' => TripCrew::STATUS_UNASSIGNED,
        ]);
        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Crew',
            'from_location' => 'Port',
            'to_location' => 'Airport',
            'pick_up_time' => '09:00:00',
        ]);

        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'crews' => [$this->crewPayload($vessel->id, null, [
                'from_location' => 'Updated Port',
            ])],
        ])->assertRedirect(route('trips.index'));

        $this->assertSame(0, Notification::count());
    }

    public function test_failed_trip_update_sends_no_notification(): void
    {
        $firebase = Mockery::mock(FirebaseNotificationService::class);
        $firebase->shouldReceive('sendToDriver')->never();
        $this->instance(FirebaseNotificationService::class, $firebase);

        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        [$trip, $vessel] = $this->createAssignedTrip($driver);

        TripCrew::creating(function () {
            throw new \RuntimeException('Simulated update failure');
        });

        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driver->id,
            'crews' => [$this->crewPayload($vessel->id, $driver->id, [
                'from_location' => 'New Port',
            ])],
        ]);

        $this->assertSame(0, Notification::count());
        $this->assertSame('Port', $trip->fresh()->crews()->first()->from_location);
    }

    public function test_splitting_edited_trip_does_not_create_duplicate_notifications(): void
    {
        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $driverA = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        $driverB = Driver::create(['name' => 'Driver B', 'notification_token' => 'token-b']);
        [$trip, $vessel] = $this->createAssignedTrip($driverA);

        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driverA->id,
            'crews' => [
                $this->crewPayload($vessel->id, $driverA->id, ['name' => 'Crew 1']),
                $this->crewPayload($vessel->id, $driverB->id, [
                    'name' => 'Crew 2',
                    'trip_date' => '2026-08-31',
                    'pick_up_time' => '10:00',
                ]),
            ],
        ])->assertRedirect(route('trips.index'));

        $this->assertSame(2, Trip::count());

        $driverANotifications = Notification::where('driver_id', $driverA->id)->get();
        $driverBNotifications = Notification::where('driver_id', $driverB->id)->get();

        $this->assertCount(1, $driverANotifications);
        $this->assertSame('Trip Updated', $driverANotifications->first()->title);

        $this->assertCount(1, $driverBNotifications);
        $this->assertSame('New Trip Assigned', $driverBNotifications->first()->title);

        $this->assertSame(2, Notification::count());
        $this->assertSame(0, Notification::where('driver_id', $driverB->id)->where('title', 'Trip Updated')->count());
    }

    public function test_trip_updated_push_uses_trip_updated_type(): void
    {
        $firebase = Mockery::mock(FirebaseNotificationService::class);
        $firebase->shouldReceive('sendToDriver')
            ->once()
            ->withArgs(function ($driver, $title, $message, $image, $data) {
                return $title === 'Trip Updated'
                    && $image === null
                    && ($data['type'] ?? null) === 'trip_updated'
                    && ! empty($data['trip_id']);
            })
            ->andReturn(true);
        $this->instance(FirebaseNotificationService::class, $firebase);

        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        [$trip, $vessel] = $this->createAssignedTrip($driver);

        $this->actingAs($staff)->put(route('trips.update', $trip), [
            'driver_id' => $driver->id,
            'crews' => [$this->crewPayload($vessel->id, $driver->id, [
                'remarks' => 'Please arrive early',
            ])],
        ])->assertRedirect(route('trips.index'));
    }

    public function test_assign_driver_a_to_b_sends_assignment_notification_to_b(): void
    {
        $staff = $this->createStaff(['edit_trips', 'view_trips']);
        $driverA = Driver::create(['name' => 'Driver A', 'notification_token' => 'token-a']);
        $driverB = Driver::create(['name' => 'Driver B', 'notification_token' => 'token-b']);
        [$trip] = $this->createAssignedTrip($driverA);

        $this->actingAs($staff)->patch(route('trips.assign-driver', $trip), [
            'driver_id' => $driverB->id,
        ]);

        $this->assertSame(0, Notification::where('driver_id', $driverA->id)->count());
        $this->assertSame(1, Notification::where('driver_id', $driverB->id)->count());
        $this->assertSame('New Trip Assigned', Notification::where('driver_id', $driverB->id)->value('title'));
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

    protected function createAssignedTrip(Driver $driver): array
    {
        $vessel = $this->createVessel();
        $trip = Trip::create([
            'driver_id' => $driver->id,
            'trip_date' => '2026-08-30',
            'title' => 'Trip 1',
            'status' => TripCrew::STATUS_ASSIGNED,
        ]);
        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Crew',
            'from_location' => 'Port',
            'to_location' => 'Airport',
            'pick_up_time' => '09:00:00',
        ]);

        return [$trip, $vessel];
    }

    protected function createVessel(): Vessel
    {
        return Vessel::withoutEvents(fn () => Vessel::create(['name' => 'Vessel A']));
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

        foreach (['view_trips', 'create_trips', 'edit_trips'] as $name) {
            Permission::create([
                'name' => $name,
                'display_name' => $name,
                'category' => 'trips',
            ]);
        }
    }
}
