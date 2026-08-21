<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\PartnerUser;
use App\Services\TextractService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/**
 * Partner submission rate-limiter enforcement regressions.
 */
class PartnerPortalRateLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function createSchema(): void
    {
        Schema::create('partners', function ($table) {
            $table->id();
            $table->string('title');
            $table->boolean('is_default')->default(false);
            $table->boolean('allow_manual_submission')->default(true);
            $table->boolean('allow_image_submission')->default(true);
            $table->timestamps();
        });

        Schema::create('partner_users', function ($table) {
            $table->id();
            $table->foreignId('partner_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('partner_requests', function ($table) {
            $table->id();
            $table->string('request_reference')->unique()->nullable();
            $table->foreignId('partner_id');
            $table->foreignId('partner_user_id')->nullable();
            $table->string('submission_method');
            $table->string('status');
            $table->string('source_image_path')->nullable();
            $table->string('extraction_status')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('partner_updated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('partner_request_items', function ($table) {
            $table->id();
            $table->foreignId('partner_request_id');
            $table->date('trip_date')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->foreignId('vessel_id')->nullable();
            $table->string('vessel_name_raw')->nullable();
            $table->timestamps();
        });

        Schema::create('vessels', function ($table) {
            $table->id();
            $table->string('name');
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
        static $counter = 0;
        $counter++;

        $partner = Partner::create(array_merge([
            'title' => 'Rate Limit Partner '.$counter,
            'allow_manual_submission' => true,
            'allow_image_submission' => true,
        ], $partnerAttrs));

        return PartnerUser::create(array_merge([
            'partner_id' => $partner->id,
            'name' => 'Partner User '.$counter,
            'email' => "ratelimit{$counter}@example.com",
            'password' => Hash::make('password'),
            'is_active' => true,
        ], $userAttrs));
    }

    protected function mockTextractEmpty(): void
    {
        $this->mock(TextractService::class, function ($mock) {
            $mock->shouldReceive('extractTableFromImage')->andReturn([]);
        });
    }

    protected function validManualPayload(string $suffix = 'A'): array
    {
        return [
            'items' => [
                [
                    'trip_date' => '2026-08-25',
                    'name' => 'Crew '.$suffix,
                    'from_location' => 'Port A',
                    'to_location' => 'Port B',
                ],
            ],
        ];
    }

    public function test_image_submission_allows_six_then_returns_429(): void
    {
        Storage::fake('local');
        $this->mockTextractEmpty();
        $user = $this->createPartnerUser();

        for ($i = 1; $i <= 6; $i++) {
            $response = $this->actingAs($user, 'partner')
                ->post(route('partner.requests.image.store'), [
                    'image' => UploadedFile::fake()->image("schedule{$i}.jpg"),
                ]);

            $this->assertNotEquals(429, $response->status(), "Image request {$i} must not be rate limited");
        }

        $response = $this->actingAs($user, 'partner')
            ->post(route('partner.requests.image.store'), [
                'image' => UploadedFile::fake()->image('schedule7.jpg'),
            ]);

        $this->assertEquals(429, $response->status());
    }

    public function test_manual_submission_allows_thirty_then_returns_429(): void
    {
        $user = $this->createPartnerUser();

        for ($i = 1; $i <= 30; $i++) {
            $response = $this->actingAs($user, 'partner')
                ->post(route('partner.requests.store'), $this->validManualPayload((string) $i));

            $this->assertNotEquals(429, $response->status(), "Manual request {$i} must not be rate limited");
        }

        $response = $this->actingAs($user, 'partner')
            ->post(route('partner.requests.store'), $this->validManualPayload('31'));

        $this->assertEquals(429, $response->status());
    }

    public function test_separate_partner_users_have_independent_rate_limit_buckets(): void
    {
        Storage::fake('local');
        $this->mockTextractEmpty();

        $userA = $this->createPartnerUser();
        $userB = $this->createPartnerUser();

        for ($i = 1; $i <= 6; $i++) {
            $this->actingAs($userA, 'partner')
                ->post(route('partner.requests.image.store'), [
                    'image' => UploadedFile::fake()->image("a{$i}.jpg"),
                ]);
        }

        $responseA = $this->actingAs($userA, 'partner')
            ->post(route('partner.requests.image.store'), [
                'image' => UploadedFile::fake()->image('a7.jpg'),
            ]);
        $this->assertEquals(429, $responseA->status());

        $responseB = $this->actingAs($userB, 'partner')
            ->post(route('partner.requests.image.store'), [
                'image' => UploadedFile::fake()->image('b1.jpg'),
            ]);

        $this->assertNotEquals(429, $responseB->status(), 'PartnerUser B must have an independent bucket');
    }

    public function test_partner_request_index_get_is_not_rate_limited(): void
    {
        $user = $this->createPartnerUser();

        for ($i = 1; $i <= 40; $i++) {
            $response = $this->actingAs($user, 'partner')
                ->get(route('partner.requests.index'));

            $this->assertEquals(200, $response->status(), "GET request {$i} must remain unthrottled");
        }
    }
}
