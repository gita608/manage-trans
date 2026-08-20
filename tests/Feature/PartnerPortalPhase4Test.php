<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\PartnerRequestItem;
use App\Models\PartnerUser;
use App\Models\Trip;
use App\Models\TripCrew;
use App\Models\Vessel;
use App\Services\TextractService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class PartnerPortalPhase4Test extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->createPartnerPortalSchema();
    }

    protected function createPartnerPortalSchema(): void
    {
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

        Schema::create('drivers', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('trips', function ($table) {
            $table->id();
            $table->string('trip_reference')->unique()->nullable();
            $table->foreignId('partner_request_id')->nullable()->constrained('partner_requests')->onDelete('set null');
            $table->foreignId('partner_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->date('trip_date')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->default('unassigned');
            $table->timestamps();
        });

        Schema::create('trip_crews', function ($table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
            $table->foreignId('vessel_id')->nullable();
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

    protected function createPartnerUser(array $partnerAttrs = [], array $userAttrs = []): PartnerUser
    {
        $partner = Partner::create(array_merge([
            'title' => 'Test Partner',
            'allow_manual_submission' => true,
            'allow_image_submission' => true,
        ], $partnerAttrs));

        return PartnerUser::create(array_merge([
            'partner_id' => $partner->id,
            'name' => 'Partner User',
            'email' => 'partner@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ], $userAttrs));
    }

    protected function mockTextractRows(array $rows): void
    {
        $this->mock(TextractService::class, function ($mock) use ($rows) {
            $mock->shouldReceive('extractTableFromImage')
                ->andReturn($rows);
        });
    }

    protected function mockTextractException(string $message = 'AWS timeout'): void
    {
        $this->mock(TextractService::class, function ($mock) use ($message) {
            $mock->shouldReceive('extractTableFromImage')
                ->andThrow(new \RuntimeException($message));
        });
    }

    protected function validImage(string $name = 'schedule.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 200, 200);
    }

    // --- Method selector ---

    public function test_both_enabled_partner_sees_method_selector(): void
    {
        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->get(route('partner.requests.new'))
            ->assertOk()
            ->assertSee('Manual Request')
            ->assertSee('Upload Schedule Image');
    }

    public function test_manual_only_new_request_redirects_to_manual(): void
    {
        $user = $this->createPartnerUser([
            'allow_manual_submission' => true,
            'allow_image_submission' => false,
        ]);

        $this->actingAs($user, 'partner')
            ->get(route('partner.requests.new'))
            ->assertRedirect(route('partner.requests.create'));
    }

    public function test_image_only_new_request_redirects_to_upload(): void
    {
        $user = $this->createPartnerUser([
            'allow_manual_submission' => false,
            'allow_image_submission' => true,
        ]);

        $this->actingAs($user, 'partner')
            ->get(route('partner.requests.new'))
            ->assertRedirect(route('partner.requests.image.create'));
    }

    public function test_neither_enabled_partner_cannot_create(): void
    {
        $user = $this->createPartnerUser([
            'allow_manual_submission' => false,
            'allow_image_submission' => false,
        ]);

        $this->actingAs($user, 'partner')
            ->get(route('partner.requests.new'))
            ->assertRedirect(route('partner.requests.index'))
            ->assertSessionHas('info');
    }

    // --- Image access control ---

    public function test_image_disabled_partner_cannot_get_upload_page(): void
    {
        $user = $this->createPartnerUser([
            'allow_manual_submission' => true,
            'allow_image_submission' => false,
        ]);

        $this->actingAs($user, 'partner')
            ->get(route('partner.requests.image.create'))
            ->assertRedirect(route('partner.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_image_disabled_partner_cannot_post_upload(): void
    {
        Storage::fake('local');
        $user = $this->createPartnerUser([
            'allow_manual_submission' => true,
            'allow_image_submission' => false,
        ]);

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), [
                'image' => $this->validImage(),
            ])
            ->assertRedirect(route('partner.dashboard'))
            ->assertSessionHas('error');
    }

    // --- Validation ---

    public function test_valid_jpg_image_accepted(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([]);

        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), [
                'image' => $this->validImage('schedule.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('partner_requests', 1);
    }

    public function test_valid_png_image_accepted(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([]);

        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), [
                'image' => UploadedFile::fake()->image('schedule.png'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('partner_requests', 1);
    }

    public function test_non_image_file_rejected(): void
    {
        Storage::fake('local');
        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), [
                'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('partner_requests', 0);
    }

    public function test_file_over_10mb_rejected(): void
    {
        Storage::fake('local');
        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), [
                'image' => UploadedFile::fake()->create('large.jpg', 10241, 'image/jpeg'),
            ])
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('partner_requests', 0);
    }

    // --- Security / REQ creation ---

    public function test_partner_id_derived_from_authenticated_partner_user(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([]);

        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), [
                'image' => $this->validImage(),
            ]);

        $request = PartnerRequest::first();
        $this->assertSame($user->partner_id, $request->partner_id);
        $this->assertSame($user->id, $request->partner_user_id);
    }

    public function test_malicious_partner_id_ignored(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([]);

        $user = $this->createPartnerUser();
        $otherPartner = Partner::create(['title' => 'Other Partner']);

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), [
                'image' => $this->validImage(),
                'partner_id' => $otherPartner->id,
                'status' => PartnerRequest::STATUS_APPROVED,
                'submission_method' => PartnerRequest::METHOD_MANUAL,
            ]);

        $request = PartnerRequest::first();
        $this->assertSame($user->partner_id, $request->partner_id);
        $this->assertSame(PartnerRequest::METHOD_IMAGE, $request->submission_method);
        $this->assertSame(PartnerRequest::STATUS_PENDING, $request->status);
    }

    public function test_image_submission_creates_partner_request_with_expected_fields(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([]);

        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), [
                'image' => $this->validImage(),
            ]);

        $request = PartnerRequest::first();
        $this->assertNotNull($request);
        $this->assertSame(PartnerRequest::METHOD_IMAGE, $request->submission_method);
        $this->assertSame(PartnerRequest::STATUS_PENDING, $request->status);
        $this->assertNotNull($request->submitted_at);
        $this->assertNotNull($request->source_image_path);
        $this->assertMatchesRegularExpression('/^REQ-\d{6}$/', $request->request_reference);
    }

    public function test_image_stored_on_private_local_disk(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([]);

        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), [
                'image' => $this->validImage(),
            ]);

        $request = PartnerRequest::first();
        Storage::disk('local')->assertExists($request->source_image_path);
        $this->assertStringStartsWith('partner-requests/'.$user->partner_id.'/', $request->source_image_path);
    }

    // --- Extraction success ---

    public function test_successful_textract_sets_extraction_status_completed(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([
            ['Crew Name', 'Driver Name', 'Vessel Name', 'Pick-up Time', 'From', 'To'],
            ['John Smith', 'Driver A', 'Test Vessel', '0300PM', 'Port A', 'Airport B'],
        ]);

        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), [
                'image' => $this->validImage(),
            ]);

        $request = PartnerRequest::first();
        $this->assertSame(PartnerRequest::EXTRACTION_COMPLETED, $request->extraction_status);
    }

    public function test_successful_extraction_creates_partner_request_items(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([
            ['Crew Name', 'Driver Name', 'Vessel Name', 'Pick-up Time', 'From', 'To'],
            ['John Smith', 'Driver A', 'Test Vessel', '0300PM', 'Port A', 'Airport B'],
        ]);

        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), [
                'image' => $this->validImage(),
            ]);

        $this->assertDatabaseCount('partner_request_items', 1);
        $item = PartnerRequestItem::first();
        $this->assertSame('John Smith', $item->name);
        $this->assertSame('Test Vessel', $item->vessel_name_raw);
    }

    public function test_extracted_vessel_raw_text_stored_in_vessel_name_raw(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([
            ['Crew Name', 'Driver Name', 'Vessel Name', 'Pick-up Time', 'From', 'To'],
            ['Jane', 'Driver', 'ADNOC A08', '0900AM', 'A', 'B'],
        ]);

        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), ['image' => $this->validImage()]);

        $this->assertSame('ADNOC A08', PartnerRequestItem::first()->vessel_name_raw);
    }

    public function test_existing_vessel_may_be_matched_to_vessel_id(): void
    {
        Storage::fake('local');
        $vessel = Vessel::withoutEvents(fn () => Vessel::create(['name' => 'ADNOC A08']));

        $this->mockTextractRows([
            ['Crew Name', 'Driver Name', 'Vessel Name', 'Pick-up Time', 'From', 'To'],
            ['Jane', 'Driver', 'ADNOC A08', '0900AM', 'A', 'B'],
        ]);

        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), ['image' => $this->validImage()]);

        $this->assertSame($vessel->id, PartnerRequestItem::first()->vessel_id);
    }

    public function test_unknown_vessel_does_not_create_new_vessel(): void
    {
        Storage::fake('local');
        Vessel::withoutEvents(fn () => Vessel::create(['name' => 'ADNOC 1010']));

        $this->mockTextractRows([
            ['Crew Name', 'Driver Name', 'Vessel Name', 'Pick-up Time', 'From', 'To'],
            ['Jane', 'Driver', 'ADNOC 101', '0900AM', 'A', 'B'],
        ]);

        $user = $this->createPartnerUser();
        $vesselCountBefore = Vessel::count();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), ['image' => $this->validImage()]);

        $this->assertSame($vesselCountBefore, Vessel::count());
        $this->assertNull(PartnerRequestItem::first()->vessel_id);
        $this->assertSame('ADNOC 101', PartnerRequestItem::first()->vessel_name_raw);
    }

    public function test_driver_id_remains_null(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([
            ['Crew Name', 'Driver Name', 'Vessel Name', 'Pick-up Time', 'From', 'To'],
            ['Jane', 'Driver Name Here', 'Vessel', '0900AM', 'A', 'B'],
        ]);

        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), ['image' => $this->validImage()]);

        $this->assertNull(PartnerRequestItem::first()->driver_id);
    }

    public function test_incomplete_extracted_data_does_not_reject_req(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([
            ['Crew Name', 'Driver Name', 'Vessel Name', 'Pick-up Time', 'From', 'To'],
            ['Partial Name', '', '', '', '', ''],
        ]);

        $user = $this->createPartnerUser();

        $response = $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), ['image' => $this->validImage()]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseCount('partner_requests', 1);
    }

    // --- Zero rows / failures ---

    public function test_zero_extracted_rows_preserves_req(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([]);

        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), ['image' => $this->validImage()])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('partner_requests', 1);
        $this->assertDatabaseCount('partner_request_items', 0);
    }

    public function test_zero_rows_sets_extraction_status_failed(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([]);

        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), ['image' => $this->validImage()]);

        $this->assertSame(PartnerRequest::EXTRACTION_FAILED, PartnerRequest::first()->extraction_status);
    }

    public function test_textract_exception_preserves_req_and_image(): void
    {
        Storage::fake('local');
        $this->mockTextractException('AWS credential error');

        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), ['image' => $this->validImage()])
            ->assertSessionHas('success');

        $request = PartnerRequest::first();
        $this->assertNotNull($request);
        Storage::disk('local')->assertExists($request->source_image_path);
    }

    public function test_textract_exception_sets_extraction_status_failed(): void
    {
        Storage::fake('local');
        $this->mockTextractException();

        $user = $this->createPartnerUser();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), ['image' => $this->validImage()]);

        $this->assertSame(PartnerRequest::EXTRACTION_FAILED, PartnerRequest::first()->extraction_status);
    }

    public function test_aws_exception_detail_not_exposed_to_partner(): void
    {
        Storage::fake('local');
        $this->mockTextractException('AWS secret credential error XYZ');

        $user = $this->createPartnerUser();

        $response = $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), ['image' => $this->validImage()]);

        $response->assertSessionHas('success');
        $response->assertSessionMissing('error');
        $this->assertStringNotContainsString('AWS', session('success') ?? '');
    }

    // --- No trip/driver/vessel creation ---

    public function test_partner_image_request_does_not_create_trip(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([
            ['Crew Name', 'Driver Name', 'Vessel Name', 'Pick-up Time', 'From', 'To'],
            ['John', 'Driver', 'Vessel', '0300PM', 'A', 'B'],
        ]);

        $user = $this->createPartnerUser();
        $tripCount = Trip::count();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), ['image' => $this->validImage()]);

        $this->assertSame($tripCount, Trip::count());
    }

    public function test_partner_image_request_does_not_create_trip_crew(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([
            ['Crew Name', 'Driver Name', 'Vessel Name', 'Pick-up Time', 'From', 'To'],
            ['John', 'Driver', 'Vessel', '0300PM', 'A', 'B'],
        ]);

        $user = $this->createPartnerUser();
        $crewCount = TripCrew::count();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), ['image' => $this->validImage()]);

        $this->assertSame($crewCount, TripCrew::count());
    }

    public function test_partner_image_request_does_not_create_driver(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([
            ['Crew Name', 'Driver Name', 'Vessel Name', 'Pick-up Time', 'From', 'To'],
            ['John', 'New Driver', 'Vessel', '0300PM', 'A', 'B'],
        ]);

        $user = $this->createPartnerUser();
        $driverCount = Driver::count();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), ['image' => $this->validImage()]);

        $this->assertSame($driverCount, Driver::count());
    }

    public function test_partner_image_request_does_not_create_vessel(): void
    {
        Storage::fake('local');
        $this->mockTextractRows([
            ['Crew Name', 'Driver Name', 'Vessel Name', 'Pick-up Time', 'From', 'To'],
            ['John', 'Driver', 'Brand New Vessel', '0300PM', 'A', 'B'],
        ]);

        $user = $this->createPartnerUser();
        $vesselCount = Vessel::count();

        $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), ['image' => $this->validImage()]);

        $this->assertSame($vesselCount, Vessel::count());
    }

    // --- Edit / withdraw rules ---

    public function test_image_pending_request_cannot_open_manual_edit(): void
    {
        $user = $this->createPartnerUser();
        $request = PartnerRequest::create([
            'partner_id' => $user->partner_id,
            'partner_user_id' => $user->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
            'request_reference' => 'REQ-000100',
            'submitted_at' => now(),
        ]);

        $this->actingAs($user, 'partner')
            ->get(route('partner.requests.edit', $request))
            ->assertRedirect(route('partner.requests.show', $request))
            ->assertSessionHas('error');
    }

    public function test_manual_pending_request_can_still_edit(): void
    {
        $user = $this->createPartnerUser();
        $request = PartnerRequest::create([
            'partner_id' => $user->partner_id,
            'partner_user_id' => $user->id,
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_PENDING,
            'request_reference' => 'REQ-000101',
            'submitted_at' => now(),
        ]);

        PartnerRequestItem::create([
            'partner_request_id' => $request->id,
            'trip_date' => '2026-08-25',
            'name' => 'Test',
            'from_location' => 'A',
            'to_location' => 'B',
        ]);

        $this->actingAs($user, 'partner')
            ->get(route('partner.requests.edit', $request))
            ->assertOk();
    }

    public function test_image_pending_request_can_withdraw(): void
    {
        Storage::fake('local');
        $user = $this->createPartnerUser();
        $path = 'partner-requests/'.$user->partner_id.'/test.jpg';
        Storage::disk('local')->put($path, 'fake-image');

        $request = PartnerRequest::create([
            'partner_id' => $user->partner_id,
            'partner_user_id' => $user->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
            'request_reference' => 'REQ-000102',
            'submitted_at' => now(),
            'source_image_path' => $path,
        ]);

        $this->actingAs($user, 'partner')
            ->patch(route('partner.requests.withdraw', $request))
            ->assertRedirect();

        $request->refresh();
        $this->assertSame(PartnerRequest::STATUS_WITHDRAWN, $request->status);
        Storage::disk('local')->assertExists($path);
    }

    // --- My Requests / detail ---

    public function test_my_requests_shows_image_req(): void
    {
        $user = $this->createPartnerUser();
        PartnerRequest::create([
            'partner_id' => $user->partner_id,
            'partner_user_id' => $user->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
            'request_reference' => 'REQ-000103',
            'submitted_at' => now(),
        ]);

        $this->actingAs($user, 'partner')
            ->get(route('partner.requests.index'))
            ->assertOk()
            ->assertSee('REQ-000103')
            ->assertSee('Image');
    }

    public function test_image_req_detail_does_not_expose_extracted_rows(): void
    {
        Storage::fake('local');
        $user = $this->createPartnerUser();
        $path = 'partner-requests/'.$user->partner_id.'/detail.jpg';
        Storage::disk('local')->put($path, 'fake-image');

        $request = PartnerRequest::create([
            'partner_id' => $user->partner_id,
            'partner_user_id' => $user->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
            'request_reference' => 'REQ-000104',
            'submitted_at' => now(),
            'source_image_path' => $path,
        ]);

        PartnerRequestItem::create([
            'partner_request_id' => $request->id,
            'name' => 'Secret OCR Name',
            'from_location' => 'Hidden From',
            'to_location' => 'Hidden To',
        ]);

        $this->actingAs($user, 'partner')
            ->get(route('partner.requests.show', $request))
            ->assertOk()
            ->assertSee('Manage Trans will review')
            ->assertDontSee('Secret OCR Name')
            ->assertDontSee('Hidden From');
    }

    public function test_image_req_detail_shows_secure_image_endpoint(): void
    {
        Storage::fake('local');
        $user = $this->createPartnerUser();
        $path = 'partner-requests/'.$user->partner_id.'/preview.jpg';
        Storage::disk('local')->put($path, 'fake-image');

        $request = PartnerRequest::create([
            'partner_id' => $user->partner_id,
            'partner_user_id' => $user->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
            'request_reference' => 'REQ-000105',
            'submitted_at' => now(),
            'source_image_path' => $path,
        ]);

        $this->actingAs($user, 'partner')
            ->get(route('partner.requests.show', $request))
            ->assertOk()
            ->assertSee(route('partner.requests.image', $request), false);
    }

    // --- Secure image endpoint ---

    public function test_owning_partner_can_view_image(): void
    {
        Storage::fake('local');
        $user = $this->createPartnerUser();
        $path = 'partner-requests/'.$user->partner_id.'/view.jpg';
        Storage::disk('local')->put($path, 'image-bytes');

        $request = PartnerRequest::create([
            'partner_id' => $user->partner_id,
            'partner_user_id' => $user->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
            'request_reference' => 'REQ-000106',
            'source_image_path' => $path,
        ]);

        $this->actingAs($user, 'partner')
            ->get(route('partner.requests.image', $request))
            ->assertOk();
    }

    public function test_another_partner_cannot_view_image(): void
    {
        Storage::fake('local');
        $owner = $this->createPartnerUser(['title' => 'Owner Partner']);
        $other = $this->createPartnerUser([
            'title' => 'Other Partner',
        ], [
            'email' => 'other@example.com',
        ]);

        $path = 'partner-requests/'.$owner->partner_id.'/secret.jpg';
        Storage::disk('local')->put($path, 'image-bytes');

        $request = PartnerRequest::create([
            'partner_id' => $owner->partner_id,
            'partner_user_id' => $owner->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
            'request_reference' => 'REQ-000107',
            'source_image_path' => $path,
        ]);

        $this->actingAs($other, 'partner')
            ->get(route('partner.requests.image', $request))
            ->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_view_private_image(): void
    {
        Storage::fake('local');
        $user = $this->createPartnerUser();
        $path = 'partner-requests/'.$user->partner_id.'/private.jpg';
        Storage::disk('local')->put($path, 'image-bytes');

        $request = PartnerRequest::create([
            'partner_id' => $user->partner_id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
            'request_reference' => 'REQ-000108',
            'source_image_path' => $path,
        ]);

        $this->get(route('partner.requests.image', $request))
            ->assertRedirect(route('partner.login'));
    }

    public function test_raw_filesystem_path_not_exposed_in_response(): void
    {
        Storage::fake('local');
        $user = $this->createPartnerUser();
        $path = 'partner-requests/'.$user->partner_id.'/hidden.jpg';
        Storage::disk('local')->put($path, 'image-bytes');

        $request = PartnerRequest::create([
            'partner_id' => $user->partner_id,
            'partner_user_id' => $user->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
            'request_reference' => 'REQ-000109',
            'submitted_at' => now(),
            'source_image_path' => $path,
        ]);

        $this->actingAs($user, 'partner')
            ->get(route('partner.requests.show', $request))
            ->assertDontSee($path);
    }

    public function test_image_storage_failure_before_req_creation_leaves_no_persisted_data(): void
    {
        $filesystem = Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $filesystem->shouldReceive('putFileAs')->andReturn(false);
        $filesystem->shouldReceive('exists')->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($filesystem);

        $user = $this->createPartnerUser();

        $response = $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), [
                'image' => $this->validImage(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(
            'error',
            'Your image could not be uploaded. Please try again.'
        );

        $this->assertDatabaseCount('partner_requests', 0);
        $this->assertDatabaseCount('partner_request_items', 0);
        $this->assertDatabaseCount('trips', 0);
    }

    public function test_partner_image_endpoint_blocks_path_traversal(): void
    {
        Storage::fake('local');
        $user = $this->createPartnerUser();
        $otherPartnerId = $user->partner_id + 99;
        $secretPath = 'partner-requests/'.$otherPartnerId.'/secret.jpg';
        Storage::disk('local')->put($secretPath, 'secret-bytes');

        $request = PartnerRequest::create([
            'partner_id' => $user->partner_id,
            'partner_user_id' => $user->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
            'request_reference' => 'REQ-000110',
            'source_image_path' => 'partner-requests/'.$user->partner_id.'/../'.$otherPartnerId.'/secret.jpg',
        ]);

        $this->actingAs($user, 'partner')
            ->get(route('partner.requests.image', $request))
            ->assertNotFound();
    }

    public function test_partner_image_endpoint_blocks_another_partners_storage_prefix(): void
    {
        Storage::fake('local');
        $user = $this->createPartnerUser();
        $otherPartner = Partner::create(['title' => 'Other Partner']);
        $secretPath = 'partner-requests/'.$otherPartner->id.'/secret.jpg';
        Storage::disk('local')->put($secretPath, 'secret-bytes');

        $request = PartnerRequest::create([
            'partner_id' => $user->partner_id,
            'partner_user_id' => $user->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
            'request_reference' => 'REQ-000111',
            'source_image_path' => $secretPath,
        ]);

        $this->actingAs($user, 'partner')
            ->get(route('partner.requests.image', $request))
            ->assertNotFound();
    }

    public function test_partner_image_endpoint_blocks_arbitrary_private_path(): void
    {
        Storage::fake('local');
        $user = $this->createPartnerUser();
        Storage::disk('local')->put('temp/private.jpg', 'secret-bytes');

        $request = PartnerRequest::create([
            'partner_id' => $user->partner_id,
            'partner_user_id' => $user->id,
            'submission_method' => PartnerRequest::METHOD_IMAGE,
            'status' => PartnerRequest::STATUS_PENDING,
            'request_reference' => 'REQ-000112',
            'source_image_path' => 'temp/private.jpg',
        ]);

        $this->actingAs($user, 'partner')
            ->get(route('partner.requests.image', $request))
            ->assertNotFound();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
