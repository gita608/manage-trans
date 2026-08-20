<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\PartnerRequestItem;
use App\Models\PartnerUser;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PartnerPortalPhase3SimplifiedTest extends TestCase
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

        Schema::create('vessels', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
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
            $table->foreignId('vessel_id')->nullable()->constrained('vessels')->onDelete('set null');
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
    public function test_pickup_time_is_not_required()
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
                        // NO pick_up_time
                    ]
                ]
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(1, PartnerRequest::count());
    }

    /** @test */
    public function test_manual_request_submitted_without_pickup_time()
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
                    ]
                ]
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
                        // NO vessel_id
                    ]
                ]
            ]);

        $response->assertSessionHasNoErrors();
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
                    ]
                ]
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
                    ]
                ]
            ]);

        $response->assertSessionHasNoErrors();
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
                    ]
                ]
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
                        'vessel_id' => 9999, // Nonexistent
                    ]
                ]
            ]);

        $response->assertSessionHasErrors('items.0.vessel_id');
        $this->assertEquals(0, PartnerRequest::count());
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
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'driver_id' => 999, // Malicious attempt
                    ]
                ]
            ]);

        $item = PartnerRequestItem::first();
        $this->assertNull($item->driver_id);
    }

    /** @test */
    public function test_partner_cannot_submit_pick_up_time_via_crafted_request()
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
                        'pick_up_time' => '10:00', // Malicious attempt
                    ]
                ]
            ]);

        $item = PartnerRequestItem::first();
        $this->assertNull($item->pick_up_time);
    }

    /** @test */
    public function test_partner_cannot_submit_phone_2_via_crafted_request()
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
                        'phone_2' => '555-0000', // Malicious attempt
                    ]
                ]
            ]);

        $item = PartnerRequestItem::first();
        $this->assertNull($item->phone_2);
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
                        'address' => 'Malicious Address', // Malicious attempt
                    ]
                ]
            ]);

        $item = PartnerRequestItem::first();
        $this->assertNull($item->address);
    }

    /** @test */
    public function test_partner_cannot_submit_flight_number_via_crafted_request()
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
                        'flight_number' => 'FL123', // Malicious attempt
                    ]
                ]
            ]);

        $item = PartnerRequestItem::first();
        $this->assertNull($item->flight_number);
    }

    /** @test */
    public function test_partner_cannot_submit_remarks_via_crafted_request()
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
                        'remarks' => 'Malicious Remarks', // Malicious attempt
                    ]
                ]
            ]);

        $item = PartnerRequestItem::first();
        $this->assertNull($item->remarks);
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
                        'sub_remark' => 'Malicious Sub Remark', // Malicious attempt
                    ]
                ]
            ]);

        $item = PartnerRequestItem::first();
        $this->assertNull($item->sub_remark);
    }

    /** @test */
    public function test_editing_partner_fields_preserves_existing_internal_fields()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);
        
        // Create request
        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ]
                ]
            ]);

        $request = PartnerRequest::first();
        $item = $request->items()->first();

        // Simulate internal review populating internal fields
        $item->update([
            'pick_up_time' => '10:00:00',
            'phone_2' => '555-0000',
            'address' => 'Internal Address',
            'flight_number' => 'FL123',
            'remarks' => 'Internal Remarks',
            'sub_remark' => 'Internal Sub Remark',
            'driver_id' => 123,
            'vessel_name_raw' => 'ADNOC A08',
        ]);

        // Partner edits the request
        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [
                    [
                        'id' => $item->id,
                        'trip_date' => '2026-08-26',
                        'name' => 'Modified Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ]
                ]
            ]);

        // Verify internal fields are preserved
        $item->refresh();
        $this->assertEquals('10:00:00', $item->pick_up_time);
        $this->assertEquals('555-0000', $item->phone_2);
        $this->assertEquals('Internal Address', $item->address);
        $this->assertEquals('FL123', $item->flight_number);
        $this->assertEquals('Internal Remarks', $item->remarks);
        $this->assertEquals('Internal Sub Remark', $item->sub_remark);
        $this->assertEquals(123, $item->driver_id);
        $this->assertEquals('ADNOC A08', $item->vessel_name_raw);
    }

    /** @test */
    public function test_partner_can_change_vessel_id_while_req_is_pending()
    {
        [$partner, $partnerUser] = $this->createPartnerWithUser(true);
        $vessel1 = Vessel::create(['name' => 'Vessel 1']);
        $vessel2 = Vessel::create(['name' => 'Vessel 2']);
        
        // Create request with vessel 1
        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-25',
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                        'vessel_id' => $vessel1->id,
                    ]
                ]
            ]);

        $request = PartnerRequest::first();
        $item = $request->items()->first();
        $this->assertEquals($vessel1->id, $item->vessel_id);

        // Change to vessel 2
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
                    ]
                ]
            ]);

        $item->refresh();
        $this->assertEquals($vessel2->id, $item->vessel_id);
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
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ]
                ]
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
                        'name' => 'Test Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ]
                ]
            ]);

        $request = PartnerRequest::first();
        $item = $request->items()->first();

        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [
                    [
                        'id' => $item->id,
                        'trip_date' => '2026-08-26',
                        'name' => 'Modified Crew',
                        'from_location' => 'Location A',
                        'to_location' => 'Location B',
                    ]
                ]
            ]);

        $this->assertEquals(0, Trip::count());
    }
}
