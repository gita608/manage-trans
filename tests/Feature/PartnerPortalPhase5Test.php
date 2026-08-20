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
            $table->foreignId('user_id');
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

    protected function completeItemPayload(PartnerRequestItem $item, ?int $driverId = null, ?int $vesselId = null): array
    {
        return [
            'id' => $item->id,
            'trip_date' => '2026-08-21',
            'pick_up_time' => '09:00',
            'name' => $item->name ?? 'Crew Member',
            'from_location' => 'Port',
            'to_location' => 'Airport',
            'driver_id' => $driverId,
            'vessel_id' => $vesselId ?? $item->vessel_id,
        ];
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

    public function test_internal_review_save_keeps_request_pending_and_does_not_create_trips(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staff = $this->createStaff(['view_trips', 'edit_trips']);

        $this->actingAs($staff)
            ->put(route('partner-requests.update', $request), [
                'request_version' => $this->requestVersion($request),
                'items' => [$this->completeItemPayload($request->items->first())],
            ])
            ->assertRedirect(route('partner-requests.show', $request));

        $this->assertSame(PartnerRequest::STATUS_PENDING, $request->fresh()->status);
        $this->assertSame(0, Trip::count());
    }

    public function test_image_request_with_zero_items_can_be_reviewed_and_items_added(): void
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

        $staff = $this->createStaff(['view_trips', 'edit_trips']);
        $vessel = Vessel::withoutEvents(fn () => Vessel::create(['name' => 'Vessel A']));

        $this->actingAs($staff)
            ->get(route('partner-requests.show', $request))
            ->assertOk()
            ->assertSee('Automatic extraction was unsuccessful');

        $this->actingAs($staff)
            ->put(route('partner-requests.update', $request), [
                'request_version' => $this->requestVersion($request),
                'items' => [[
                    'trip_date' => '2026-08-21',
                    'pick_up_time' => '10:00',
                    'name' => 'Manual Crew',
                    'from_location' => 'A',
                    'to_location' => 'B',
                    'vessel_id' => $vessel->id,
                ]],
            ]);

        $this->assertSame(1, $request->fresh()->items()->count());
    }

    public function test_staff_can_add_and_remove_crew_items_and_cannot_inject_foreign_item(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $requestA = $this->createPendingRequest($partner, $partnerUser);
        $requestB = $this->createPendingRequest($partner, $partnerUser);
        $foreignItem = $requestB->items->first();
        $staff = $this->createStaff(['view_trips', 'edit_trips']);
        $vessel = Vessel::withoutEvents(fn () => Vessel::create(['name' => 'Vessel B']));

        $this->actingAs($staff)
            ->put(route('partner-requests.update', $requestA), [
                'request_version' => $this->requestVersion($requestA),
                'items' => [
                    $this->completeItemPayload($requestA->items->first(), null, $vessel->id),
                    [
                        'id' => $foreignItem->id,
                        'trip_date' => '2026-08-22',
                        'pick_up_time' => '11:00',
                        'name' => 'Injected',
                        'from_location' => 'X',
                        'to_location' => 'Y',
                        'vessel_id' => $vessel->id,
                    ],
                ],
            ]);

        $this->assertSame(1, $requestA->fresh()->items()->count());
        $this->assertSame(1, $requestB->fresh()->items()->count());
    }

    public function test_non_pending_request_is_read_only(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
        ]);

        $staff = $this->createStaff(['view_trips', 'edit_trips', 'create_trips']);
        $this->actingAs($staff)
            ->put(route('partner-requests.update', $request), [
                'request_version' => $this->requestVersion($request),
                'items' => [],
            ])
            ->assertRedirect(route('partner-requests.show', $request))
            ->assertSessionHas('error');
    }

    public function test_approval_requires_create_and_edit_trips_permissions(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staff = $this->createStaff(['view_trips', 'edit_trips']);

        $this->actingAs($staff)
            ->post(route('partner-requests.approve', $request), [
                'request_version' => $this->requestVersion($request),
            ])
            ->assertRedirect(route('error.403'));
    }

    public function test_approval_validation_failure_leaves_request_pending_and_creates_no_trips(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $request->items()->update(['vessel_id' => null, 'pick_up_time' => null]);
        $staff = $this->createStaff(['view_trips', 'edit_trips', 'create_trips']);

        $this->actingAs($staff)
            ->post(route('partner-requests.approve', $request), [
                'request_version' => $this->requestVersion($request->fresh()),
            ])
            ->assertRedirect(route('partner-requests.show', $request))
            ->assertSessionHas('error');

        $this->assertSame(PartnerRequest::STATUS_PENDING, $request->fresh()->status);
        $this->assertSame(0, Trip::count());
    }

    public function test_two_trp_example_creates_two_trips_linked_to_same_request(): void
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
                'pick_up_time' => '09:00:00',
                'name' => $name,
                'from_location' => 'A',
                'to_location' => 'B',
                'vessel_id' => $vessel->id,
            ]);
        }

        $staff = $this->createStaff(['view_trips', 'edit_trips', 'create_trips']);

        $this->actingAs($staff)
            ->post(route('partner-requests.approve', $request), [
                'request_version' => $this->requestVersion($request->fresh()),
            ])
            ->assertRedirect(route('partner-requests.show', $request))
            ->assertSessionHas('success');

        $trips = Trip::where('partner_request_id', $request->id)->get();
        $this->assertCount(2, $trips);
        $this->assertSame(2, $trips[0]->crews()->count());
        $this->assertSame(2, $trips[1]->crews()->count());
        $this->assertNotSame($trips[0]->trip_reference, $trips[1]->trip_reference);
        $this->assertSame(PartnerRequest::STATUS_APPROVED, $request->fresh()->status);
    }

    public function test_second_approval_attempt_is_idempotent(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staff = $this->createStaff(['view_trips', 'edit_trips', 'create_trips']);

        $payload = ['request_version' => $this->requestVersion($request->fresh())];

        $this->actingAs($staff)->post(route('partner-requests.approve', $request), $payload);
        $this->actingAs($staff)->post(route('partner-requests.approve', $request->fresh()), $payload);

        $this->assertSame(1, Trip::where('partner_request_id', $request->id)->count());
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

    public function test_stale_review_snapshot_blocks_save_and_approve(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staleVersion = $this->requestVersion($request);
        $request->items()->first()->update(['name' => 'Changed After Load']);
        $staff = $this->createStaff(['view_trips', 'edit_trips', 'create_trips']);

        $this->actingAs($staff)
            ->put(route('partner-requests.update', $request), [
                'request_version' => $staleVersion,
                'items' => [$this->completeItemPayload($request->items->first())],
            ])
            ->assertSessionHas('error');

        $this->actingAs($staff)
            ->post(route('partner-requests.approve', $request), [
                'request_version' => $staleVersion,
            ])
            ->assertSessionHas('error');
    }

    public function test_stale_review_rejected_when_partner_changes_field(): void
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
            ]);

        $staff = $this->createStaff(['view_trips', 'edit_trips']);
        $this->actingAs($staff)
            ->put(route('partner-requests.update', $request), [
                'request_version' => $staleVersion,
                'items' => [$this->completeItemPayload($request->fresh()->items->first())],
            ])
            ->assertSessionHas('error');
    }

    public function test_stale_review_rejected_when_partner_adds_item(): void
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
            ]);

        $staff = $this->createStaff(['view_trips', 'edit_trips']);
        $this->actingAs($staff)
            ->put(route('partner-requests.update', $request), [
                'request_version' => $staleVersion,
                'items' => [$this->completeItemPayload($request->fresh()->items->first())],
            ])
            ->assertSessionHas('error');
    }

    public function test_stale_review_rejected_when_partner_removes_item(): void
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
            ]);

        $staff = $this->createStaff(['view_trips', 'edit_trips']);
        $this->actingAs($staff)
            ->put(route('partner-requests.update', $request), [
                'request_version' => $staleVersion,
                'items' => array_map(
                    fn ($item) => $this->completeItemPayload($item),
                    $request->fresh('items')->items->all()
                ),
            ])
            ->assertSessionHas('error');
    }

    public function test_stale_review_rejected_when_another_internal_reviewer_changes_crew_field(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staleVersion = $this->requestVersion($request);
        $reviewerA = $this->createStaff(['view_trips', 'edit_trips']);
        $reviewerB = $this->createStaff(['view_trips', 'edit_trips']);
        $item = $request->items->first();

        $this->actingAs($reviewerA)
            ->put(route('partner-requests.update', $request), [
                'request_version' => $this->requestVersion($request),
                'items' => [[
                    'id' => $item->id,
                    'trip_date' => '2026-08-21',
                    'pick_up_time' => '09:00',
                    'name' => 'Reviewer A Updated',
                    'from_location' => 'Port',
                    'to_location' => 'Airport',
                    'vessel_id' => $item->vessel_id,
                ]],
            ]);

        $this->actingAs($reviewerB)
            ->put(route('partner-requests.update', $request), [
                'request_version' => $staleVersion,
                'items' => [$this->completeItemPayload($request->fresh()->items->first())],
            ])
            ->assertSessionHas('error');
    }

    public function test_stale_review_rejected_when_another_internal_reviewer_adds_or_removes_item(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staleVersion = $this->requestVersion($request);
        $reviewerA = $this->createStaff(['view_trips', 'edit_trips']);
        $reviewerB = $this->createStaff(['view_trips', 'edit_trips']);
        $vessel = Vessel::withoutEvents(fn () => Vessel::create(['name' => 'Vessel C']));

        $this->actingAs($reviewerA)
            ->put(route('partner-requests.update', $request), [
                'request_version' => $this->requestVersion($request),
                'items' => [
                    $this->completeItemPayload($request->items->first(), null, $vessel->id),
                    [
                        'trip_date' => '2026-08-22',
                        'pick_up_time' => '10:00',
                        'name' => 'Added By Reviewer A',
                        'from_location' => 'X',
                        'to_location' => 'Y',
                        'vessel_id' => $vessel->id,
                    ],
                ],
            ]);

        $this->actingAs($reviewerB)
            ->put(route('partner-requests.update', $request), [
                'request_version' => $staleVersion,
                'items' => [$this->completeItemPayload($request->items->first(), null, $vessel->id)],
            ])
            ->assertSessionHas('error');
    }

    public function test_stale_save_review_rejected_when_request_becomes_approved(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staleVersion = $this->requestVersion($request);
        $request->update(['status' => PartnerRequest::STATUS_APPROVED, 'approved_at' => now()]);

        $staff = $this->createStaff(['view_trips', 'edit_trips']);
        $this->actingAs($staff)
            ->put(route('partner-requests.update', $request), [
                'request_version' => $staleVersion,
                'items' => [$this->completeItemPayload($request->items->first())],
            ])
            ->assertSessionHas('error');
    }

    public function test_stale_save_review_rejected_when_request_becomes_declined(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staleVersion = $this->requestVersion($request);
        $request->update([
            'status' => PartnerRequest::STATUS_DECLINED,
            'declined_at' => now(),
            'decline_reason' => 'No',
        ]);

        $staff = $this->createStaff(['view_trips', 'edit_trips']);
        $this->actingAs($staff)
            ->put(route('partner-requests.update', $request), [
                'request_version' => $staleVersion,
                'items' => [$this->completeItemPayload($request->items->first())],
            ])
            ->assertSessionHas('error');
    }

    public function test_stale_save_review_rejected_when_request_becomes_withdrawn(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staleVersion = $this->requestVersion($request);
        $request->update(['status' => PartnerRequest::STATUS_WITHDRAWN, 'withdrawn_at' => now()]);

        $staff = $this->createStaff(['view_trips', 'edit_trips']);
        $this->actingAs($staff)
            ->put(route('partner-requests.update', $request), [
                'request_version' => $staleVersion,
                'items' => [$this->completeItemPayload($request->items->first())],
            ])
            ->assertSessionHas('error');
    }

    public function test_stale_approve_creates_zero_trips(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $staleVersion = $this->requestVersion($request);
        $request->items()->first()->update(['remarks' => 'Changed']);
        $staff = $this->createStaff(['view_trips', 'edit_trips', 'create_trips']);

        $this->actingAs($staff)
            ->post(route('partner-requests.approve', $request), [
                'request_version' => $staleVersion,
            ])
            ->assertSessionHas('error');

        $this->assertSame(PartnerRequest::STATUS_PENDING, $request->fresh()->status);
        $this->assertSame(0, Trip::count());
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

    public function test_partner_portal_shows_decline_reason_and_approved_trip_references(): void
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

        $approved = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'submitted_at' => now(),
        ]);

        $trip = Trip::create([
            'partner_id' => $partner->id,
            'partner_request_id' => $approved->id,
            'trip_date' => '2026-08-21',
            'title' => 'Trip 1',
            'status' => 'assigned',
        ]);

        $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.show', $declined))
            ->assertSee('Need confirmation');

        $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.show', $approved))
            ->assertSee($trip->fresh()->trip_reference);
    }

    public function test_admin_permission_bypass_allows_approval(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $request = $this->createPendingRequest($partner, $partnerUser);
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('partner-requests.approve', $request), [
                'request_version' => $this->requestVersion($request),
            ])
            ->assertSessionHas('success');

        $this->assertSame(1, Trip::where('partner_request_id', $request->id)->count());
    }
}
