<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\PartnerRequestItem;
use App\Models\PartnerUser;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vessel;
use App\Support\PartnerRequestReviewVersion;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PartnerPortalManualOperationalFieldsTest extends TestCase
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
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('partners', function ($table) {
            $table->id();
            $table->string('title');
            $table->boolean('is_default')->default(false);
            $table->boolean('allow_manual_submission')->default(true);
            $table->boolean('allow_image_submission')->default(false);
            $table->timestamps();
        });

        Schema::create('partner_users', function ($table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
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
            $table->foreignId('partner_id')->constrained('partners');
            $table->foreignId('partner_user_id')->nullable()->constrained('partner_users');
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

        Schema::create('vessels', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('drivers', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('partner_request_items', function ($table) {
            $table->id();
            $table->foreignId('partner_request_id')->constrained('partner_requests')->cascadeOnDelete();
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
            $table->foreignId('vessel_id')->nullable()->constrained('vessels')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('trips', function ($table) {
            $table->id();
            $table->string('trip_reference')->nullable();
            $table->foreignId('partner_request_id')->nullable();
            $table->foreignId('partner_id')->nullable();
            $table->string('title')->nullable();
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

    protected function createPartnerContext(): array
    {
        $partner = Partner::create([
            'title' => 'QA Partner',
            'allow_manual_submission' => true,
        ]);

        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'Partner User',
            'email' => 'partner@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        return [$partner, $partnerUser];
    }

    public function test_individual_manual_request_persists_operational_fields(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $vessel = Vessel::create(['name' => 'Vessel A']);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [[
                    'trip_date' => '2026-08-31',
                    'name' => 'Mohammed',
                    'phone' => '0501111111',
                    'phone_2' => '0551111111',
                    'vessel_id' => $vessel->id,
                    'pick_up_time' => '09:00',
                    'flight_number' => 'EK202',
                    'remarks' => 'Terminal 3 pickup',
                    'from_location' => 'Port Rashid',
                    'to_location' => 'Dubai Airport',
                ]],
            ])
            ->assertRedirect();

        $item = PartnerRequestItem::first();
        $this->assertSame('09:00:00', $item->pick_up_time);
        $this->assertSame('0551111111', $item->phone_2);
        $this->assertSame('EK202', $item->flight_number);
        $this->assertSame('Terminal 3 pickup', $item->remarks);
        $this->assertSame($vessel->id, $item->vessel_id);
    }

    public function test_operational_fields_remain_optional_on_create(): void
    {
        [, $partnerUser] = $this->createPartnerContext();

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [[
                    'trip_date' => '2026-08-31',
                    'name' => 'Ahmed',
                    'from_location' => 'Port Rashid',
                    'to_location' => 'Dubai Airport',
                ]],
            ])
            ->assertSessionHasNoErrors();

        $item = PartnerRequestItem::first();
        $this->assertNull($item->pick_up_time);
        $this->assertNull($item->phone_2);
        $this->assertNull($item->flight_number);
        $this->assertNull($item->remarks);
    }

    public function test_group_mode_supports_different_vessels_per_crew(): void
    {
        [, $partnerUser] = $this->createPartnerContext();
        $vesselA = Vessel::create(['name' => 'Vessel A']);
        $vesselB = Vessel::create(['name' => 'Vessel B']);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [
                    [
                        'trip_date' => '2026-08-31',
                        'name' => 'Mohammed',
                        'phone' => '0501111111',
                        'phone_2' => '0551111111',
                        'vessel_id' => $vesselA->id,
                        'pick_up_time' => '09:00',
                        'flight_number' => 'EK202',
                        'from_location' => 'Port Rashid',
                        'to_location' => 'Dubai Airport',
                    ],
                    [
                        'trip_date' => '2026-08-31',
                        'name' => 'Ahmed',
                        'phone' => '0502222222',
                        'phone_2' => '0552222222',
                        'vessel_id' => $vesselB->id,
                        'pick_up_time' => '10:30',
                        'flight_number' => 'FZ123',
                        'from_location' => 'Port Rashid',
                        'to_location' => 'Dubai Airport',
                    ],
                ],
            ])
            ->assertRedirect();

        $items = PartnerRequestItem::orderBy('id')->get();
        $this->assertCount(2, $items);
        $this->assertSame($vesselA->id, $items[0]->vessel_id);
        $this->assertSame($vesselB->id, $items[1]->vessel_id);
        $this->assertSame('09:00:00', $items[0]->pick_up_time);
        $this->assertSame('10:30:00', $items[1]->pick_up_time);
        $this->assertSame('EK202', $items[0]->flight_number);
        $this->assertSame('FZ123', $items[1]->flight_number);
    }

    public function test_pending_manual_request_can_edit_operational_fields(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $vessel = Vessel::create(['name' => 'Updated Vessel']);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [[
                    'trip_date' => '2026-08-31',
                    'name' => 'Mohammed',
                    'from_location' => 'Port Rashid',
                    'to_location' => 'Dubai Airport',
                ]],
            ]);

        $request = PartnerRequest::first();
        $item = $request->items()->first();

        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [[
                    'id' => $item->id,
                    'trip_date' => '2026-09-01',
                    'name' => 'Mohammed Updated',
                    'phone' => '0501111111',
                    'phone_2' => '0559999999',
                    'vessel_id' => $vessel->id,
                    'pick_up_time' => '11:15',
                    'flight_number' => 'EK999',
                    'remarks' => 'Updated remarks',
                    'from_location' => 'Port Rashid',
                    'to_location' => 'Dubai Airport',
                ]],
            ])
            ->assertRedirect();

        $item->refresh();
        $this->assertSame('11:15:00', $item->pick_up_time);
        $this->assertSame('0559999999', $item->phone_2);
        $this->assertSame('EK999', $item->flight_number);
        $this->assertSame('Updated remarks', $item->remarks);
        $this->assertSame($vessel->id, $item->vessel_id);
    }

    public function test_partner_edit_preserves_internal_only_fields(): void
    {
        [, $partnerUser] = $this->createPartnerContext();

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [[
                    'trip_date' => '2026-08-31',
                    'name' => 'Mohammed',
                    'from_location' => 'Port Rashid',
                    'to_location' => 'Dubai Airport',
                ]],
            ]);

        $request = PartnerRequest::first();
        $item = $request->items()->first();
        $item->update([
            'address' => 'Internal Address',
            'sub_remark' => 'Internal sub remark',
            'driver_id' => 99,
            'vessel_name_raw' => 'RAW NAME',
        ]);

        $this->actingAs($partnerUser, 'partner')
            ->put(route('partner.requests.update', $request), [
                'items' => [[
                    'id' => $item->id,
                    'trip_date' => '2026-09-01',
                    'name' => 'Mohammed Updated',
                    'from_location' => 'Port Rashid',
                    'to_location' => 'Dubai Airport',
                ]],
            ]);

        $item->refresh();
        $this->assertSame('Internal Address', $item->address);
        $this->assertSame('Internal sub remark', $item->sub_remark);
        $this->assertSame(99, $item->driver_id);
        $this->assertSame('RAW NAME', $item->vessel_name_raw);
    }

    public function test_partner_details_page_shows_manual_operational_fields(): void
    {
        [, $partnerUser] = $this->createPartnerContext();

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [[
                    'trip_date' => '2026-08-31',
                    'name' => 'Mohammed',
                    'phone_2' => '0551111111',
                    'pick_up_time' => '09:30',
                    'flight_number' => 'EK202',
                    'remarks' => 'Terminal 3 pickup',
                    'from_location' => 'Port Rashid',
                    'to_location' => 'Dubai Airport',
                ]],
            ]);

        $request = PartnerRequest::first();

        $this->actingAs($partnerUser, 'partner')
            ->get(route('partner.requests.show', $request))
            ->assertOk()
            ->assertSee('Pickup Time')
            ->assertSee('Phone Number 2')
            ->assertSee('Flight Details')
            ->assertSee('Terminal 3 pickup')
            ->assertSee('EK202');
    }

    public function test_internal_review_shows_manual_operational_fields(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $staff = User::create([
            'name' => 'Staff',
            'email' => 'staff@example.com',
            'password' => 'password',
            'role' => 1,
        ]);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [[
                    'trip_date' => '2026-08-31',
                    'name' => 'Mohammed',
                    'phone_2' => '0551111111',
                    'pick_up_time' => '09:30',
                    'flight_number' => 'EK202',
                    'remarks' => 'Terminal 3 pickup',
                    'from_location' => 'Port Rashid',
                    'to_location' => 'Dubai Airport',
                ]],
            ]);

        $request = PartnerRequest::first();

        $this->actingAs($staff, 'web')
            ->get(route('partner-requests.show', $request))
            ->assertOk()
            ->assertSee('Pickup Time')
            ->assertSee('Phone 2')
            ->assertSee('Flight Number')
            ->assertSee('Terminal 3 pickup');
    }

    public function test_manual_operational_fields_prefill_trip_creation(): void
    {
        [$partner, $partnerUser] = $this->createPartnerContext();
        $staff = User::create([
            'name' => 'Staff',
            'email' => 'staff@example.com',
            'password' => 'password',
            'role' => 1,
        ]);
        $vessel = Vessel::create(['name' => 'Vessel A']);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [[
                    'trip_date' => '2026-08-31',
                    'name' => 'Mohammed',
                    'phone_2' => '0551234567',
                    'vessel_id' => $vessel->id,
                    'pick_up_time' => '09:30',
                    'flight_number' => 'EK202',
                    'remarks' => 'Terminal 3 pickup',
                    'from_location' => 'Port Rashid',
                    'to_location' => 'Dubai Airport',
                ]],
            ]);

        $request = PartnerRequest::first()->fresh('items');

        $this->actingAs($staff, 'web')
            ->post(route('partner-requests.approve', $request), [
                'request_version' => PartnerRequestReviewVersion::make($request),
            ])
            ->assertRedirect(route('trips.create-from-partner-request', $request));

        $response = $this->actingAs($staff, 'web')
            ->get(route('trips.create-from-partner-request', $request));

        $response->assertOk();
        $prefillCrews = $response->viewData('prefillCrews');
        $this->assertSame('09:30', $prefillCrews[0]['pick_up_time']);
        $this->assertSame('0551234567', $prefillCrews[0]['phone_2']);
        $this->assertSame('EK202', $prefillCrews[0]['flight_number']);
        $this->assertSame('Terminal 3 pickup', $prefillCrews[0]['remarks']);
        $this->assertSame($vessel->id, (int) $prefillCrews[0]['vessel_id']);
        $this->assertSame(0, Trip::count());
    }

    public function test_approve_action_has_no_confirmation_modal(): void
    {
        [, $partnerUser] = $this->createPartnerContext();
        $staff = User::create([
            'name' => 'Staff',
            'email' => 'staff@example.com',
            'password' => 'password',
            'role' => 1,
        ]);

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [[
                    'trip_date' => '2026-08-31',
                    'name' => 'Mohammed',
                    'from_location' => 'Port Rashid',
                    'to_location' => 'Dubai Airport',
                ]],
            ]);

        $request = PartnerRequest::first();

        $this->actingAs($staff, 'web')
            ->get(route('partner-requests.show', $request))
            ->assertOk()
            ->assertSee('btn-review-approve')
            ->assertDontSee('id="approveModal"');
    }

    public function test_crafted_partner_id_cannot_create_request_for_another_partner(): void
    {
        $otherPartner = Partner::create([
            'title' => 'Other Partner',
            'allow_manual_submission' => true,
        ]);

        [, $partnerUser] = $this->createPartnerContext();

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'partner_id' => $otherPartner->id,
                'items' => [[
                    'trip_date' => '2026-08-31',
                    'name' => 'Mohammed',
                    'from_location' => 'Port Rashid',
                    'to_location' => 'Dubai Airport',
                ]],
            ]);

        $request = PartnerRequest::first();
        $this->assertNotSame($otherPartner->id, $request->partner_id);
        $this->assertSame($partnerUser->partner_id, $request->partner_id);
    }

    public function test_internal_only_fields_cannot_be_mass_assigned_by_partner_input(): void
    {
        [, $partnerUser] = $this->createPartnerContext();

        $this->actingAs($partnerUser, 'partner')
            ->post(route('partner.requests.store'), [
                'items' => [[
                    'trip_date' => '2026-08-31',
                    'name' => 'Mohammed',
                    'from_location' => 'Port Rashid',
                    'to_location' => 'Dubai Airport',
                    'driver_id' => 77,
                    'address' => 'Hidden Address',
                    'sub_remark' => 'Hidden Sub Remark',
                    'vessel_name_raw' => 'Hidden Raw Vessel',
                ]],
            ]);

        $item = PartnerRequestItem::first();
        $this->assertNull($item->driver_id);
        $this->assertNull($item->address);
        $this->assertNull($item->sub_remark);
        $this->assertNull($item->vessel_name_raw);
    }
}
