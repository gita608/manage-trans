<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\Trip;
use App\Models\TripCrew;
use App\Models\TripExpenseType;
use App\Models\TripIssueType;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverTripApiSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
    }

    protected function createSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'personal_access_tokens', 'trip_expenses', 'trip_expense_types',
            'trip_issues', 'trip_issue_types', 'activity_logs', 'trip_crews',
            'trips', 'partner_requests', 'partners', 'vessels', 'drivers', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('type')->default(1);
            $table->string('notification_token')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('role')->default(User::ROLE_STAFF);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('partner_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_reference')->nullable();
            $table->foreignId('partner_id');
            $table->string('submission_method')->default('manual');
            $table->string('status')->default('approved');
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
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->time('pick_up_time')->nullable();
            $table->timestamps();
        });

        Schema::create('trip_issue_types', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('trip_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id');
            $table->foreignId('driver_id');
            $table->foreignId('issue_type_id');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('trip_expense_types', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->json('input_types')->nullable();
            $table->timestamps();
        });

        Schema::create('trip_expenses', function (Blueprint $table) {
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

        Schema::create('personal_access_tokens', function (Blueprint $table) {
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

    protected function createAssignedTrip(Driver $driver, ?PartnerRequest $request = null): Trip
    {
        $partner = Partner::first() ?? Partner::create(['title' => 'Partner A']);

        $trip = Trip::create([
            'driver_id' => $driver->id,
            'partner_id' => $partner->id,
            'partner_request_id' => $request?->id,
            'trip_date' => now()->toDateString(),
            'title' => 'Trip 1',
            'status' => 'assigned',
        ]);

        $vessel = Vessel::create(['name' => 'Vessel A']);
        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Crew A',
            'from_location' => 'Port',
            'to_location' => 'Airport',
            'pick_up_time' => '09:00:00',
        ]);

        return $trip->fresh(['crews']);
    }

    public function test_driver_can_access_own_trip_but_not_other_driver_trip(): void
    {
        $driverA = Driver::create(['name' => 'Driver A']);
        $driverB = Driver::create(['name' => 'Driver B']);
        $tripA = $this->createAssignedTrip($driverA);
        $tripB = $this->createAssignedTrip($driverB);

        Sanctum::actingAs($driverA);
        $this->getJson(route('api.driver.trip.show', $tripA->id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.trip_reference', $tripA->trip_reference);

        $this->getJson(route('api.driver.trip.show', $tripB->id))
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_driver_can_update_own_trip_status_but_not_other_driver_trip(): void
    {
        $driverA = Driver::create(['name' => 'Driver A']);
        $driverB = Driver::create(['name' => 'Driver B']);
        $tripA = $this->createAssignedTrip($driverA);
        $tripB = $this->createAssignedTrip($driverB);

        Sanctum::actingAs($driverA);
        $this->putJson(route('api.driver.trip.update-status', $tripA->id), ['status' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $this->putJson(route('api.driver.trip.update-status', $tripB->id), ['status' => 'in_progress'])
            ->assertNotFound();
    }

    public function test_driver_can_update_own_crew_but_not_other_driver_crew(): void
    {
        $driverA = Driver::create(['name' => 'Driver A']);
        $driverB = Driver::create(['name' => 'Driver B']);
        $tripA = $this->createAssignedTrip($driverA);
        $tripB = $this->createAssignedTrip($driverB);
        $crewA = $tripA->crews->first();
        $crewB = $tripB->crews->first();

        Sanctum::actingAs($driverA);
        $this->putJson(route('api.driver.trip.update-crew', [$tripA->id, $crewA->id]), ['name' => 'Updated Crew'])
            ->assertOk()
            ->assertJsonPath('data.crew_information.name', 'Updated Crew');

        $this->putJson(route('api.driver.trip.update-crew', [$tripB->id, $crewB->id]), ['name' => 'Hacked'])
            ->assertNotFound();

        $this->putJson(route('api.driver.trip.update-crew', [$tripA->id, $crewB->id]), ['name' => 'Cross Crew'])
            ->assertNotFound();
    }

    public function test_driver_can_submit_issue_and_expense_only_for_own_trip(): void
    {
        $driverA = Driver::create(['name' => 'Driver A']);
        $driverB = Driver::create(['name' => 'Driver B']);
        $tripA = $this->createAssignedTrip($driverA);
        $tripB = $this->createAssignedTrip($driverB);

        $issueType = TripIssueType::create(['title' => 'Delay']);
        $expenseType = TripExpenseType::create([
            'title' => 'Fuel',
            'input_types' => ['amount'],
        ]);

        Sanctum::actingAs($driverA);
        $this->postJson(route('api.driver.trip.submit-issue', $tripA->id), [
            'issue_type_id' => $issueType->id,
            'description' => 'Traffic',
        ])->assertCreated();

        $this->postJson(route('api.driver.trip.submit-issue', $tripB->id), [
            'issue_type_id' => $issueType->id,
            'description' => 'Should fail',
        ])->assertNotFound();

        $this->postJson(route('api.driver.trip.submit-expense', $tripA->id), [
            'expense_type_id' => $expenseType->id,
            'amount' => 25,
        ])->assertCreated();

        $this->postJson(route('api.driver.trip.submit-expense', $tripB->id), [
            'expense_type_id' => $expenseType->id,
            'amount' => 99,
        ])->assertNotFound();
    }

    public function test_driver_api_access_flips_after_internal_reassignment(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
        ]);
        $driverA = Driver::create(['name' => 'Driver A']);
        $driverB = Driver::create(['name' => 'Driver B']);
        $partner = Partner::first() ?? Partner::create(['title' => 'Partner A']);
        $partnerRequest = PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
        ]);
        $trip = $this->createAssignedTrip($driverA, $partnerRequest);

        Sanctum::actingAs($driverA);
        $this->getJson(route('api.driver.trip.show', $trip->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        Sanctum::actingAs($driverB);
        $this->getJson(route('api.driver.trip.show', $trip->id))
            ->assertNotFound()
            ->assertJsonPath('success', false);

        $this->actingAs($admin, 'web')->patch(route('trips.assign-driver', $trip), [
            'driver_id' => $driverB->id,
        ])->assertRedirect(route('trips.index'));

        Sanctum::actingAs($driverA);
        $this->getJson(route('api.driver.trip.show', $trip->id))
            ->assertNotFound()
            ->assertJsonPath('success', false);

        Sanctum::actingAs($driverB);
        $this->getJson(route('api.driver.trip.show', $trip->id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.trip_reference', $trip->fresh()->trip_reference);
    }
}
