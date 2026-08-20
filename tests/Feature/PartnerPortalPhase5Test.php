<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\PartnerRequestItem;
use App\Models\PartnerUser;
use App\Models\Permission;
use App\Models\Trip;
use App\Models\TripCrew;
use App\Models\User;
use App\Models\Vessel;
use App\Support\PartnerRequestReviewVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PartnerPortalPhase5Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
        $this->seedPermissions();
    }

    protected function createSchema(): void
    {
        $tables = [
            'trip_crews', 'trips', 'partner_request_items', 'partner_requests',
            'partner_users', 'partners', 'drivers', 'vessels', 'activity_logs',
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
            $table->string('phone_2')->nullable();
            $table->string('address')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->string('flight_number')->nullable();
            $table->text('remarks')->nullable();
            $table->string('sub_remark')->nullable();
            $table->string('vessel_name_raw')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->foreignId('vessel_id')->nullable();
            $table->timestamps();
        });

        Schema::create('drivers', function ($table) {
            $table->id();
            $table->string('name');
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

    protected function seedPermissions(): void
    {
        foreach (['view_trips', 'create_trips', 'edit_trips'] as $name) {
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

    protected function createPendingRequest(Partner $partner, PartnerUser $partnerUser, string $method = 'manual', array $itemOverrides = []): PartnerRequest
    {
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => $method,
            'status' => PartnerRequest::STATUS_PENDING,
            'submitted_at' => now()->subHour(),
        ]);

        PartnerRequestItem::create(array_merge([
            'partner_request_id' => $request->id,
            'trip_date' => '2026-08-21',
            'pick_up_time' => '09:00:00',
            'name' => 'Crew Member',
            'from_location' => 'Port',
            'to_location' => 'Airport',
            'vessel_id' => Vessel::withoutEvents(fn () => Vessel::create(['name' => 'Vessel A']))->id,
        ], $itemOverrides));

        return $request->fresh(['items']);
    }

    protected function tripCrewPayload(PartnerRequestItem $item, ?int $driverId = null, ?int $vesselId = null, array $overrides = []): array
    {
        return array_merge([
            'driver_id' => $driverId ?? $item->driver_id,
            'trip_date' => $item->trip_date?->format('Y-m-d') ?? '2026-08-21',
            'pick_up_time' => '09:00',
            'name' => $item->name ?? 'Crew Member',
            'from_location' => $item->from_location ?? 'Port',
            'to_location' => $item->to_location ?? 'Airport',
            'vessel_id' => $vesselId ?? $item->vessel_id,
        ], $overrides);
    }

    protected function approveRequest(PartnerRequest $request, User $staff): PartnerRequest
    {
        $this->actingAs($staff)
            ->post(route('partner-requests.approve', $request), [
                'request_version' => $this->requestVersion($request->fresh('items')),
            ])
            ->assertRedirect(route('trips.create-from-partner-request', $request))
            ->assertSessionHas('success');

        return $request->fresh(['items']);
    }

    protected function createTripsFromApprovedRequest(PartnerRequest $request, User $staff, ?array $crews = null): void
    {
        if (!$request->fresh()->isApproved()) {
            $request = $this->approveRequest($request, $staff);
        }

        $crews ??= $request->items->map(fn ($item) => $this->tripCrewPayload($item))->all();

        $this->actingAs($staff)->post(route('trips.store'), [
            'partner_request_id' => $request->id,
            'partner_id' => $request->partner_id,
            'crews' => $crews,
        ])->assertRedirect(route('partner-requests.show', $request));
    }

    protected function requestVersion(PartnerRequest $request): string
    {
        return PartnerRequestReviewVersion::make($request->fresh('items'));
    }

    public function test_guest_cannot_access_partner_requests_inbox(): void
    {
        $this->get(route('partner-requests.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_view_trips_cannot_access_inbox(): void
    {
        $staff = $this->createStaff();
        $this->actingAs($staff)->get(route('partner-requests.index'))->assertRedirect(route('error.403'));
    }

    public function test_user_with_view_trips_can_access_inbox(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $this->actingAs($staff)->get(route('partner-requests.index'))->assertOk();
    }

    public function test_pending_is_default_queue_and_filters_work(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $pending = $this->createPendingRequest($partner, $partnerUser);
        $approved = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
        ]);

        $staff = $this->createStaff(['view_trips']);

        $this->actingAs($staff)
            ->get(route('partner-requests.index'))
            ->assertOk()
            ->assertSee($pending->fresh()->request_reference)
            ->assertDontSee($approved->fresh()->request_reference);

        $this->actingAs($staff)
            ->get(route('partner-requests.index', ['status' => 'approved']))
            ->assertSee($approved->fresh()->request_reference);
    }

    public function test_inbox_supports_method_filter_search_and_pagination(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $manual = $this->createPendingRequest($partner, $partnerUser, PartnerRequest::METHOD_MANUAL);
        $image = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $staff = $this->createStaff(['view_trips']);

        $this->actingAs($staff)
            ->get(route('partner-requests.index', ['submission_method' => 'image']))
            ->assertSee($image->fresh()->request_reference)
            ->assertDontSee($manual->fresh()->request_reference);

        $this->actingAs($staff)
            ->get(route('partner-requests.index', ['search' => 'ZAKHER']))
            ->assertSee($manual->fresh()->request_reference);
    }

    public function test_sidebar_pending_badge_counts_only_pending_requests(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $this->createPendingRequest($partner, $partnerUser);
        PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
        ]);

        $staff = $this->createStaff(['view_trips']);
        $this->actingAs($staff)
            ->get(route('partner-requests.index'))
            ->assertSee('Partner Requests')
            ->assertSee('>1<', false);
    }

    public function test_pending_manual_request_detail_is_read_only(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staff = $this->createStaff(['view_trips', 'create_trips']);

        $this->actingAs($staff)
            ->get(route('partner-requests.show', $request))
            ->assertOk()
            ->assertSee($request->fresh()->request_reference)
            ->assertSee('Approve Request')
            ->assertSee('Decline Request')
            ->assertDontSee('Save Review')
            ->assertDontSee('Add Crew Member');
    }

    public function test_pending_image_request_detail_is_read_only(): void
    {
        Storage::fake('local');
        [$partner, $partnerUser] = $this->createPartnerContext();
        $path = 'partner-requests/'.$partner->id.'/schedule.jpg';
        Storage::disk('local')->put($path, 'image');

        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
            'submitted_at' => now(),
            'source_image_path' => $path,
            'extraction_status' => PartnerRequest::EXTRACTION_FAILED,
        ]);

        $staff = $this->createStaff(['view_trips', 'create_trips']);

        $this->actingAs($staff)
            ->get(route('partner-requests.show', $request))
            ->assertOk()
            ->assertSee('Automatic extraction was unsuccessful')
            ->assertDontSee('Save Review');
    }

    public function test_approve_sets_status_without_creating_trips_or_notifications(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token']);
        $request = $this->createPendingRequest($partner, $partnerUser, 'manual', ['driver_id' => $driver->id]);
        $staff = $this->createStaff(['view_trips', 'create_trips']);

        $this->approveRequest($request, $staff);

        $this->assertSame(PartnerRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertSame(0, Trip::count());
        $this->assertSame(0, TripCrew::count());
        $this->assertSame(0, \App\Models\Notification::count());
    }

    public function test_approve_succeeds_with_incomplete_operational_fields(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_PENDING,
            'submitted_at' => now(),
        ]);
        PartnerRequestItem::create([
            'partner_request_id' => $request->id,
            'name' => 'Only Name',
        ]);

        $staff = $this->createStaff(['view_trips', 'create_trips']);
        $this->approveRequest($request->fresh('items'), $staff);

        $this->assertSame(PartnerRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertSame(0, Trip::count());
    }

    public function test_approve_image_request_with_zero_rows_succeeds(): void
    {
        Storage::fake('local');
        [$partner, $partnerUser] = $this->createPartnerContext();
        $path = 'partner-requests/'.$partner->id.'/empty.jpg';
        Storage::disk('local')->put($path, 'image');

        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
            'submitted_at' => now(),
            'source_image_path' => $path,
            'extraction_status' => PartnerRequest::EXTRACTION_FAILED,
        ]);

        $staff = $this->createStaff(['view_trips', 'create_trips']);
        $this->approveRequest($request, $staff);

        $this->assertSame(PartnerRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertSame(0, Trip::count());
    }

    public function test_approval_requires_create_trips_permission(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staff = $this->createStaff(['view_trips']);

        $this->actingAs($staff)
            ->post(route('partner-requests.approve', $request), [
                'request_version' => $this->requestVersion($request),
            ])
            ->assertRedirect(route('error.403'));
    }

    public function test_two_trp_example_creates_two_trips_via_normal_trip_store(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $driverA = Driver::create(['name' => 'Driver A']);
        $driverB = Driver::create(['name' => 'Driver B']);
        $vessel = Vessel::withoutEvents(fn () => Vessel::create(['name' => 'Vessel A']));

        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_PENDING,
            'submitted_at' => now(),
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
                'name' => $name,
                'from_location' => 'A',
                'to_location' => 'B',
                'vessel_id' => $vessel->id,
            ]);
        }

        $staff = $this->createStaff(['view_trips', 'create_trips']);
        $request = $this->approveRequest($request->fresh('items'), $staff);

        $crews = $request->items->map(fn ($item) => $this->tripCrewPayload($item, $item->driver_id, $vessel->id))->all();
        $this->actingAs($staff)->post(route('trips.store'), [
            'partner_request_id' => $request->id,
            'partner_id' => $partner->id,
            'crews' => $crews,
        ])->assertRedirect(route('partner-requests.show', $request));

        $trips = Trip::where('partner_request_id', $request->id)->get();
        $this->assertCount(2, $trips);
        $this->assertSame(2, $trips[0]->crews()->count());
        $this->assertSame(2, $trips[1]->crews()->count());
        $this->assertNotSame($trips[0]->trip_reference, $trips[1]->trip_reference);
    }

    public function test_three_driver_date_groups_create_three_trips_with_same_partner_request_id(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $driverA = Driver::create(['name' => 'Driver A']);
        $driverB = Driver::create(['name' => 'Driver B']);
        $vessel = Vessel::withoutEvents(fn () => Vessel::create(['name' => 'Vessel A']));

        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        foreach ([
            [$driverA->id, '2026-08-21', 'Crew 1'],
            [$driverB->id, '2026-08-21', 'Crew 2'],
            [$driverA->id, '2026-08-22', 'Crew 3'],
        ] as [$driverId, $date, $name]) {
            PartnerRequestItem::create([
                'partner_request_id' => $request->id,
                'driver_id' => $driverId,
                'trip_date' => $date,
                'name' => $name,
                'from_location' => 'A',
                'to_location' => 'B',
                'vessel_id' => $vessel->id,
            ]);
        }

        $staff = $this->createStaff(['view_trips', 'create_trips']);
        $request = $this->approveRequest($request->fresh('items'), $staff);

        $crews = $request->items->map(fn ($item) => $this->tripCrewPayload($item, $item->driver_id, $vessel->id))->all();
        $this->actingAs($staff)->post(route('trips.store'), [
            'partner_request_id' => $request->id,
            'partner_id' => $partner->id,
            'crews' => $crews,
        ]);

        $trips = Trip::where('partner_request_id', $request->id)->get();
        $this->assertCount(3, $trips);
        $this->assertTrue($trips->every(fn ($trip) => $trip->partner_request_id === $request->id));
    }

    public function test_second_approval_attempt_after_approval_creates_zero_trips(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staff = $this->createStaff(['view_trips', 'create_trips']);

        $payload = ['request_version' => $this->requestVersion($request->fresh())];
        $this->actingAs($staff)->post(route('partner-requests.approve', $request), $payload);
        $this->actingAs($staff)->post(route('partner-requests.approve', $request->fresh()), $payload);

        $this->assertSame(0, Trip::where('partner_request_id', $request->id)->count());
    }

    public function test_trip_create_from_manual_request_is_prefilled(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staff = $this->createStaff(['view_trips', 'create_trips']);
        $request = $this->approveRequest($request, $staff);
        $item = $request->items->first();

        $this->actingAs($staff)
            ->get(route('trips.create-from-partner-request', $request))
            ->assertOk()
            ->assertSee('Source Request')
            ->assertSee($request->request_reference)
            ->assertSee($item->name)
            ->assertSee($item->from_location);
    }

    public function test_trip_create_from_image_zero_row_request_has_empty_crew_and_source_link(): void
    {
        Storage::fake('local');
        [$partner, $partnerUser] = $this->createPartnerContext();
        $path = 'partner-requests/'.$partner->id.'/empty.jpg';
        Storage::disk('local')->put($path, 'image');

        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
            'approved_at' => now(),
            'source_image_path' => $path,
            'extraction_status' => PartnerRequest::EXTRACTION_FAILED,
        ]);

        $staff = $this->createStaff(['view_trips', 'create_trips']);

        $this->actingAs($staff)
            ->get(route('trips.create-from-partner-request', $request))
            ->assertOk()
            ->assertSee('View Source Schedule')
            ->assertSee('name="crews[0][name]"', false);
    }

    public function test_trip_store_from_request_forces_partner_id_server_side(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $otherPartner = Partner::create(['title' => 'Other Partner']);
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staff = $this->createStaff(['view_trips', 'create_trips']);
        $request = $this->approveRequest($request, $staff);
        $item = $request->items->first();

        $this->actingAs($staff)->post(route('trips.store'), [
            'partner_request_id' => $request->id,
            'partner_id' => $otherPartner->id,
            'crews' => [$this->tripCrewPayload($item)],
        ]);

        $trip = Trip::where('partner_request_id', $request->id)->firstOrFail();
        $this->assertSame($partner->id, $trip->partner_id);
    }

    public function test_duplicate_conversion_creates_zero_extra_trips(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staff = $this->createStaff(['view_trips', 'create_trips']);
        $this->createTripsFromApprovedRequest($request, $staff);

        $item = $request->fresh('items')->items->first();
        $this->actingAs($staff)->post(route('trips.store'), [
            'partner_request_id' => $request->id,
            'partner_id' => $request->partner_id,
            'crews' => [$this->tripCrewPayload($item)],
        ])->assertRedirect(route('partner-requests.show', $request))
            ->assertSessionHas('error');

        $this->assertSame(1, Trip::where('partner_request_id', $request->id)->count());
    }

    public function test_approved_request_without_trips_shows_create_trip_action(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $staff = $this->createStaff(['view_trips', 'create_trips']);

        $this->actingAs($staff)
            ->get(route('partner-requests.show', $request))
            ->assertOk()
            ->assertSee('Approved — Awaiting Trip Creation')
            ->assertSee('Create Trip');
    }

    public function test_approved_request_with_trips_shows_linked_trps_without_create_trip(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $trip = Trip::create([
            'partner_id' => $partner->id,
            'partner_request_id' => $request->id,
            'trip_date' => '2026-08-21',
            'title' => 'Trip 1',
            'status' => 'assigned',
        ]);

        $staff = $this->createStaff(['view_trips']);

        $this->actingAs($staff)
            ->get(route('partner-requests.show', $request))
            ->assertOk()
            ->assertSee($trip->fresh()->trip_reference)
            ->assertDontSee('Create Trip');
    }

    public function test_driver_notification_occurs_during_trip_creation_not_approval(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $driver = Driver::create(['name' => 'Driver A', 'notification_token' => 'token']);
        $request = $this->createPendingRequest($partner, $partnerUser, 'manual', ['driver_id' => $driver->id]);
        $staff = $this->createStaff(['view_trips', 'create_trips']);

        $this->approveRequest($request, $staff);
        $this->assertSame(0, \App\Models\Notification::count());

        $item = $request->fresh('items')->items->first();
        $this->actingAs($staff)->post(route('trips.store'), [
            'partner_request_id' => $request->id,
            'partner_id' => $request->partner_id,
            'crews' => [$this->tripCrewPayload($item, $driver->id)],
        ]);

        $this->assertSame(1, \App\Models\Notification::where('driver_id', $driver->id)->count());
    }

    public function test_normal_internal_trip_store_without_partner_request_id(): void
    {
        $staff = $this->createStaff(['create_trips']);
        $vessel = Vessel::withoutEvents(fn () => Vessel::create(['name' => 'Vessel A']));

        $this->actingAs($staff)->post(route('trips.store'), [
            'crews' => [[
                'trip_date' => '2026-08-21',
                'pick_up_time' => '09:00',
                'name' => 'Crew',
                'from_location' => 'A',
                'to_location' => 'B',
                'vessel_id' => $vessel->id,
            ]],
        ])->assertRedirect(route('trips.index'));

        $trip = Trip::first();
        $this->assertNull($trip->partner_request_id);
    }

    public function test_decline_requires_reason_and_creates_no_trips(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staff = $this->createStaff(['view_trips', 'edit_trips']);

        $this->actingAs($staff)
            ->post(route('partner-requests.decline', $request), [
                'request_version' => $this->requestVersion($request),
                'decline_reason' => 'Transportation details need confirmation.',
            ])
            ->assertRedirect(route('partner-requests.show', $request));

        $request->refresh();
        $this->assertSame(PartnerRequest::STATUS_DECLINED, $request->status);
        $this->assertNotNull($request->declined_at);
        $this->assertSame('Transportation details need confirmation.', $request->decline_reason);
        $this->assertSame(0, Trip::count());
    }

    public function test_stale_review_snapshot_blocks_approve(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staleVersion = $this->requestVersion($request);
        $request->items()->first()->update(['name' => 'Changed After Load']);
        $staff = $this->createStaff(['view_trips', 'create_trips']);

        $this->actingAs($staff)
            ->post(route('partner-requests.approve', $request), [
                'request_version' => $staleVersion,
            ])
            ->assertSessionHas('error');

        $this->assertSame(PartnerRequest::STATUS_PENDING, $request->fresh()->status);
        $this->assertSame(0, Trip::count());
    }

    public function test_stale_approve_rejected_when_partner_changes_field(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staleVersion = $this->requestVersion($request);
        $item = $request->items->first();

        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [[
                    'id' => $item->id,
                    'trip_date' => '2026-08-26',
                    'name' => 'Partner Changed Name',
                    'from_location' => 'Port',
                    'to_location' => 'Airport',
                ]],
            ])
            ->assertRedirect(route('partner.requests.show', $request))
            ->assertSessionHas('success');

        $staff = $this->createStaff(['view_trips', 'create_trips']);
        Auth::guard('partner')->logout();
        $this->actingAs($staff, 'web')
            ->post(route('partner-requests.approve', $request), [
                'request_version' => $staleVersion,
            ])
            ->assertRedirect(route('partner-requests.show', $request))
            ->assertSessionHas('error');
    }

    public function test_stale_approve_rejected_when_partner_adds_item(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staleVersion = $this->requestVersion($request);
        $item = $request->items->first();

        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [
                    [
                        'id' => $item->id,
                        'trip_date' => '2026-08-21',
                        'name' => $item->name,
                        'from_location' => 'Port',
                        'to_location' => 'Airport',
                    ],
                    [
                        'trip_date' => '2026-08-22',
                        'name' => 'Partner Added Crew',
                        'from_location' => 'X',
                        'to_location' => 'Y',
                    ],
                ],
            ])
            ->assertRedirect(route('partner.requests.show', $request))
            ->assertSessionHas('success');

        $staff = $this->createStaff(['view_trips', 'create_trips']);
        Auth::guard('partner')->logout();
        $this->actingAs($staff, 'web')
            ->post(route('partner-requests.approve', $request), [
                'request_version' => $staleVersion,
            ])
            ->assertRedirect(route('partner-requests.show', $request))
            ->assertSessionHas('error');
    }

    public function test_stale_approve_rejected_when_partner_removes_item(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        PartnerRequestItem::create([
            'partner_request_id' => $request->id,
            'trip_date' => '2026-08-22',
            'name' => 'Second Crew',
            'from_location' => 'A',
            'to_location' => 'B',
        ]);
        $staleVersion = $this->requestVersion($request->fresh('items'));

        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [[
                    'id' => $request->items->first()->id,
                    'trip_date' => '2026-08-21',
                    'name' => 'Crew Member',
                    'from_location' => 'Port',
                    'to_location' => 'Airport',
                ]],
            ])
            ->assertRedirect(route('partner.requests.show', $request))
            ->assertSessionHas('success');

        $staff = $this->createStaff(['view_trips', 'create_trips']);
        Auth::guard('partner')->logout();
        $this->actingAs($staff, 'web')
            ->post(route('partner-requests.approve', $request), [
                'request_version' => $staleVersion,
            ])
            ->assertRedirect(route('partner-requests.show', $request))
            ->assertSessionHas('error');
    }

    public function test_stale_decline_does_not_change_status(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staleVersion = $this->requestVersion($request);
        $request->items()->first()->update(['remarks' => 'Changed']);
        $staff = $this->createStaff(['view_trips', 'edit_trips']);

        $this->actingAs($staff)
            ->post(route('partner-requests.decline', $request), [
                'request_version' => $staleVersion,
                'decline_reason' => 'Should not apply',
            ])
            ->assertSessionHas('error');

        $this->assertSame(PartnerRequest::STATUS_PENDING, $request->fresh()->status);
    }

    public function test_partner_cannot_edit_or_withdraw_after_approval(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
        ]);

        $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.edit', $request))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($partnerUser, 'partner')
            ->patch(route('partner.requests.withdraw', $request))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_internal_image_endpoint_security_and_preservation(): void
    {
        Storage::fake('local');
        [$partner, $partnerUser] = $this->createPartnerContext();
        $path = 'partner-requests/'.$partner->id.'/secure.jpg';
        Storage::disk('local')->put($path, 'bytes');

        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
            'submitted_at' => now(),
            'source_image_path' => $path,
        ]);

        $authorized = $this->createStaff(['view_trips']);
        $unauthorized = $this->createStaff();

        $this->actingAs($authorized)->get(route('partner-requests.image', $request))->assertOk();
        $this->actingAs($unauthorized)->get(route('partner-requests.image', $request))->assertRedirect(route('error.403'));

        auth()->logout();
        $this->get(route('partner-requests.image', $request))->assertRedirect(route('login'));

        $request->update(['status' => PartnerRequest::STATUS_DECLINED, 'decline_reason' => 'No']);
        Storage::disk('local')->assertExists($path);
    }

    public function test_partner_portal_shows_decline_reason_approved_zero_trips_message_and_trip_references(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $declined = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_DECLINED,
            'submitted_at' => now(),
            'decline_reason' => 'Need confirmation',
        ]);

        $approvedNoTrips = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $approvedWithTrip = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $trip = Trip::create([
            'partner_id' => $partner->id,
            'partner_request_id' => $approvedWithTrip->id,
            'trip_date' => '2026-08-21',
            'title' => 'Trip 1',
            'status' => 'assigned',
        ]);

        $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.show', $declined))
            ->assertSee('Need confirmation');

        $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.show', $approvedNoTrips))
            ->assertSee('Transportation scheduling is being prepared');

        $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.show', $approvedWithTrip))
            ->assertSee($trip->fresh()->trip_reference);
    }

    public function test_admin_permission_bypass_allows_approval_without_creating_trips(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('partner-requests.approve', $request), [
                'request_version' => $this->requestVersion($request),
            ])
            ->assertRedirect(route('trips.create-from-partner-request', $request))
            ->assertSessionHas('success');

        $this->assertSame(0, Trip::where('partner_request_id', $request->id)->count());
    }

    public function test_guest_cannot_access_create_from_partner_request(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $this->get(route('trips.create-from-partner-request', $request))
            ->assertRedirect(route('login'));
    }

    public function test_partner_user_cannot_access_create_from_partner_request(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $this->actingAs($partnerUser, 'partner')
            ->get(route('trips.create-from-partner-request', $request))
            ->assertRedirect(route('login'));
    }

    public function test_staff_without_create_trips_cannot_access_create_from_partner_request(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staff = $this->createStaff(['view_trips']);
        $request->update(['status' => PartnerRequest::STATUS_APPROVED, 'approved_at' => now()]);

        $this->actingAs($staff)
            ->get(route('trips.create-from-partner-request', $request))
            ->assertRedirect(route('error.403'));
    }

    public function test_create_from_partner_request_rejects_pending(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staff = $this->createStaff(['view_trips', 'create_trips']);

        $this->actingAs($staff)
            ->get(route('trips.create-from-partner-request', $request))
            ->assertNotFound();
    }

    public function test_create_from_partner_request_rejects_declined(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_DECLINED,
            'submitted_at' => now(),
            'declined_at' => now(),
            'decline_reason' => 'No',
        ]);
        $staff = $this->createStaff(['view_trips', 'create_trips']);

        $this->actingAs($staff)
            ->get(route('trips.create-from-partner-request', $request))
            ->assertNotFound();
    }

    public function test_create_from_partner_request_rejects_withdrawn(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_WITHDRAWN,
            'submitted_at' => now(),
            'withdrawn_at' => now(),
        ]);
        $staff = $this->createStaff(['view_trips', 'create_trips']);

        $this->actingAs($staff)
            ->get(route('trips.create-from-partner-request', $request))
            ->assertNotFound();
    }

    public function test_create_from_partner_request_redirects_when_already_converted(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);
        Trip::create([
            'partner_id' => $partner->id,
            'partner_request_id' => $request->id,
            'trip_date' => '2026-08-21',
            'title' => 'Existing Trip',
            'status' => 'assigned',
        ]);
        $staff = $this->createStaff(['view_trips', 'create_trips']);

        $this->actingAs($staff)
            ->get(route('trips.create-from-partner-request', $request))
            ->assertRedirect(route('partner-requests.show', $request))
            ->assertSessionHas('error');
    }

    public function test_manual_prefill_omits_operational_fields_not_submitted_by_partner(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $request->items()->first()->update(['pick_up_time' => '09:00:00']);
        $staff = $this->createStaff(['view_trips', 'create_trips']);
        $request = $this->approveRequest($request, $staff);

        $response = $this->actingAs($staff)
            ->get(route('trips.create-from-partner-request', $request));

        $response->assertOk()
            ->assertSee($request->items->first()->name)
            ->assertDontSee('value="09:00"', false);
    }
}
