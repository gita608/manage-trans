<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\DriverCheckIn;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Minimal schema — full RefreshDatabase fails on historical SQLite trip migrations.
 */
class DriverCheckInDailyLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('driver_check_ins');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('settings');

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('type')->default(1);
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('brand')->nullable();
            $table->string('number')->nullable();
            $table->text('info')->nullable();
            $table->timestamps();
        });

        Schema::create('driver_check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id');
            $table->foreignId('vehicle_id');
            $table->date('check_in_date');
            $table->time('check_in_time');
            $table->timestamp('check_in_at');
            $table->decimal('start_km', 10, 2)->default(0);
            $table->timestamp('checked_out_at')->nullable();
            $table->string('status')->default('checked_in');
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

        DB::table('settings')->insert([
            'key' => 'check_in_auto_checkout_hours',
            'value' => '12',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('settings')->insert([
            'key' => 'app_timezone',
            'value' => 'Asia/Dubai',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-08-07 15:02:00', 'Asia/Dubai'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_single_session_gets_full_daily_allowance(): void
    {
        [$driver, $vehicle] = $this->makeDriverAndVehicle();

        Sanctum::actingAs($driver);

        $response = $this->postJson('/api/check-in', [
            'vehicle_id' => $vehicle->id,
            'date' => '2026-08-07',
            'time' => '15:02',
            'start_km' => 100,
        ])->assertCreated();

        $due = Carbon::parse($response->json('data.auto_checkout_at'));
        $this->assertTrue(
            $due->equalTo(Carbon::parse('2026-08-08 03:02:00', 'Asia/Dubai'))
            || $due->equalTo(Carbon::parse('2026-08-08 03:02:00', 'UTC')->timezone('Asia/Dubai'))
            || abs($due->getTimestamp() - Carbon::parse('2026-08-07 15:02:00', 'Asia/Dubai')->addHours(12)->getTimestamp()) < 2
        );
    }

    public function test_second_session_uses_remaining_not_fresh_12_hours(): void
    {
        [$driver, $vehicleA, $vehicleB] = $this->makeDriverAndTwoVehicles();

        // Session 1: 15:02:00 -> 15:03:04 = 64 seconds
        DriverCheckIn::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicleA->id,
            'check_in_date' => '2026-08-07',
            'check_in_time' => '15:02:00',
            'check_in_at' => Carbon::parse('2026-08-07 15:02:00', 'Asia/Dubai'),
            'start_km' => 10,
            'checked_out_at' => Carbon::parse('2026-08-07 15:03:04', 'Asia/Dubai'),
            'status' => DriverCheckIn::STATUS_CHECKED_OUT,
        ]);

        $this->assertSame(64, DriverCheckIn::usedSecondsForDriverDay($driver->id, '2026-08-07'));
        $this->assertSame((12 * 3600) - 64, DriverCheckIn::remainingSecondsForDriverDay($driver->id, '2026-08-07'));

        // New session starts immediately after (exact seconds)
        $session2Start = Carbon::parse('2026-08-07 15:03:04', 'Asia/Dubai');
        $session2 = DriverCheckIn::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicleB->id,
            'check_in_date' => '2026-08-07',
            'check_in_time' => '15:03:04',
            'check_in_at' => $session2Start,
            'start_km' => 11,
            'status' => DriverCheckIn::STATUS_CHECKED_IN,
        ]);

        $due = $session2->autoCheckoutDueAt();
        $expected = $session2Start->copy()->addSeconds((12 * 3600) - 64);
        $this->assertSame($expected->getTimestamp(), $due->getTimestamp());

        // Close at due and verify exact daily total
        Carbon::setTestNow($due);
        $session2->closeForAutoExpiry();
        $session2->refresh();

        $this->assertSame(12 * 3600, DriverCheckIn::usedSecondsForDriverDay($driver->id, '2026-08-07'));
    }

    public function test_vehicle_switch_preserves_remaining_allowance(): void
    {
        [$driver, $vehicleA, $vehicleB] = $this->makeDriverAndTwoVehicles();

        Sanctum::actingAs($driver);

        Carbon::setTestNow(Carbon::parse('2026-08-07 08:00:00', 'Asia/Dubai'));
        $this->postJson('/api/check-in', [
            'vehicle_id' => $vehicleA->id,
            'date' => '2026-08-07',
            'time' => '08:00',
            'start_km' => 1,
        ])->assertCreated();

        // Switch after 5 hours
        Carbon::setTestNow(Carbon::parse('2026-08-07 13:00:00', 'Asia/Dubai'));
        $response = $this->postJson('/api/check-in', [
            'vehicle_id' => $vehicleB->id,
            'date' => '2026-08-07',
            'time' => '13:00',
            'start_km' => 50,
        ])->assertCreated();

        $newSession = DriverCheckIn::find($response->json('data.id'));
        $due = $newSession->autoCheckoutDueAt();

        $this->assertSame(
            Carbon::parse('2026-08-07 20:00:00', 'Asia/Dubai')->getTimestamp(),
            $due->getTimestamp()
        );

        $closed = DriverCheckIn::where('vehicle_id', $vehicleA->id)->first();
        $this->assertSame(DriverCheckIn::STATUS_CHECKED_OUT, $closed->status);
        $this->assertSame(5 * 3600, $closed->durationSeconds());
    }

    public function test_rejects_check_in_when_daily_limit_exhausted(): void
    {
        [$driver, $vehicleA, $vehicleB] = $this->makeDriverAndTwoVehicles();

        DriverCheckIn::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicleA->id,
            'check_in_date' => '2026-08-07',
            'check_in_time' => '08:00:00',
            'check_in_at' => Carbon::parse('2026-08-07 08:00:00', 'Asia/Dubai'),
            'start_km' => 1,
            'checked_out_at' => Carbon::parse('2026-08-07 20:00:00', 'Asia/Dubai'),
            'status' => DriverCheckIn::STATUS_CHECKED_OUT,
        ]);

        Sanctum::actingAs($driver);
        Carbon::setTestNow(Carbon::parse('2026-08-07 21:00:00', 'Asia/Dubai'));

        $this->postJson('/api/check-in', [
            'vehicle_id' => $vehicleB->id,
            'date' => '2026-08-07',
            'time' => '21:00',
            'start_km' => 90,
        ])->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'You have reached the maximum 12 hours of check-in time for this duty day.',
            ]);

        $this->assertSame(1, DriverCheckIn::count());
    }

    public function test_current_lazy_auto_checkouts_at_cumulative_due_time(): void
    {
        [$driver, $vehicle] = $this->makeDriverAndVehicle();

        $checkIn = DriverCheckIn::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'check_in_date' => '2026-08-07',
            'check_in_time' => '08:00:00',
            'check_in_at' => Carbon::parse('2026-08-07 08:00:00', 'Asia/Dubai'),
            'start_km' => 1,
            'status' => DriverCheckIn::STATUS_CHECKED_IN,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-07 20:00:00', 'Asia/Dubai'));
        Sanctum::actingAs($driver);

        $this->getJson('/api/check-in/current')
            ->assertOk()
            ->assertJsonPath('data', null);

        $checkIn->refresh();
        $this->assertSame(DriverCheckIn::STATUS_CHECKED_OUT, $checkIn->status);
        $this->assertSame(
            Carbon::parse('2026-08-07 20:00:00', 'Asia/Dubai')->getTimestamp(),
            $checkIn->checked_out_at->getTimestamp()
        );
    }

    public function test_auto_checkout_expired_uses_per_session_due_at(): void
    {
        [$driver, $vehicleA, $vehicleB] = $this->makeDriverAndTwoVehicles();

        DriverCheckIn::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicleA->id,
            'check_in_date' => '2026-08-07',
            'check_in_time' => '15:02:00',
            'check_in_at' => Carbon::parse('2026-08-07 15:02:00', 'Asia/Dubai'),
            'start_km' => 1,
            'checked_out_at' => Carbon::parse('2026-08-07 15:03:04', 'Asia/Dubai'),
            'status' => DriverCheckIn::STATUS_CHECKED_OUT,
        ]);

        $active = DriverCheckIn::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicleB->id,
            'check_in_date' => '2026-08-07',
            'check_in_time' => '15:03:04',
            'check_in_at' => Carbon::parse('2026-08-07 15:03:04', 'Asia/Dubai'),
            'start_km' => 2,
            'status' => DriverCheckIn::STATUS_CHECKED_IN,
        ]);

        $due = $active->autoCheckoutDueAt();
        Carbon::setTestNow($due->copy()->addMinute());

        $closed = DriverCheckIn::autoCheckoutExpired();
        $this->assertSame(1, $closed);

        $active->refresh();
        $this->assertSame(DriverCheckIn::STATUS_CHECKED_OUT, $active->status);
        $this->assertSame($due->getTimestamp(), $active->checked_out_at->getTimestamp());
        $this->assertSame(12 * 3600, DriverCheckIn::usedSecondsForDriverDay($driver->id, '2026-08-07'));
    }

    public function test_separate_drivers_and_dates_have_independent_allowances(): void
    {
        [$driverA, $vehicle] = $this->makeDriverAndVehicle('Driver A', 'a@example.com');
        $driverB = Driver::create([
            'name' => 'Driver B',
            'type' => Driver::TYPE_INTERNAL,
            'email' => 'b@example.com',
            'password' => 'password',
        ]);

        DriverCheckIn::create([
            'driver_id' => $driverA->id,
            'vehicle_id' => $vehicle->id,
            'check_in_date' => '2026-08-07',
            'check_in_time' => '08:00:00',
            'check_in_at' => Carbon::parse('2026-08-07 08:00:00', 'Asia/Dubai'),
            'start_km' => 1,
            'checked_out_at' => Carbon::parse('2026-08-07 20:00:00', 'Asia/Dubai'),
            'status' => DriverCheckIn::STATUS_CHECKED_OUT,
        ]);

        $this->assertTrue(DriverCheckIn::dailyLimitReached($driverA->id, '2026-08-07'));
        $this->assertFalse(DriverCheckIn::dailyLimitReached($driverB->id, '2026-08-07'));
        $this->assertFalse(DriverCheckIn::dailyLimitReached($driverA->id, '2026-08-08'));
    }

    public function test_configurable_hours_other_than_twelve(): void
    {
        DB::table('settings')->where('key', 'check_in_auto_checkout_hours')->update(['value' => '8']);
        Cache::flush();

        [$driver, $vehicle] = $this->makeDriverAndVehicle();

        DriverCheckIn::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'check_in_date' => '2026-08-07',
            'check_in_time' => '08:00:00',
            'check_in_at' => Carbon::parse('2026-08-07 08:00:00', 'Asia/Dubai'),
            'start_km' => 1,
            'checked_out_at' => Carbon::parse('2026-08-07 12:00:00', 'Asia/Dubai'),
            'status' => DriverCheckIn::STATUS_CHECKED_OUT,
        ]);

        $this->assertSame(8 * 3600, DriverCheckIn::dailyLimitSeconds());
        $this->assertSame(4 * 3600, DriverCheckIn::remainingSecondsForDriverDay($driver->id, '2026-08-07'));
    }

    /**
     * @return array{0: Driver, 1: Vehicle}
     */
    private function makeDriverAndVehicle(string $name = 'Driver', string $email = 'driver@example.com'): array
    {
        $driver = Driver::create([
            'name' => $name,
            'type' => Driver::TYPE_INTERNAL,
            'email' => $email,
            'password' => 'password',
        ]);

        $vehicle = Vehicle::create([
            'name' => 'Vehicle 1',
            'brand' => 'Toyota',
            'number' => 'ABC-1',
        ]);

        return [$driver, $vehicle];
    }

    /**
     * @return array{0: Driver, 1: Vehicle, 2: Vehicle}
     */
    private function makeDriverAndTwoVehicles(): array
    {
        [$driver, $vehicleA] = $this->makeDriverAndVehicle();
        $vehicleB = Vehicle::create([
            'name' => 'Vehicle 2',
            'brand' => 'Nissan',
            'number' => 'ABC-2',
        ]);

        return [$driver, $vehicleA, $vehicleB];
    }
}
