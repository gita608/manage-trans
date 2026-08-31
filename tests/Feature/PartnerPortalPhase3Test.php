<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\PartnerRequestItem;
use App\Models\PartnerUser;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PartnerPortalPhase3Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Define schema for all necessary tables
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
            $table->boolean('allow_manual_submission')->default(false);
            $table->boolean('allow_image_submission')->default(false);
            $table->timestamps();
        });

        Schema::create('partner_users', function ($table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('partner_requests', function ($table) {
            $table->id();
            $table->string('request_reference')->unique()->nullable();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('restrict');
            $table->foreignId('partner_user_id')->nullable()->constrained('partner_users')->onDelete('set null');
            $table->string('submission_method');
            $table->string('status');
            $table->string('source_image_path')->nullable();
            $table->string('extraction_status')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('partner_updated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('declined_at')->nullable();
            $table->foreignId('declined_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('decline_reason')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            $table->index('partner_id');
            $table->index('status');
            $table->index('submission_method');
        });

        Schema::create('partner_request_items', function ($table) {
            $table->id();
            $table->foreignId('partner_request_id')->constrained('partner_requests')->onDelete('cascade');
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

        Schema::create('vessels', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('trips', function ($table) {
            $table->id();
            $table->string('trip_reference')->unique()->nullable();
            $table->foreignId('partner_request_id')->nullable()->constrained('partner_requests')->onDelete('set null');
            $table->foreignId('partner_id')->nullable();
            $table->string('title')->nullable();
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

    protected function createPartnerWithUser($manualSubmission = true)
    {
        $partner = Partner::create([
            'title' => 'Test Partner',
            'allow_manual_submission' => $manualSubmission,
        ]);

        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'John Doe',
            'email' => 'john@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        return [$partner, $partnerUser];
    }

    /** @test */
    public function test_partner_with_manual_submission_enabled_can_access_create_page()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $response = $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.create'));

        $response->assertOk();
    }

    /** @test */
    public function test_partner_with_manual_submission_disabled_cannot_access_create_page()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(false);

        $response = $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.create'));

        $response->assertRedirect(route('partner.requests.index'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function test_partner_with_manual_submission_disabled_cannot_post_manually()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(false);

        $response = $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $response->assertRedirect(route('partner.requests.index'));
        $response->assertSessionHas('error');
        $this->assertEquals(0, PartnerRequest::count());
    }

    /** @test */
    public function test_manual_store_creates_one_partner_request()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $response = $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $this->assertEquals(1, PartnerRequest::count());
        $request = PartnerRequest::first();
        $response->assertRedirect(route('partner.requests.show', $request));
    }

    /** @test */
    public function test_request_automatically_receives_req_reference()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $this->assertNotNull($request->request_reference);
        $this->assertStringStartsWith('REQ-', $request->request_reference);
    }

    /** @test */
    public function test_created_request_status_is_pending()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $this->assertEquals(PartnerRequest::STATUS_PENDING, $request->status);
    }

    /** @test */
    public function test_submission_method_is_manual()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $this->assertEquals(PartnerRequest::METHOD_MANUAL, $request->submission_method);
    }

    /** @test */
    public function test_partner_id_comes_from_authenticated_user()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $this->assertEquals($partner->id, $request->partner_id);
    }

    /** @test */
    public function test_posted_malicious_partner_id_is_ignored()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);
        $otherPartner = Partner::create(['title' => 'Other Partner']);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'partner_id' => $otherPartner->id, // Malicious attempt
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $this->assertEquals($partner->id, $request->partner_id);
        $this->assertNotEquals($otherPartner->id, $request->partner_id);
    }

    /** @test */
    public function test_partner_user_id_records_authenticated_submitter()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $this->assertEquals($partnerUser->id, $request->partner_user_id);
    }

    /** @test */
    public function test_submitted_at_is_populated()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $this->assertNotNull($request->submitted_at);
    }

    /** @test */
    public function test_multiple_request_items_can_be_submitted()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Crew 1',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                    [
                        'trip_date' => '2026-08-26',
                        'pick_up_time' => '14:00',
                        'name' => 'Crew 2',
                        'from_location' => 'Location C',
                        'to_location' => 'Location D',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $this->assertEquals(2, $request->items()->count());
    }

    /** @test */
    public function test_minimum_one_item_required()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $response = $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [],
            ]);

        $response->assertSessionHasErrors('items');
        $this->assertEquals(0, PartnerRequest::count());
    }

    /** @test */
    public function test_core_required_manual_fields_validated()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $response = $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        // Missing all required fields
                    ],
                ],
            ]);

        $response->assertSessionHasErrors([
            'items.0.trip_date',
            'items.0.name',
            'items.0.from_location',
            'items.0.to_location',
        ]);
    }

    /** @test */
    public function test_partner_cannot_submit_driver_id()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'driver_id' => 999, // Malicious attempt
                    ],
                ],
            ]);

        $item = PartnerRequestItem::first();
        $this->assertNull($item->driver_id);
    }

    /** @test */
    public function test_partner_cannot_submit_vessel_id()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        // Now vessel_id IS allowed, but must exist
        $response = $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'vessel_id' => 999, // Invalid - does not exist
                    ],
                ],
            ]);

        // Should be rejected by validation
        $response->assertSessionHasErrors('items.0.vessel_id');
        $this->assertEquals(0, PartnerRequestItem::count());
    }

    /** @test */
    public function test_partner_cannot_submit_status_approved()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'status' => PartnerRequest::STATUS_APPROVED, // Malicious attempt
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $this->assertEquals(PartnerRequest::STATUS_PENDING, $request->status);
    }

    /** @test */
    public function test_partner_a_cannot_view_partner_b_req()
    {
        [$partnerA, $userA] = $this->createPartnerWithUser(true);

        $partnerB = Partner::create(['title' => 'Partner B', 'allow_manual_submission' => true]);
        $userB = PartnerUser::create([
            'partner_id' => $partnerB->id,
            'name' => 'Jane Doe',
            'email' => 'jane@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        // User B creates a request
        $this->actingAs($userB, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::where('partner_id', $partnerB->id)->first();

        // User A tries to view it
        $response = $this->actingAs($userA, 'partner')
            ->get(route('partner.requests.show', ['partnerRequest' => $request->id]));

        $response->assertNotFound();
    }

    /** @test */
    public function test_partner_a_cannot_edit_partner_b_req()
    {
        [$partnerA, $userA] = $this->createPartnerWithUser(true);

        $partnerB = Partner::create(['title' => 'Partner B', 'allow_manual_submission' => true]);
        $userB = PartnerUser::create([
            'partner_id' => $partnerB->id,
            'name' => 'Jane Doe',
            'email' => 'jane@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        // User B creates a request
        $this->actingAs($userB, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();

        // User A tries to edit it
        $response = $this->actingAs($userA, 'partner')
            ->get(route('partner.requests.edit', $request));

        $response->assertNotFound();
    }

    /** @test */
    public function test_partner_a_cannot_update_partner_b_req()
    {
        [$partnerA, $userA] = $this->createPartnerWithUser(true);

        $partnerB = Partner::create(['title' => 'Partner B', 'allow_manual_submission' => true]);
        $userB = PartnerUser::create([
            'partner_id' => $partnerB->id,
            'name' => 'Jane Doe',
            'email' => 'jane@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        // User B creates a request
        $this->actingAs($userB, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $item = $request->items()->first();

        // User A tries to update it
        $response = $this->actingAs($userA, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [
                    [
                        'id' => $item->id,
                        'trip_date' => '2026-08-26',
                        'pick_up_time' => '11:00',
                        'name' => 'Modified Crew',
                        'from_location' => 'Location X',
                        'to_location' => 'Location Y',
                    ],
                ],
            ]);

        $response->assertNotFound();
    }

    /** @test */
    public function test_partner_a_cannot_withdraw_partner_b_req()
    {
        [$partnerA, $userA] = $this->createPartnerWithUser(true);

        $partnerB = Partner::create(['title' => 'Partner B', 'allow_manual_submission' => true]);
        $userB = PartnerUser::create([
            'partner_id' => $partnerB->id,
            'name' => 'Jane Doe',
            'email' => 'jane@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        // User B creates a request
        $this->actingAs($userB, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();

        // User A tries to withdraw it
        $response = $this->actingAs($userA, 'partner')
            ->patch(route('partner.requests.withdraw', $request));

        $response->assertNotFound();
    }

    /** @test */
    public function test_another_user_under_same_partner_can_view_company_req()
    {
        [$partner, $user1] = $this->createPartnerWithUser(true);

        $user2 = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'Jane Doe',
            'email' => 'jane@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        // User 1 creates a request
        $this->actingAs($user1, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();

        // User 2 can view it
        $response = $this->actingAs($user2, 'partner')
            ->get(route('partner.requests.show', $request));

        $response->assertOk();
    }

    /** @test */
    public function test_another_user_under_same_partner_can_edit_pending_company_req()
    {
        [$partner, $user1] = $this->createPartnerWithUser(true);

        $user2 = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'Jane Doe',
            'email' => 'jane@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        // User 1 creates a request
        $this->actingAs($user1, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();

        // User 2 can edit it
        $response = $this->actingAs($user2, 'partner')
            ->get(route('partner.requests.edit', $request));

        $response->assertOk();
    }

    /** @test */
    public function test_pending_request_can_be_edited()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $this->assertTrue($request->canPartnerEdit());

        $response = $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.edit', $request));

        $response->assertOk();
    }

    /** @test */
    public function test_editing_changes_partner_updated_at()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $item = $request->items()->first();
        $this->assertNull($request->partner_updated_at);

        sleep(1);

        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [
                    [
                        'id' => $item->id,
                        'trip_date' => '2026-08-26',
                        'pick_up_time' => '11:00',
                        'name' => 'Modified Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request->refresh();
        $this->assertNotNull($request->partner_updated_at);
    }

    /** @test */
    public function test_editing_does_not_change_submitted_at()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $item = $request->items()->first();
        $originalSubmittedAt = $request->submitted_at;

        sleep(1);

        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [
                    [
                        'id' => $item->id,
                        'trip_date' => '2026-08-26',
                        'pick_up_time' => '11:00',
                        'name' => 'Modified Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request->refresh();
        $this->assertEquals($originalSubmittedAt->timestamp, $request->submitted_at->timestamp);
    }

    /** @test */
    public function test_editing_does_not_change_original_partner_user_id()
    {
        [$partner, $user1] = $this->createPartnerWithUser(true);

        $user2 = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'Jane Doe',
            'email' => 'jane@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        // User 1 creates request
        $this->actingAs($user1, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $item = $request->items()->first();
        $this->assertEquals($user1->id, $request->partner_user_id);

        // User 2 edits it
        $this->actingAs($user2, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [
                    [
                        'id' => $item->id,
                        'trip_date' => '2026-08-26',
                        'pick_up_time' => '11:00',
                        'name' => 'Modified Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request->refresh();
        $this->assertEquals($user1->id, $request->partner_user_id);
    }

    /** @test */
    public function test_editing_existing_item_preserves_driver_id()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $item = $request->items()->first();

        // Simulate internal review setting driver_id
        $item->update(['driver_id' => 123]);

        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [
                    [
                        'id' => $item->id,
                        'trip_date' => '2026-08-26',
                        'pick_up_time' => '11:00',
                        'name' => 'Modified Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $item->refresh();
        $this->assertEquals(123, $item->driver_id);
    }

    /** @test */
    public function test_editing_existing_item_preserves_vessel_id()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        // Create vessel
        $vessel = Vessel::create(['name' => 'Test Vessel']);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'vessel_id' => $vessel->id,
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $item = $request->items()->first();

        // Partner edits and keeps same vessel
        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [
                    [
                        'id' => $item->id,
                        'trip_date' => '2026-08-26',
                        'name' => 'Modified Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'vessel_id' => $vessel->id, // Explicitly keeps vessel
                    ],
                ],
            ]);

        $item->refresh();
        $this->assertEquals($vessel->id, $item->vessel_id);
    }

    /** @test */
    public function test_new_rows_can_be_added_during_edit()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $item = $request->items()->first();
        $this->assertEquals(1, $request->items()->count());

        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [
                    [
                        'id' => $item->id,
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                    [
                        // New row without ID
                        'trip_date' => '2026-08-26',
                        'pick_up_time' => '14:00',
                        'name' => 'New Crew',
                        'from_location' => 'Location C',
                        'to_location' => 'Location D',
                    ],
                ],
            ]);

        $request->refresh();
        $this->assertEquals(2, $request->items()->count());
    }

    /** @test */
    public function test_explicitly_removed_rows_are_removed_only_from_that_request()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Crew 1',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                    [
                        'trip_date' => '2026-08-26',
                        'pick_up_time' => '14:00',
                        'name' => 'Crew 2',
                        'from_location' => 'Location C',
                        'to_location' => 'Location D',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $items = $request->items()->get();
        $item1 = $items[0];

        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [
                    [
                        'id' => $item1->id,
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Crew 1',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                    // Item 2 is not included, so it should be deleted
                ],
            ]);

        $request->refresh();
        $this->assertEquals(1, $request->items()->count());
    }

    /** @test */
    public function test_item_id_belonging_to_another_req_cannot_be_injected_into_update()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        // Create two requests
        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Request 1 Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-26',
                        'pick_up_time' => '14:00',
                        'name' => 'Request 2 Crew',
                        'from_location' => 'Location C',
                        'to_location' => 'Location D',
                    ],
                ],
            ]);

        $request1 = PartnerRequest::first();
        $request2 = PartnerRequest::skip(1)->first();
        $item1 = $request1->items()->first();
        $item2 = $request2->items()->first();

        // Try to inject item2's ID into request1 update
        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request1), [
                'items' => [
                    [
                        'id' => $item2->id, // Malicious injection
                        'trip_date' => '2026-08-27',
                        'pick_up_time' => '16:00',
                        'name' => 'Hacked Crew',
                        'from_location' => 'Location X',
                        'to_location' => 'Location Y',
                    ],
                ],
            ]);

        // Item2 should remain unchanged
        $item2->refresh();
        $this->assertEquals('Request 2 Crew', $item2->name);
    }

    /** @test */
    public function test_approved_request_cannot_be_edited()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $request->update(['status' => PartnerRequest::STATUS_APPROVED]);

        $this->assertFalse($request->canPartnerEdit());

        $response = $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.edit', $request));

        $response->assertRedirect(route('partner.requests.show', $request));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function test_declined_request_cannot_be_edited()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $request->update(['status' => PartnerRequest::STATUS_DECLINED]);

        $this->assertFalse($request->canPartnerEdit());

        $response = $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.edit', $request));

        $response->assertRedirect(route('partner.requests.show', $request));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function test_withdrawn_request_cannot_be_edited()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $request->update(['status' => PartnerRequest::STATUS_WITHDRAWN]);

        $this->assertFalse($request->canPartnerEdit());

        $response = $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.edit', $request));

        $response->assertRedirect(route('partner.requests.show', $request));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function test_pending_request_can_be_withdrawn()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $this->assertTrue($request->isPending());

        $response = $this->actingAs($partnerUser, 'partner')
            ->patch(route('partner.requests.withdraw', $request));

        $response->assertRedirect(route('partner.requests.show', $request));
        $request->refresh();
        $this->assertTrue($request->isWithdrawn());
    }

    /** @test */
    public function test_withdrawal_preserves_req_and_items()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $itemCount = $request->items()->count();

        $this->actingAs($partnerUser, 'partner')
            ->patch(route('partner.requests.withdraw', $request));

        $this->assertEquals(1, PartnerRequest::count());
        $request->refresh();
        $this->assertEquals($itemCount, $request->items()->count());
    }

    /** @test */
    public function test_withdrawn_at_populated()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $this->assertNull($request->withdrawn_at);

        $this->actingAs($partnerUser, 'partner')
            ->patch(route('partner.requests.withdraw', $request));

        $request->refresh();
        $this->assertNotNull($request->withdrawn_at);
    }

    /** @test */
    public function test_withdrawn_request_cannot_be_withdrawn_again()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();

        $this->actingAs($partnerUser, 'partner')
            ->patch(route('partner.requests.withdraw', $request));

        $request->refresh();
        $this->assertTrue($request->isWithdrawn());

        $response = $this->actingAs($partnerUser, 'partner')
            ->patch(route('partner.requests.withdraw', $request));

        $response->assertRedirect(route('partner.requests.show', $request));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function test_my_requests_contains_only_authenticated_partner_company_reqs()
    {
        [$partnerA, $userA] = $this->createPartnerWithUser(true);

        $partnerB = Partner::create(['title' => 'Partner B', 'allow_manual_submission' => true]);
        $userB = PartnerUser::create([
            'partner_id' => $partnerB->id,
            'name' => 'Jane Doe',
            'email' => 'jane@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        // User A creates a request
        $this->actingAs($userA, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Crew A',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $requestA = PartnerRequest::where('partner_id', $partnerA->id)->first();

        // User B creates a request
        $this->actingAs($userB, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-26',
                        'pick_up_time' => '14:00',
                        'name' => 'Crew B',
                        'from_location' => 'Location C',
                        'to_location' => 'Location D',
                    ],
                ],
            ]);

        $requestB = PartnerRequest::where('partner_id', $partnerB->id)->first();

        // User A should only see their partner's request
        $response = $this->actingAs($userA, 'partner')
            ->get(route('partner.requests.index'));

        $response->assertOk();
        $response->assertSee($requestA->request_reference);
        $response->assertDontSee($requestB->request_reference);
    }

    /** @test */
    public function test_status_filter_works()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        // Create pending request
        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Pending Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $pendingRequest = PartnerRequest::where('status', PartnerRequest::STATUS_PENDING)->first();

        // Create approved request
        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-26',
                        'pick_up_time' => '14:00',
                        'name' => 'Approved Crew',
                        'from_location' => 'Location C',
                        'to_location' => 'Location D',
                    ],
                ],
            ]);

        $approvedRequest = PartnerRequest::where('partner_id', $partner->id)
            ->where('status', PartnerRequest::STATUS_PENDING)
            ->skip(1)
            ->first();
        $approvedRequest->update(['status' => PartnerRequest::STATUS_APPROVED]);

        // Filter for pending
        $response = $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.index', ['status' => 'pending']));

        $response->assertOk();
        $response->assertSee($pendingRequest->request_reference);
        $response->assertDontSee($approvedRequest->request_reference);

        // Filter for approved
        $response = $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.index', ['status' => 'approved']));

        $response->assertOk();
        $response->assertSee($approvedRequest->request_reference);
        $response->assertDontSee($pendingRequest->request_reference);
    }

    /** @test */
    public function test_my_requests_is_paginated()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        // Create 20 requests
        for ($i = 1; $i <= 20; $i++) {
            $this->actingAs($partnerUser, 'partner')
                ->post(route('partner.requests.store'), [
                    'items' => [
                        [
                            'trip_date' => '2026-08-25',
                            'pick_up_time' => '10:00',
                            'name' => "Crew $i",
                            'from_location' => 'Location A',
                            'to_location' => 'Location B',
                        ],
                    ],
                ]);
        }

        $response = $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.index'));

        $response->assertOk();
        // Should see pagination links
        $response->assertSee('page=2');
    }

    /** @test */
    public function test_request_detail_displays_linked_trp_references_if_relationship_exists()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();

        // Create a linked trip
        $trip = Trip::create([
            'trip_reference' => 'TRP-000001',
            'partner_request_id' => $request->id,
            'title' => 'Test Trip',
        ]);

        $response = $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.show', $request));

        $response->assertOk();
        $response->assertSee('TRP-000001');
    }

    /** @test */
    public function test_creating_partner_req_creates_zero_trip_records()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->assertEquals(0, Trip::count());

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $this->assertEquals(1, PartnerRequest::count());
        $this->assertEquals(0, Trip::count());
    }

    /** @test */
    public function test_updating_partner_req_creates_zero_trip_records()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'pick_up_time' => '10:00',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $item = $request->items()->first();

        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [
                    [
                        'id' => $item->id,
                        'trip_date' => '2026-08-26',
                        'pick_up_time' => '11:00',
                        'name' => 'Modified Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $this->assertEquals(0, Trip::count());
    }

    // ========================================
    // NEW SIMPLIFIED FORM TESTS
    // ========================================

    /** @test */
    public function test_pickup_time_is_no_longer_required()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $response = $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        // No pick_up_time
                    ],
                ],
            ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertEquals(1, PartnerRequest::count());
    }

    /** @test */
    public function test_manual_request_can_be_submitted_without_pickup_time()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $item = PartnerRequestItem::first();
        $this->assertNull($item->pick_up_time);
    }

    /** @test */
    public function test_vessel_is_optional()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $response = $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        // No vessel_id
                    ],
                ],
            ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertEquals(1, PartnerRequest::count());
    }

    /** @test */
    public function test_request_with_no_vessel_saves_vessel_id_null()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $item = PartnerRequestItem::first();
        $this->assertNull($item->vessel_id);
    }

    /** @test */
    public function test_valid_vessel_can_be_selected()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);
        $vessel = Vessel::create(['name' => 'Test Vessel']);

        $response = $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'vessel_id' => $vessel->id,
                    ],
                ],
            ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertEquals(1, PartnerRequest::count());
    }

    /** @test */
    public function test_selected_vessel_id_is_stored_correctly()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);
        $vessel = Vessel::create(['name' => 'Test Vessel']);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'vessel_id' => $vessel->id,
                    ],
                ],
            ]);

        $item = PartnerRequestItem::first();
        $this->assertEquals($vessel->id, $item->vessel_id);
    }

    /** @test */
    public function test_invalid_vessel_id_is_rejected()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $response = $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'vessel_id' => 99999, // Non-existent vessel
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('items.0.vessel_id');
        $this->assertEquals(0, PartnerRequest::count());
    }

    /** @test */
    public function test_partner_can_submit_pick_up_time()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'pick_up_time' => '10:00',
                    ],
                ],
            ]);

        $item = PartnerRequestItem::first();
        $this->assertSame('10:00:00', $item->pick_up_time);
    }

    /** @test */
    public function test_partner_can_submit_phone_2()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'phone_2' => '1234567890',
                    ],
                ],
            ]);

        $item = PartnerRequestItem::first();
        $this->assertSame('1234567890', $item->phone_2);
    }

    /** @test */
    public function test_partner_cannot_submit_address_via_crafted_request()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'address' => '123 Main St', // Crafted attempt
                    ],
                ],
            ]);

        $item = PartnerRequestItem::first();
        $this->assertNull($item->address);
    }

    /** @test */
    public function test_partner_can_submit_flight_number()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'flight_number' => 'FL123',
                    ],
                ],
            ]);

        $item = PartnerRequestItem::first();
        $this->assertSame('FL123', $item->flight_number);
    }

    /** @test */
    public function test_partner_can_submit_remarks()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'remarks' => 'Some remark',
                    ],
                ],
            ]);

        $item = PartnerRequestItem::first();
        $this->assertSame('Some remark', $item->remarks);
    }

    /** @test */
    public function test_partner_cannot_submit_sub_remark_via_crafted_request()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'sub_remark' => 'Sub remark', // Crafted attempt
                    ],
                ],
            ]);

        $item = PartnerRequestItem::first();
        $this->assertNull($item->sub_remark);
    }

    /** @test */
    public function test_editing_partner_fields_preserves_existing_internal_fields()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $item = $request->items()->first();

        // Simulate internal review filling internal fields
        $item->update([
            'pick_up_time' => '10:00:00',
            'phone_2' => '9876543210',
            'address' => '456 Internal St',
            'flight_number' => 'FL999',
            'remarks' => 'Internal remark',
            'sub_remark' => 'Internal sub remark',
            'driver_id' => 123,
            'vessel_name_raw' => 'RAW VESSEL NAME',
        ]);

        // Partner edits only their allowed fields
        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [
                    [
                        'id' => $item->id,
                        'trip_date' => '2026-08-26',
                        'name' => 'Modified Crew',
                        'from_location' => 'Location C',
                        'to_location' => 'Location D',
                    ],
                ],
            ]);

        $item->refresh();
        // Partner editable fields should be updated
        $this->assertEquals('2026-08-26', $item->trip_date->format('Y-m-d'));
        $this->assertEquals('Modified Crew', $item->name);

        // Internal-only fields should be preserved
        $this->assertEquals('456 Internal St', $item->address);
        $this->assertEquals('Internal sub remark', $item->sub_remark);
        $this->assertEquals(123, $item->driver_id);
        $this->assertEquals('RAW VESSEL NAME', $item->vessel_name_raw);
        $this->assertNull($item->pick_up_time);
        $this->assertNull($item->phone_2);
        $this->assertNull($item->flight_number);
        $this->assertNull($item->remarks);
    }

    /** @test */
    public function test_partner_can_change_vessel_id_while_req_is_pending()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);
        $vessel1 = Vessel::create(['name' => 'Vessel 1']);
        $vessel2 = Vessel::create(['name' => 'Vessel 2']);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'vessel_id' => $vessel1->id,
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $item = $request->items()->first();
        $this->assertEquals($vessel1->id, $item->vessel_id);

        // Change vessel
        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [
                    [
                        'id' => $item->id,
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'vessel_id' => $vessel2->id,
                    ],
                ],
            ]);

        $item->refresh();
        $this->assertEquals($vessel2->id, $item->vessel_id);
    }

    /** @test */
    public function test_manual_create_page_contains_group_entry_modes_and_buttons()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $response = $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.create'));

        $response->assertOk();
        $response->assertSee('Individual Entry');
        $response->assertSee('Group / Bulk Entry');
        $response->assertSee('Add Row');
        $response->assertSee('Add 5 Rows');
    }

    /** @test */
    public function test_group_submission_with_10_items_creates_one_req_and_ten_items()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);
        $vessel = Vessel::create(['name' => 'Bulk Vessel']);

        $items = [];
        for ($i = 0; $i < 10; $i++) {
            $items[] = [
                'trip_date' => '2026-09-01',
                'name' => "Crew Member {$i}",
                'phone' => "123456789{$i}",
                'from_location' => 'Bulk Location A',
                'to_location' => 'Bulk Location B',
                'vessel_id' => $vessel->id,
            ];
        }

        $response = $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'entry_mode' => 'group',
                'items' => $items,
            ]);

        $this->assertEquals(1, PartnerRequest::count());
        $request = PartnerRequest::first();
        $this->assertEquals(10, $request->items()->count());
        $response->assertRedirect(route('partner.requests.show', $request));

        // Verify common details persisted correctly to all items
        foreach ($request->items as $item) {
            $this->assertEquals('2026-09-01', $item->trip_date->format('Y-m-d'));
            $this->assertEquals('Bulk Location A', $item->from_location);
            $this->assertEquals('Bulk Location B', $item->to_location);
            $this->assertEquals($vessel->id, $item->vessel_id);

            // Name/Phone remain per crew
            $this->assertStringStartsWith('Crew Member ', $item->name);
        }

        // No trips are created from Group Manual submission
        $this->assertEquals(0, Trip::count());
    }

    /** @test */
    public function test_group_submission_records_partner_and_cannot_inject_spoofed_fields()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);

        $response = $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'entry_mode' => 'group',
                'partner_id' => 999, // Malicious
                'status' => 'approved', // Malicious
                'items' => [
                    [
                        'trip_date' => '2026-09-01',
                        'name' => 'Crew 1',
                        'from_location' => 'Loc A',
                        'to_location' => 'Loc B',
                        'driver_id' => 888, // Malicious
                        'pick_up_time' => '10:00', // Malicious
                    ],
                ],
            ]);

        $request = PartnerRequest::first();
        $this->assertEquals($partner->id, $request->partner_id);
        $this->assertEquals(PartnerRequest::STATUS_PENDING, $request->status);

        $item = $request->items()->first();
        $this->assertNull($item->driver_id);
        $this->assertSame('10:00:00', $item->pick_up_time);
    }
}
