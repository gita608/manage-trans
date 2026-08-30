<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Permission;
use App\Models\Trip;
use App\Models\TripCrew;
use App\Models\User;
use App\Models\Vessel;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Minimal schema — full RefreshDatabase fails on historical SQLite trip migrations.
 */
class ReportExportPresentationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
    }

    public function test_driver_performance_export_uses_driver_name_without_avatar_initial(): void
    {
        $admin = $this->createAdmin();
        $driver = Driver::create([
            'name' => 'John Smith',
            'type' => Driver::TYPE_INTERNAL,
        ]);

        Trip::create([
            'driver_id' => $driver->id,
            'trip_date' => '2026-08-30',
            'title' => 'Trip 1',
            'status' => TripCrew::STATUS_ASSIGNED,
        ]);

        $html = $this->actingAs($admin)
            ->get(route('reports.driver-performance', [
                'date_from' => '2026-08-01',
                'date_to' => '2026-08-31',
            ]))
            ->assertOk()
            ->assertSee('JOHN SMITH', false)
            ->getContent();

        $this->assertStringContainsString('data-export="JOHN SMITH"', $html);
        $this->assertStringContainsString('data-export-name', $html);
        $this->assertStringContainsString('function driverPerformanceExportValue', $html);
        $this->assertStringContainsString("node.hasAttribute('data-export')", $html);

        $cell = $this->driverNameExportCell($html);
        $this->assertNotNull($cell);
        $this->assertSame('JOHN SMITH', $cell['export']);
        $this->assertSame('JOHN SMITH', $cell['named']);
        $this->assertStringContainsString('J', $cell['full_text']);
        $this->assertNotSame('JOHN SMITH', preg_replace('/\s+/', '', $cell['full_text']));
        $this->assertFalse(str_starts_with($cell['export'], 'JJOHN'));
        $this->assertStringNotContainsString('<', $cell['export']);
        $this->assertStringNotContainsString('avatar', strtolower($cell['export']));
    }

    public function test_driver_performance_photo_alt_text_is_not_the_export_value_source(): void
    {
        $admin = $this->createAdmin();
        Driver::create([
            'name' => 'Jane Roe',
            'type' => Driver::TYPE_INTERNAL,
            'photo' => 'drivers/jane.png',
        ]);

        $html = $this->actingAs($admin)
            ->get(route('reports.driver-performance', [
                'date_from' => '2026-08-01',
                'date_to' => '2026-08-31',
            ]))
            ->assertOk()
            ->getContent();

        $cell = $this->driverNameExportCell($html);
        $this->assertNotNull($cell);
        $this->assertSame('JANE ROE', $cell['export']);
        $this->assertSame('JANE ROE', $cell['named']);
        $this->assertStringContainsString('<img', $cell['html']);
        $this->assertStringNotContainsString('<img', $cell['export']);
        $this->assertStringNotContainsString('drivers/jane.png', $cell['export']);
    }

    public function test_trip_summary_displays_dates_as_dd_mm_yyyy_and_keeps_sortable_order(): void
    {
        $admin = $this->createAdmin();
        $vessel = Vessel::withoutEvents(fn () => Vessel::create(['name' => 'Vessel A']));
        $driver = Driver::create(['name' => 'Driver A']);
        $trip = Trip::create([
            'driver_id' => $driver->id,
            'trip_date' => '2026-08-30',
            'title' => 'Trip 1',
            'status' => TripCrew::STATUS_ASSIGNED,
        ]);
        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Crew Member',
            'from_location' => 'Port',
            'to_location' => 'Airport',
            'pick_up_time' => '09:00:00',
        ]);

        $html = $this->actingAs($admin)
            ->get(route('reports.trip-summary', [
                'date_from' => '2026-08-01',
                'date_to' => '2026-08-31',
            ]))
            ->assertOk()
            ->assertSee('30/08/2026', false)
            ->assertDontSee('Aug 30, 2026', false)
            ->assertDontSee('August 30, 2026', false)
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/data-order="\d+"[^>]*>30\/08\/2026/',
            $html
        );
        $this->assertStringNotContainsString("format('M d, Y')", $html);
    }

    /**
     * @return array{export: string, named: string, full_text: string, html: string}|null
     */
    protected function driverNameExportCell(string $html): ?array
    {
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $cells = $xpath->query('//td[@data-export]');

        if ($cells->length === 0) {
            return null;
        }

        $cell = $cells->item(0);
        $named = $xpath->query('.//*[@data-export-name]', $cell)->item(0);

        return [
            'export' => $cell->getAttribute('data-export'),
            'named' => $named ? trim($named->textContent) : '',
            'full_text' => trim(preg_replace('/\s+/', ' ', $cell->textContent) ?? ''),
            'html' => $dom->saveHTML($cell),
        ];
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

    protected function createSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'personal_access_tokens', 'trip_expenses', 'trip_expense_types',
            'trip_issues', 'trip_issue_types', 'trip_crews', 'trips',
            'partners', 'vessels', 'drivers', 'activity_logs',
            'user_permissions', 'role_permissions', 'permissions',
            'notifications', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('category');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('role');
            $table->foreignId('permission_id');
            $table->timestamps();
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('permission_id');
            $table->boolean('granted')->default(true);
            $table->timestamps();
        });

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

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('role')->default(2);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('type')->default(1);
            $table->string('photo')->nullable();
            $table->string('notification_token')->nullable();
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

        Permission::create([
            'name' => 'view_reports',
            'display_name' => 'View Reports',
            'category' => 'reports',
        ]);
    }
}
