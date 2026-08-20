<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\PartnerRequestItem;
use App\Models\PartnerUser;
use App\Models\Trip;
use App\Models\TripCrew;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 1 Partner Portal Tests
 * Tests database, models, relationships, unique references, and business logic
 */
class PartnerPortalPhase1Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('partner_request_items');
        Schema::dropIfExists('partner_requests');
        Schema::dropIfExists('partner_users');
        Schema::dropIfExists('trip_crews');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('vessels');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->tinyInteger('role')->default(1);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->boolean('is_default')->default(false);
            $table->boolean('allow_manual_submission')->default(false);
            $table->boolean('allow_image_submission')->default(false);
            $table->timestamps();
        });

        Schema::create('partner_users', function (Blueprint $table) {
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

        Schema::create('partner_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_reference')->unique()->nullable();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('restrict');
            $table->foreignId('partner_user_id')->nullable()->constrained('partner_users')->onDelete('set null');
            $table->string('submission_method');
            $table->string('status')->default('pending');
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
        });

        Schema::create('partner_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_request_id')->constrained('partner_requests')->onDelete('cascade');
            $table->date('trip_date')->nullable();
            $table->time('pick_up_time')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_2')->nullable();
            $table->text('address')->nullable();
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

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('type')->default(1);
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('vessels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_reference')->unique()->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->foreignId('partner_id')->nullable();
            $table->foreignId('partner_request_id')->nullable();
            $table->date('trip_date');
            $table->string('title')->nullable();
            $table->string('status')->default('assigned');
            $table->timestamps();
        });

        Schema::create('trip_crews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id');
            $table->foreignId('vessel_id')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
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
    }

    public function test_partner_can_have_multiple_partner_users(): void
    {
        $partner = Partner::create(['title' => 'Test Partner']);

        $user1 = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'User One',
            'email' => 'user1@partner.com',
            'password' => 'password',
        ]);

        $user2 = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'User Two',
            'email' => 'user2@partner.com',
            'password' => 'password',
        ]);

        $this->assertEquals(2, $partner->partnerUsers()->count());
        $this->assertTrue($partner->partnerUsers->contains($user1));
        $this->assertTrue($partner->partnerUsers->contains($user2));
    }

    public function test_partner_user_email_is_unique(): void
    {
        $partner1 = Partner::create(['title' => 'Partner 1']);
        $partner2 = Partner::create(['title' => 'Partner 2']);

        PartnerUser::create([
            'partner_id' => $partner1->id,
            'name' => 'User One',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        PartnerUser::create([
            'partner_id' => $partner2->id,
            'name' => 'User Two',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);
    }

    public function test_partner_request_automatically_gets_req_reference(): void
    {
        $partner = Partner::create(['title' => 'Test Partner']);

        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_PENDING,
        ]);

        $this->assertNotNull($request->fresh()->request_reference);
        $this->assertMatchesRegularExpression('/^REQ-\d{6}$/', $request->fresh()->request_reference);
    }

    public function test_req_reference_is_based_on_id_and_unique(): void
    {
        $partner = Partner::create(['title' => 'Test Partner']);

        $request1 = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
        ]);

        $request2 = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
        ]);

        $ref1 = $request1->fresh()->request_reference;
        $ref2 = $request2->fresh()->request_reference;

        $this->assertNotEquals($ref1, $ref2);
        $this->assertEquals(sprintf('REQ-%06d', $request1->id), $ref1);
        $this->assertEquals(sprintf('REQ-%06d', $request2->id), $ref2);
    }

    public function test_partner_request_can_contain_multiple_items(): void
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
        ]);

        $item1 = PartnerRequestItem::create([
            'partner_request_id' => $request->id,
            'name' => 'Crew 1',
            'trip_date' => now()->toDateString(),
        ]);

        $item2 = PartnerRequestItem::create([
            'partner_request_id' => $request->id,
            'name' => 'Crew 2',
            'trip_date' => now()->toDateString(),
        ]);

        $this->assertEquals(2, $request->items()->count());
        $this->assertTrue($request->items->contains($item1));
        $this->assertTrue($request->items->contains($item2));
    }

    public function test_partner_request_to_partner_relationship(): void
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
        ]);

        $this->assertEquals($partner->id, $request->partner->id);
        $this->assertTrue($partner->requests->contains($request));
    }

    public function test_partner_request_to_partner_user_relationship(): void
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'User One',
            'email' => 'user@partner.com',
            'password' => 'password',
        ]);

        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'partner_user_id' => $partnerUser->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
        ]);

        $this->assertEquals($partnerUser->id, $request->partnerUser->id);
    }

    public function test_partner_request_to_multiple_trips_relationship(): void
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
        ]);

        $trip1 = Trip::create([
            'partner_id' => $partner->id,
            'partner_request_id' => $request->id,
            'trip_date' => now()->toDateString(),
            'title' => 'Trip 1',
            'status' => TripCrew::STATUS_ASSIGNED,
        ]);

        $trip2 = Trip::create([
            'partner_id' => $partner->id,
            'partner_request_id' => $request->id,
            'trip_date' => now()->addDay()->toDateString(),
            'title' => 'Trip 2',
            'status' => TripCrew::STATUS_ASSIGNED,
        ]);

        $this->assertEquals(2, $request->trips()->count());
        $this->assertTrue($request->trips->contains($trip1));
        $this->assertTrue($request->trips->contains($trip2));
    }

    public function test_newly_created_trip_automatically_receives_trp_reference(): void
    {
        $trip = Trip::create([
            'trip_date' => now()->toDateString(),
            'title' => 'Test Trip',
            'status' => TripCrew::STATUS_ASSIGNED,
        ]);

        $this->assertNotNull($trip->fresh()->trip_reference);
        $this->assertMatchesRegularExpression('/^TRP-\d{6}$/', $trip->fresh()->trip_reference);
    }

    public function test_trip_reference_format_helper_works_correctly(): void
    {
        $trip = Trip::create([
            'trip_date' => now()->toDateString(),
            'title' => 'Test Trip',
        ]);

        $reference = $trip->fresh()->trip_reference;
        $expectedReference = sprintf('TRP-%06d', $trip->id);

        $this->assertEquals($expectedReference, $reference);
    }

    public function test_internal_trip_can_have_null_partner_request_id(): void
    {
        $trip = Trip::create([
            'trip_date' => now()->toDateString(),
            'title' => 'Manual Trip',
            'partner_request_id' => null,
        ]);

        $this->assertNull($trip->partner_request_id);
        $this->assertNull($trip->partnerRequest);
    }

    public function test_different_trips_can_belong_to_same_request(): void
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
        ]);

        $trip1 = Trip::create([
            'partner_request_id' => $request->id,
            'trip_date' => now()->toDateString(),
            'title' => 'Trip 1',
        ]);

        $trip2 = Trip::create([
            'partner_request_id' => $request->id,
            'trip_date' => now()->toDateString(),
            'title' => 'Trip 2',
        ]);

        $this->assertEquals($request->id, $trip1->partner_request_id);
        $this->assertEquals($request->id, $trip2->partner_request_id);
        $this->assertEquals($request->id, $trip1->partnerRequest->id);
        $this->assertEquals($request->id, $trip2->partnerRequest->id);
    }

    public function test_request_status_helpers_behave_correctly(): void
    {
        $partner = Partner::create(['title' => 'Test Partner']);

        $pendingRequest = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_PENDING,
        ]);

        $approvedRequest = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
        ]);

        $declinedRequest = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_DECLINED,
        ]);

        $withdrawnRequest = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_WITHDRAWN,
        ]);

        $this->assertTrue($pendingRequest->isPending());
        $this->assertFalse($pendingRequest->isApproved());
        $this->assertFalse($pendingRequest->isDeclined());
        $this->assertFalse($pendingRequest->isWithdrawn());

        $this->assertFalse($approvedRequest->isPending());
        $this->assertTrue($approvedRequest->isApproved());
        $this->assertFalse($approvedRequest->isDeclined());
        $this->assertFalse($approvedRequest->isWithdrawn());

        $this->assertFalse($declinedRequest->isPending());
        $this->assertFalse($declinedRequest->isApproved());
        $this->assertTrue($declinedRequest->isDeclined());
        $this->assertFalse($declinedRequest->isWithdrawn());

        $this->assertFalse($withdrawnRequest->isPending());
        $this->assertFalse($withdrawnRequest->isApproved());
        $this->assertFalse($withdrawnRequest->isDeclined());
        $this->assertTrue($withdrawnRequest->isWithdrawn());
    }

    public function test_can_partner_edit_is_true_for_pending_only(): void
    {
        $partner = Partner::create(['title' => 'Test Partner']);

        $pendingRequest = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_PENDING,
        ]);

        $approvedRequest = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
        ]);

        $declinedRequest = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_DECLINED,
        ]);

        $this->assertTrue($pendingRequest->canPartnerEdit());
        $this->assertFalse($approvedRequest->canPartnerEdit());
        $this->assertFalse($declinedRequest->canPartnerEdit());

        $imagePendingRequest = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
        ]);

        $this->assertFalse($imagePendingRequest->canPartnerEdit());
    }

    public function test_partner_submission_settings_cast_correctly_as_booleans(): void
    {
        $partner = Partner::create([
            'title' => 'Test Partner',
            'allow_manual_submission' => true,
            'allow_image_submission' => false,
        ]);

        $this->assertTrue($partner->allow_manual_submission);
        $this->assertFalse($partner->allow_image_submission);
        $this->assertIsBool($partner->allow_manual_submission);
        $this->assertIsBool($partner->allow_image_submission);
    }

    public function test_partner_request_items_relationships(): void
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        $driver = Driver::create(['name' => 'Test Driver', 'type' => Driver::TYPE_INTERNAL]);
        $vessel = Vessel::create(['name' => 'Test Vessel']);

        $request = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
        ]);

        $item = PartnerRequestItem::create([
            'partner_request_id' => $request->id,
            'name' => 'Crew 1',
            'vessel_name_raw' => 'Maersk Denver',
            'driver_id' => $driver->id,
            'vessel_id' => $vessel->id,
        ]);

        $this->assertEquals($request->id, $item->request->id);
        $this->assertEquals($driver->id, $item->driver->id);
        $this->assertEquals($vessel->id, $item->vessel->id);
        $this->assertEquals('Maersk Denver', $item->vessel_name_raw);
    }

    public function test_partner_with_requests_cannot_be_deleted(): void
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        
        PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        $partner->delete();
    }
}
