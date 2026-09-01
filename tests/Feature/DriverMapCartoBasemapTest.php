<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\DriverLocation;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DriverMapCartoBasemapTest extends TestCase
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
            'notifications', 'driver_check_ins', 'driver_locations', 'vehicles', 'trip_crews',
            'trips', 'partners', 'vessels', 'drivers', 'users', 'activity_logs', 'settings',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

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

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('role')->default(User::ROLE_ADMIN);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('type')->default(Driver::TYPE_INTERNAL);
            $table->string('contact')->nullable();
            $table->string('photo')->nullable();
            $table->string('notification_token')->nullable();
            $table->timestamps();
        });

        Schema::create('driver_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('number')->nullable();
            $table->text('info')->nullable();
            $table->timestamps();
        });

        Schema::create('driver_check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id');
            $table->foreignId('vehicle_id')->nullable();
            $table->date('check_in_date');
            $table->time('check_in_time');
            $table->timestamp('check_in_at')->nullable();
            $table->decimal('start_km', 10, 2)->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->string('status')->default('checked_in');
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
            $table->foreignId('vessel_id')->nullable();
            $table->string('name')->nullable();
            $table->time('pick_up_time')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
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

    protected function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_driver_map_renders_with_carto_key_configured(): void
    {
        Config::set('services.carto.basemap_key', 'test_carto_basemap_key_12345');

        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('drivers.map'));

        $response->assertOk();
        $html = $response->getContent();

        // Key is serialized into JS via @json
        $this->assertStringContainsString('"test_carto_basemap_key_12345"', $html);

        // Updated CARTO raster URLs
        $this->assertStringContainsString('https://{s}.basemaps.cartocdn.com/rastertiles/dark_all/{z}/{x}/{y}{r}.png', $html);
        $this->assertStringContainsString('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', $html);

        // Attribution
        $this->assertStringContainsString('https://www.openstreetmap.org/copyright', $html);
        $this->assertStringContainsString('https://carto.com/attributions', $html);

        // Esri Light and OSM basemaps remain intact
        $this->assertStringContainsString('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{x}/{y}', $html);
        $this->assertStringContainsString('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', $html);

        // Theme selector has active options when key is configured
        $this->assertStringContainsString('<option value="dark">Dark</option>', $html);
        $this->assertStringContainsString('<option value="voyager">Voyager</option>', $html);
    }

    public function test_driver_map_handles_missing_carto_key_safely(): void
    {
        Config::set('services.carto.basemap_key', null);

        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('drivers.map'));

        $response->assertOk();
        $html = $response->getContent();

        // null serialized
        $this->assertStringContainsString('const cartoBasemapKey = null;', $html);

        // Dark and Voyager disabled/hidden in selector
        $this->assertStringContainsString('<option value="dark" disabled hidden>Dark</option>', $html);
        $this->assertStringContainsString('<option value="voyager" disabled hidden>Voyager</option>', $html);

        // Light and OSM remain available
        $this->assertStringContainsString('<option value="light">Light</option>', $html);
        $this->assertStringContainsString('<option value="osm">OpenStreetMap</option>', $html);
    }

    public function test_carto_key_comes_from_config_rather_than_hardcoded_source(): void
    {
        $servicesConfig = File::get(config_path('services.php'));
        $this->assertStringContainsString("'carto'", $servicesConfig);
        $this->assertStringContainsString("env('CARTO_BASEMAP_API_KEY')", $servicesConfig);

        $mapBlade = File::get(resource_path('views/drivers/map.blade.php'));
        // Must use config() instead of env() directly in Blade
        $this->assertStringContainsString("config('services.carto.basemap_key')", $mapBlade);
        $this->assertStringNotContainsString("env('CARTO_BASEMAP_API_KEY')", $mapBlade);
    }

    public function test_driver_locations_endpoint_is_unchanged(): void
    {
        $admin = $this->createAdmin();

        $driver = Driver::create([
            'name' => 'John Doe',
            'type' => Driver::TYPE_INTERNAL,
            'contact' => '+971500000000',
        ]);

        DriverLocation::create([
            'driver_id' => $driver->id,
            'latitude' => 25.2048,
            'longitude' => 55.2708,
        ]);

        $response = $this->actingAs($admin)->get(route('api.drivers.locations'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'drivers' => [
                    [
                        'id' => $driver->id,
                        'name' => 'John Doe',
                        'latitude' => 25.2048,
                        'longitude' => 55.2708,
                        'is_busy' => false,
                        'contact' => '+971500000000',
                    ],
                ],
            ]);
    }

    public function test_tileerror_fallback_and_saved_theme_validation_present(): void
    {
        $mapBlade = File::get(resource_path('views/drivers/map.blade.php'));

        // Tile error fallback console warning exists and doesn't log the API key
        $this->assertStringContainsString("console.warn('CARTO basemap unavailable; falling back to Light.');", $mapBlade);
        $this->assertStringNotContainsString("console.warn('CARTO basemap unavailable; falling back to Light.' + cartoBasemapKey)", $mapBlade);

        // Fallback to light when theme not available
        $this->assertStringContainsString('if (!mapThemes[savedTheme])', $mapBlade);
        $this->assertStringContainsString("savedTheme = 'light';", $mapBlade);
        $this->assertStringContainsString("localStorage.setItem('mapTheme', 'light');", $mapBlade);
    }
}
