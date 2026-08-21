<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\PartnerUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Proves default-Partner delete rolls back when PartnerRequest FK RESTRICT fires.
 */
class PartnerPortalDefaultPartnerRollbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
        DB::statement('PRAGMA foreign_keys = ON');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('partner_requests');
        Schema::dropIfExists('partner_users');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    protected function createSchema(): void
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
            $table->boolean('allow_manual_submission')->default(true);
            $table->boolean('allow_image_submission')->default(true);
            $table->timestamps();
        });

        Schema::create('partner_users', function ($table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('partner_requests', function ($table) {
            $table->id();
            $table->string('request_reference')->nullable();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('restrict');
            $table->foreignId('partner_user_id')->nullable()->constrained('partner_users')->onDelete('set null');
            $table->string('submission_method');
            $table->string('status');
            $table->timestamp('submitted_at')->nullable();
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

    public function test_failed_default_partner_delete_rolls_back_default_reassignment(): void
    {
        $this->assertSame(1, (int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys);

        $partnerA = Partner::create([
            'title' => 'Partner A Default',
            'is_default' => true,
        ]);
        $partnerB = Partner::create([
            'title' => 'Partner B Other',
            'is_default' => false,
        ]);

        $partnerUser = PartnerUser::create([
            'partner_id' => $partnerA->id,
            'name' => 'Partner User A',
            'email' => 'partner-a@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        PartnerRequest::create([
            'partner_id' => $partnerA->id,
            'partner_user_id' => $partnerUser->id,
            'request_reference' => 'REQ-900001',
            'submission_method' => PartnerRequest::METHOD_MANUAL,
            'status' => PartnerRequest::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-fk@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('partners.destroy', $partnerA));

        $response->assertRedirect(route('partners.index'));
        $response->assertSessionHas('error');
        $response->assertSessionMissing('success');

        $this->assertDatabaseHas('partners', ['id' => $partnerA->id]);
        $this->assertDatabaseHas('partners', ['id' => $partnerB->id]);

        $this->assertTrue($partnerA->fresh()->is_default, 'Original default must remain default after rollback');
        $this->assertFalse($partnerB->fresh()->is_default, 'Replacement must not remain default after rollback');
        $this->assertSame(1, Partner::where('is_default', true)->count());
    }
}
