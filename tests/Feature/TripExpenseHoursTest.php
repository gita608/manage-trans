<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Trip;
use App\Models\TripCrew;
use App\Models\TripExpense;
use App\Models\TripExpenseType;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Uses a minimal in-memory schema because full RefreshDatabase fails on an
 * existing SQLite-incompatible historical trips migration.
 */
class TripExpenseHoursTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('trip_expenses');
        Schema::dropIfExists('trip_expense_types');
        Schema::dropIfExists('trip_issues');
        Schema::dropIfExists('trip_issue_types');
        Schema::dropIfExists('trip_crews');
        Schema::dropIfExists('vessels');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('drivers');

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('type')->default(1);
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('license_number')->nullable();
            $table->string('contact')->nullable();
            $table->integer('age')->nullable();
            $table->string('photo')->nullable();
            $table->string('notification_token')->nullable();
            $table->decimal('total_kilometers', 12, 2)->default(0);
            $table->rememberToken();
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

        Schema::create('vessels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('trip_crews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id');
            $table->foreignId('vessel_id')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_2')->nullable();
            $table->text('address')->nullable();
            $table->time('pick_up_time')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->text('remarks')->nullable();
            $table->string('sub_remark')->nullable();
            $table->string('flight_number')->nullable();
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

    public function test_submit_hours_only_expense_and_trip_details_totals(): void
    {
        $driver = Driver::create([
            'name' => 'Driver A',
            'type' => Driver::TYPE_INTERNAL,
            'email' => 'driver-a@example.com',
            'password' => 'password',
        ]);

        $trip = Trip::create([
            'driver_id' => $driver->id,
            'trip_date' => now()->toDateString(),
            'title' => 'Test Trip',
            'status' => TripCrew::STATUS_ASSIGNED,
        ]);

        $hoursType = TripExpenseType::create([
            'title' => 'Overtime',
            'input_types' => ['hours', 'text'],
        ]);

        $amountType = TripExpenseType::create([
            'title' => 'Fuel',
            'input_types' => ['amount'],
        ]);

        $bothType = TripExpenseType::create([
            'title' => 'Allowance',
            'input_types' => ['amount', 'hours', 'text'],
        ]);

        Sanctum::actingAs($driver);

        $hoursOne = $this->postJson("/api/trips/{$trip->id}/expenses", [
            'expense_type_id' => $hoursType->id,
            'hours' => 2,
            'description' => 'Waiting',
        ]);
        $hoursOne->assertCreated();

        $this->postJson("/api/trips/{$trip->id}/expenses", [
            'expense_type_id' => $hoursType->id,
            'hours' => 3.5,
            'description' => 'Extra wait',
        ])->assertCreated();

        $this->postJson("/api/trips/{$trip->id}/expenses", [
            'expense_type_id' => $amountType->id,
            'amount' => 100,
        ])->assertCreated();

        $this->postJson("/api/trips/{$trip->id}/expenses", [
            'expense_type_id' => $bothType->id,
            'amount' => 50,
            'hours' => 1.25,
            'description' => 'Mixed duty',
        ])->assertCreated();

        $this->postJson("/api/trips/{$trip->id}/expenses", [
            'expense_type_id' => $hoursType->id,
            'description' => 'Missing hours',
        ])->assertStatus(422);

        $this->postJson("/api/trips/{$trip->id}/expenses", [
            'expense_type_id' => $amountType->id,
        ])->assertStatus(422);

        $response = $this->getJson("/api/trips/{$trip->id}")->assertOk();

        $response->assertJsonPath('data.expenses.total', 4);
        $response->assertJsonPath('data.expenses.total_amount', 150);
        $response->assertJsonPath('data.expenses.total_hours', 6.75);

        $expenses = $response->json('data.expenses.data');
        $this->assertTrue(collect($expenses)->every(fn ($e) => array_key_exists('hours', $e)));
        $this->assertTrue(collect($expenses)->every(fn ($e) => array_key_exists('amount', $e)));

        $this->assertEquals(150.0, (float) TripExpense::where('trip_id', $trip->id)->sum('amount'));
        $this->assertEquals(6.75, (float) TripExpense::where('trip_id', $trip->id)->sum('hours'));
    }

    public function test_null_hours_do_not_break_totals(): void
    {
        $driver = Driver::create([
            'name' => 'Driver B',
            'type' => Driver::TYPE_INTERNAL,
            'email' => 'driver-b@example.com',
            'password' => 'password',
        ]);

        $trip = Trip::create([
            'driver_id' => $driver->id,
            'trip_date' => now()->toDateString(),
            'title' => 'Null Hours Trip',
            'status' => TripCrew::STATUS_ASSIGNED,
        ]);

        $amountType = TripExpenseType::create([
            'title' => 'Fuel',
            'input_types' => ['amount'],
        ]);

        TripExpense::create([
            'trip_id' => $trip->id,
            'driver_id' => $driver->id,
            'expense_type_id' => $amountType->id,
            'amount' => 40,
            'hours' => null,
            'description' => null,
            'receipt' => null,
        ]);

        Sanctum::actingAs($driver);

        $this->getJson("/api/trips/{$trip->id}")
            ->assertOk()
            ->assertJsonPath('data.expenses.total_amount', 40)
            ->assertJsonPath('data.expenses.total_hours', 0);
    }
}
