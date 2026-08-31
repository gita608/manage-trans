<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\Permission;
use App\Models\Trip;
use App\Models\TripCrew;
use App\Models\TripCrewRemoval;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Minimal schema — full RefreshDatabase fails on historical SQLite trip migrations.
 */
class TripUniversalSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function searchableFieldProvider(): array
    {
        return [
            'trip_reference' => ['TRP-000387'],
            'partial trip_reference' => ['000387'],
            'trip title' => ['Night Shuttle'],
            'partner request reference' => ['REQ-000021'],
            'partner title' => ['ABC Shipping'],
            'driver name' => ['Driver Rabil'],
            'driver contact' => ['055998'],
            'active crew name' => ['Mohammed Ali Active'],
            'crew primary phone' => ['050123'],
            'crew secondary phone' => ['050987'],
            'crew address' => ['Al Mina'],
            'from location' => ['Port Rashid'],
            'to location' => ['Dubai Airport'],
            'flight number' => ['EK202'],
            'remarks' => ['late arrival'],
            'sub remark' => ['gate change'],
            'vessel name' => ['Ever Given'],
        ];
    }

    #[DataProvider('searchableFieldProvider')]
    public function test_universal_search_matches_expected_fields(string $term): void
    {
        $staff = $this->createStaff(['view_trips']);
        $match = $this->createSearchableTrip();
        $this->createUnrelatedTrip();

        $this->assertSame('TRP-000387', $match->trip_reference);

        $response = $this->actingAs($staff)->get(route('trips.index', [
            'search' => $term,
        ]));

        $response->assertOk();
        $this->assertSeeTrip($response, $match);
        $this->assertDontSeeTrip($response, $this->unrelatedTripReference());
        $response->assertSee('Search Results', false);
        $response->assertDontSee("Today's Trips");
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function removedCrewFieldProvider(): array
    {
        return [
            'removed crew name' => ['Removed Person'],
            'removed crew phone' => ['050111'],
            'removed crew route' => ['Dock Alpha'],
            'removed crew vessel snapshot' => ['Snapshot Vessel'],
            'removal remark' => ['cancelled seat'],
            'removed crew driver snapshot' => ['Snapshot Driver'],
        ];
    }

    #[DataProvider('removedCrewFieldProvider')]
    public function test_universal_search_matches_removed_crew_history(string $term): void
    {
        $staff = $this->createStaff(['view_trips']);
        $trip = $this->createTripWithRemovedCrew();
        $otherTrip = $this->createUnrelatedTrip();

        $response = $this->actingAs($staff)->get(route('trips.index', [
            'search' => $term,
        ]));

        $response->assertOk();
        $this->assertSeeTrip($response, $trip);
        $this->assertDontSeeTrip($response, $otherTrip->trip_reference);
        $this->assertSame(1, substr_count($response->getContent(), 'class="trip-card '));
    }

    public function test_search_is_case_insensitive_and_partial(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $match = $this->createSearchableTrip();
        $this->createUnrelatedTrip();

        $response = $this->actingAs($staff)->get(route('trips.index', [
            'search' => 'mohammed ali ACTIVE',
        ]));

        $response->assertOk();
        $this->assertSeeTrip($response, $match);
        $this->assertDontSeeTrip($response, $this->unrelatedTripReference());
    }

    public function test_unrelated_trip_is_not_returned(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $this->createSearchableTrip();
        $unrelated = $this->createUnrelatedTrip();

        $response = $this->actingAs($staff)->get(route('trips.index', [
            'search' => 'Mohammed Ali Active',
        ]));

        $response->assertOk();
        $this->assertDontSeeTrip($response, $unrelated->trip_reference);
    }

    public function test_historical_trip_is_found_when_search_has_no_date_filter(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $historical = $this->createSearchableTrip(['trip_date' => today()->subMonths(2)->format('Y-m-d')]);
        $todayTrip = $this->createTodayTrip();

        $response = $this->actingAs($staff)->get(route('trips.index', [
            'search' => 'Mohammed Ali Active',
        ]));

        $response->assertOk();
        $this->assertSeeTrip($response, $historical);
        $this->assertDontSeeTrip($response, $todayTrip->trip_reference);
        $response->assertSee('Search Results', false);
        $response->assertDontSee("Today's Trips");
        $response->assertSee('selected>All Dates', false);
    }

    public function test_index_without_search_still_defaults_to_today(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $historical = $this->createSearchableTrip(['trip_date' => today()->subMonths(2)->format('Y-m-d')]);
        $todayTrip = $this->createTodayTrip();

        $response = $this->actingAs($staff)->get(route('trips.index'));

        $response->assertOk();
        $response->assertSee("Today's Trips");
        $this->assertSeeTrip($response, $todayTrip);
        $this->assertDontSeeTrip($response, $historical->trip_reference);
    }

    public function test_search_plus_today_does_not_return_historical_match(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $historical = $this->createSearchableTrip(['trip_date' => today()->subMonths(2)->format('Y-m-d')]);
        $todayMatch = $this->createSearchableTrip([
            'trip_date' => today()->format('Y-m-d'),
            'trip_reference' => 'TRP-000388',
            'title' => 'Today Shuttle',
            'crew_name' => 'Mohammed Ali Active',
        ]);

        $response = $this->actingAs($staff)->get(route('trips.index', [
            'search' => 'Mohammed Ali Active',
            'date_range' => 'today',
        ]));

        $response->assertOk();
        $this->assertSeeTrip($response, $todayMatch);
        $this->assertDontSeeTrip($response, $historical->trip_reference);
        $response->assertSee('Search Results — Today', false);
    }

    public function test_search_plus_last_7_days_excludes_older_matches(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $recent = $this->createSearchableTrip([
            'trip_date' => today()->subDays(3)->format('Y-m-d'),
            'trip_reference' => 'TRP-000390',
        ]);
        $older = $this->createSearchableTrip([
            'trip_date' => today()->subDays(20)->format('Y-m-d'),
            'trip_reference' => 'TRP-000391',
            'title' => 'Older Shuttle',
        ]);

        $response = $this->actingAs($staff)->get(route('trips.index', [
            'search' => 'Mohammed Ali Active',
            'date_range' => 'last_7_days',
        ]));

        $response->assertOk();
        $this->assertSeeTrip($response, $recent);
        $this->assertDontSeeTrip($response, $older->trip_reference);
        $response->assertSee('Search Results — Last 7 Days', false);
    }

    public function test_search_plus_custom_date_range_is_respected(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $inside = $this->createSearchableTrip([
            'trip_date' => '2026-03-15',
            'trip_reference' => 'TRP-000392',
        ]);
        $outside = $this->createSearchableTrip([
            'trip_date' => '2026-01-10',
            'trip_reference' => 'TRP-000393',
            'title' => 'January Shuttle',
        ]);

        $response = $this->actingAs($staff)->get(route('trips.index', [
            'search' => 'Mohammed Ali Active',
            'date_range' => 'custom',
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
        ]));

        $response->assertOk();
        $this->assertSeeTrip($response, $inside);
        $this->assertDontSeeTrip($response, $outside->trip_reference);
        $response->assertSee('Search Results — Custom Range', false);
    }

    public function test_search_and_status_filter_combine_with_and_semantics(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $completedMatch = $this->createSearchableTrip([
            'status' => TripCrew::STATUS_COMPLETED,
            'trip_reference' => 'TRP-000401',
        ]);
        $assignedMatch = $this->createSearchableTrip([
            'status' => TripCrew::STATUS_ASSIGNED,
            'trip_reference' => 'TRP-000402',
            'title' => 'Assigned Shuttle',
        ]);
        $completedOther = $this->createUnrelatedTrip([
            'status' => TripCrew::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($staff)->get(route('trips.index', [
            'search' => 'Mohammed Ali Active',
            'status' => TripCrew::STATUS_COMPLETED,
        ]));

        $response->assertOk();
        $this->assertSeeTrip($response, $completedMatch);
        $this->assertDontSeeTrip($response, $assignedMatch->trip_reference);
        $this->assertDontSeeTrip($response, $completedOther->trip_reference);
    }

    public function test_search_and_driver_filter_combine_with_and_semantics(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $aliceTrip = $this->createSearchableTrip([
            'driver_name' => 'Alice Driver',
            'trip_reference' => 'TRP-000410',
        ]);
        $bobTrip = $this->createSearchableTrip([
            'driver_name' => 'Bob Driver',
            'trip_reference' => 'TRP-000411',
            'title' => 'Bob Shuttle',
        ]);

        $response = $this->actingAs($staff)->get(route('trips.index', [
            'search' => 'Mohammed Ali Active',
            'driver' => 'Alice Driver',
        ]));

        $response->assertOk();
        $this->assertSeeTrip($response, $aliceTrip);
        $this->assertDontSeeTrip($response, $bobTrip->trip_reference);
    }

    public function test_search_and_vessel_filter_combine_with_and_semantics(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $everGiven = $this->createSearchableTrip([
            'vessel_name' => 'Ever Given',
            'trip_reference' => 'TRP-000420',
        ]);
        $otherVessel = $this->createSearchableTrip([
            'vessel_name' => 'Other Ship',
            'trip_reference' => 'TRP-000421',
            'title' => 'Other Vessel Shuttle',
        ]);

        $response = $this->actingAs($staff)->get(route('trips.index', [
            'search' => 'Mohammed Ali Active',
            'vessel' => 'Ever Given',
        ]));

        $response->assertOk();
        $this->assertSeeTrip($response, $everGiven);
        $this->assertDontSeeTrip($response, $otherVessel->trip_reference);
    }

    public function test_multiple_matching_crews_do_not_duplicate_trip_rows(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $trip = $this->createSearchableTrip();
        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $trip->crews->first()->vessel_id,
            'name' => 'Mohammed Ali Twin',
            'from_location' => 'Port Rashid',
            'to_location' => 'Dubai Airport',
            'pick_up_time' => '11:00:00',
        ]);

        $response = $this->actingAs($staff)->get(route('trips.index', [
            'search' => 'Mohammed',
        ]));

        $response->assertOk();
        $this->assertSeeTrip($response, $trip);
        $this->assertSame(1, substr_count($response->getContent(), 'class="trip-card '));
    }

    public function test_empty_and_whitespace_search_behaves_like_no_search(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $historical = $this->createSearchableTrip(['trip_date' => today()->subMonths(2)->format('Y-m-d')]);
        $todayTrip = $this->createTodayTrip();

        foreach (['', '   '] as $term) {
            $response = $this->actingAs($staff)->get(route('trips.index', [
                'search' => $term,
            ]));

            $response->assertOk();
            $response->assertSee("Today's Trips");
            $this->assertSeeTrip($response, $todayTrip);
            $this->assertDontSeeTrip($response, $historical->trip_reference);
        }
    }

    public function test_stats_represent_the_filtered_search_result_set(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $first = $this->createSearchableTrip([
            'status' => TripCrew::STATUS_COMPLETED,
            'trip_reference' => 'TRP-000430',
        ]);
        $second = $this->createSearchableTrip([
            'status' => TripCrew::STATUS_IN_PROGRESS,
            'trip_reference' => 'TRP-000431',
            'title' => 'Second Shuttle',
        ]);
        $this->createTodayTrip();

        $response = $this->actingAs($staff)->get(route('trips.index', [
            'search' => 'Mohammed Ali Active',
        ]));

        $response->assertOk();
        $this->assertSeeTrip($response, $first);
        $this->assertSeeTrip($response, $second);
        $this->assertSame(2, substr_count($response->getContent(), 'class="trip-card '));
        $response->assertSee('Total Trips', false);
        $response->assertSee('Total Crew', false);
        $this->assertMatchesRegularExpression('/Total Trips[\s\S]{0,400}?\b2\b/', $response->getContent());
        $this->assertMatchesRegularExpression('/In Progress[\s\S]{0,400}?\b1\b/', $response->getContent());
        $this->assertMatchesRegularExpression('/Completed[\s\S]{0,400}?\b1\b/', $response->getContent());
    }

    public function test_search_page_restores_filter_state(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $this->createSearchableTrip();

        $response = $this->actingAs($staff)->get(route('trips.index', [
            'search' => 'Mohammed Ali Active',
            'status' => TripCrew::STATUS_ASSIGNED,
            'driver' => 'Driver Rabil',
            'vessel' => 'Ever Given',
        ]));

        $response->assertOk();
        $response->assertSee('value="Mohammed Ali Active"', false);
        $response->assertSee('selected>All Dates', false);
        $response->assertSee('Search Trips', false);
        $response->assertSee('Search ref, crew, phone, route, vessel...', false);
    }

    public function test_removed_crew_match_does_not_return_a_different_trip(): void
    {
        $staff = $this->createStaff(['view_trips']);
        $match = $this->createTripWithRemovedCrew();
        $other = $this->createTripWithRemovedCrew([
            'trip_reference' => 'TRP-000499',
            'crew_name' => 'Someone Else',
            'phone' => '0599999999',
            'from_location' => 'Other Dock',
            'to_location' => 'Other Gate',
            'vessel_name' => 'Other Snapshot',
            'removal_remark' => 'Different reason',
            'driver_name' => 'Other Snapshot Driver',
        ]);

        $response = $this->actingAs($staff)->get(route('trips.index', [
            'search' => 'Removed Person',
        ]));

        $response->assertOk();
        $this->assertSeeTrip($response, $match);
        $this->assertDontSeeTrip($response, $other->trip_reference);
    }

    protected function assertSeeTrip($response, Trip $trip): void
    {
        $response->assertSee($trip->trip_reference, false);
    }

    protected function assertDontSeeTrip($response, string $reference): void
    {
        $response->assertDontSee($reference, false);
    }

    protected function unrelatedTripReference(): string
    {
        return 'TRP-000500';
    }

    protected function createSearchableTrip(array $overrides = []): Trip
    {
        $partner = Partner::withoutEvents(fn () => Partner::create([
            'title' => $overrides['partner_title'] ?? 'ABC Shipping',
        ]));

        $partnerRequest = PartnerRequest::withoutEvents(fn () => PartnerRequest::create([
            'partner_id' => $partner->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_APPROVED,
            'request_reference' => $overrides['request_reference'] ?? 'REQ-000021',
        ]));

        $driver = Driver::withoutEvents(fn () => Driver::create([
            'name' => $overrides['driver_name'] ?? 'Driver Rabil',
            'contact' => $overrides['driver_contact'] ?? '0559988776',
        ]));

        $vessel = Vessel::withoutEvents(fn () => Vessel::create([
            'name' => $overrides['vessel_name'] ?? 'Ever Given',
        ]));

        $trip = Trip::withoutEvents(fn () => Trip::create([
            'driver_id' => $driver->id,
            'partner_id' => $partner->id,
            'partner_request_id' => $partnerRequest->id,
            'trip_date' => $overrides['trip_date'] ?? today()->subMonths(2)->format('Y-m-d'),
            'title' => $overrides['title'] ?? 'Night Shuttle Special',
            'status' => $overrides['status'] ?? TripCrew::STATUS_ASSIGNED,
            'trip_reference' => $overrides['trip_reference'] ?? 'TRP-000387',
        ]));

        if (empty($trip->trip_reference)) {
            $trip->trip_reference = $overrides['trip_reference'] ?? 'TRP-000387';
            $trip->saveQuietly();
        }

        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => $overrides['crew_name'] ?? 'Mohammed Ali Active',
            'phone' => $overrides['phone'] ?? '0501234567',
            'phone_2' => $overrides['phone_2'] ?? '0509876543',
            'address' => $overrides['address'] ?? 'Al Mina Street 14',
            'from_location' => $overrides['from_location'] ?? 'Port Rashid',
            'to_location' => $overrides['to_location'] ?? 'Dubai Airport',
            'pick_up_time' => '09:00:00',
            'flight_number' => $overrides['flight_number'] ?? 'EK202',
            'remarks' => $overrides['remarks'] ?? 'late arrival note',
            'sub_remark' => $overrides['sub_remark'] ?? 'gate change note',
        ]);

        return $trip->fresh(['crews.vessel', 'driver', 'partner', 'partnerRequest']);
    }

    protected function createTripWithRemovedCrew(array $overrides = []): Trip
    {
        $driver = Driver::withoutEvents(fn () => Driver::create([
            'name' => 'Current Driver',
        ]));
        $vessel = Vessel::withoutEvents(fn () => Vessel::create([
            'name' => 'Current Vessel',
        ]));

        $trip = Trip::withoutEvents(fn () => Trip::create([
            'driver_id' => $driver->id,
            'trip_date' => $overrides['trip_date'] ?? today()->subMonths(3)->format('Y-m-d'),
            'title' => $overrides['title'] ?? 'Removal History Trip',
            'status' => TripCrew::STATUS_ASSIGNED,
            'trip_reference' => $overrides['trip_reference'] ?? 'TRP-000450',
        ]));

        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Still On Board',
            'from_location' => 'Current Port',
            'to_location' => 'Current Airport',
            'pick_up_time' => '08:00:00',
        ]);

        TripCrewRemoval::create([
            'trip_id' => $trip->id,
            'trip_crew_id' => null,
            'crew_name' => $overrides['crew_name'] ?? 'Removed Person',
            'phone' => $overrides['phone'] ?? '0501112223',
            'phone_2' => $overrides['phone_2'] ?? '0504445556',
            'address' => $overrides['address'] ?? 'Old Dock Address',
            'vessel_name' => $overrides['vessel_name'] ?? 'Snapshot Vessel',
            'from_location' => $overrides['from_location'] ?? 'Dock Alpha',
            'to_location' => $overrides['to_location'] ?? 'Gate Nine',
            'flight_number' => $overrides['flight_number'] ?? 'XY999',
            'remarks' => $overrides['remarks'] ?? 'Was waiting',
            'sub_remark' => $overrides['sub_remark'] ?? 'Was delayed',
            'driver_name' => $overrides['driver_name'] ?? 'Snapshot Driver',
            'removed_at' => now(),
            'removal_remark' => $overrides['removal_remark'] ?? 'Client cancelled seat',
        ]);

        return $trip->fresh();
    }

    protected function createTodayTrip(): Trip
    {
        $driver = Driver::withoutEvents(fn () => Driver::create([
            'name' => 'Today Driver',
        ]));
        $vessel = Vessel::withoutEvents(fn () => Vessel::create([
            'name' => 'Today Vessel',
        ]));

        $trip = Trip::withoutEvents(fn () => Trip::create([
            'driver_id' => $driver->id,
            'trip_date' => today()->format('Y-m-d'),
            'title' => 'Today Only Trip',
            'status' => TripCrew::STATUS_ASSIGNED,
            'trip_reference' => 'TRP-000600',
        ]));

        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Today Only Crew',
            'from_location' => 'Today Port',
            'to_location' => 'Today Airport',
            'pick_up_time' => '09:00:00',
        ]);

        return $trip->fresh();
    }

    protected function createUnrelatedTrip(array $overrides = []): Trip
    {
        $partner = Partner::withoutEvents(fn () => Partner::create([
            'title' => 'Other Company',
        ]));
        $driver = Driver::withoutEvents(fn () => Driver::create([
            'name' => 'Other Driver',
            'contact' => '0500000000',
        ]));
        $vessel = Vessel::withoutEvents(fn () => Vessel::create([
            'name' => 'Other Vessel',
        ]));

        $trip = Trip::withoutEvents(fn () => Trip::create([
            'driver_id' => $driver->id,
            'partner_id' => $partner->id,
            'trip_date' => $overrides['trip_date'] ?? today()->subMonths(2)->format('Y-m-d'),
            'title' => $overrides['title'] ?? 'Unrelated Title',
            'status' => $overrides['status'] ?? TripCrew::STATUS_ASSIGNED,
            'trip_reference' => $overrides['trip_reference'] ?? $this->unrelatedTripReference(),
        ]));

        TripCrew::create([
            'trip_id' => $trip->id,
            'vessel_id' => $vessel->id,
            'name' => 'Unrelated Person',
            'phone' => '0510000000',
            'from_location' => 'Unrelated From',
            'to_location' => 'Unrelated To',
            'pick_up_time' => '09:00:00',
        ]);

        return $trip->fresh();
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

    protected function createSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'personal_access_tokens', 'trip_expenses', 'trip_expense_types',
            'trip_issues', 'trip_issue_types', 'trip_crew_removals', 'trip_crews', 'trips',
            'partner_requests', 'partners', 'vessels', 'drivers', 'activity_logs',
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
            $table->string('contact')->nullable();
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

        Schema::create('partner_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_reference')->nullable();
            $table->foreignId('partner_id')->nullable();
            $table->foreignId('partner_user_id')->nullable();
            $table->string('submission_method')->nullable();
            $table->string('status')->nullable();
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
            $table->date('trip_date')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->default('unassigned');
            $table->timestamps();
        });

        Schema::create('trip_crews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id');
            $table->string('name')->nullable();
            $table->foreignId('vessel_id')->nullable();
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

        Schema::create('trip_crew_removals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id');
            $table->unsignedBigInteger('trip_crew_id')->nullable();
            $table->string('crew_name');
            $table->string('phone')->nullable();
            $table->string('phone_2')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('vessel_id')->nullable();
            $table->string('vessel_name')->nullable();
            $table->time('pick_up_time')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->string('flight_number')->nullable();
            $table->text('remarks')->nullable();
            $table->string('sub_remark')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->string('driver_name')->nullable();
            $table->foreignId('removed_by')->nullable();
            $table->timestamp('removed_at');
            $table->text('removal_remark')->nullable();
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
            $table->text('user_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        foreach (['view_trips'] as $name) {
            Permission::create([
                'name' => $name,
                'display_name' => $name,
                'category' => 'trips',
            ]);
        }
    }
}
