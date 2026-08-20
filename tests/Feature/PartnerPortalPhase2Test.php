<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\PartnerUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PartnerPortalPhase2Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Define schema for all necessary tables
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

    /** @test */
    public function test_partner_guard_uses_partner_user_provider()
    {
        $guardConfig = config('auth.guards.partner');
        $this->assertEquals('session', $guardConfig['driver']);
        $this->assertEquals('partner_users', $guardConfig['provider']);

        $providerConfig = config('auth.providers.partner_users');
        $this->assertEquals('eloquent', $providerConfig['driver']);
        $this->assertEquals(\App\Models\PartnerUser::class, $providerConfig['model']);
    }

    /** @test */
    public function test_active_partner_user_can_login()
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'John Doe',
            'email' => 'john@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $response = $this->post(route('partner.login.submit'), [
            'email' => 'john@partner.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('partner.dashboard'));
        $this->assertTrue(Auth::guard('partner')->check());
        $this->assertEquals($partnerUser->id, Auth::guard('partner')->id());
    }

    /** @test */
    public function test_successful_partner_login_redirects_to_dashboard()
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'Jane Doe',
            'email' => 'jane@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $response = $this->post(route('partner.login.submit'), [
            'email' => 'jane@partner.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('partner.dashboard'));
    }

    /** @test */
    public function test_last_login_at_updates_on_successful_login()
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'John Doe',
            'email' => 'john@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $this->assertNull($partnerUser->last_login_at);

        $this->post(route('partner.login.submit'), [
            'email' => 'john@partner.com',
            'password' => 'password123',
        ]);

        $partnerUser->refresh();
        $this->assertNotNull($partnerUser->last_login_at);
    }

    /** @test */
    public function test_remember_option_is_accepted()
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'John Doe',
            'email' => 'john@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $response = $this->post(route('partner.login.submit'), [
            'email' => 'john@partner.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect(route('partner.dashboard'));
        $this->assertTrue(Auth::guard('partner')->check());
    }

    /** @test */
    public function test_invalid_credentials_fail_safely()
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'John Doe',
            'email' => 'john@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $response = $this->post(route('partner.login.submit'), [
            'email' => 'john@partner.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('partner')->check());
    }

    /** @test */
    public function test_inactive_partner_user_cannot_login()
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'Inactive User',
            'email' => 'inactive@partner.com',
            'password' => 'password123',
            'is_active' => false,
        ]);

        $response = $this->post(route('partner.login.submit'), [
            'email' => 'inactive@partner.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('partner')->check());
    }

    /** @test */
    public function test_inactive_partner_user_is_blocked_on_next_request()
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'John Doe',
            'email' => 'john@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        // Login successfully
        Auth::guard('partner')->login($partnerUser);
        $this->assertTrue(Auth::guard('partner')->check());

        // Deactivate user
        $partnerUser->update(['is_active' => false]);

        // Attempt to access protected route
        $response = $this->get(route('partner.dashboard'));

        // Should be logged out and redirected
        $response->assertRedirect(route('partner.login'));
        $this->assertFalse(Auth::guard('partner')->check());
    }

    /** @test */
    public function test_unauthenticated_partner_redirects_to_login()
    {
        $response = $this->get(route('partner.dashboard'));
        $response->assertRedirect(route('partner.login'));
    }

    /** @test */
    public function test_authenticated_partner_visiting_login_redirects_to_dashboard()
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'John Doe',
            'email' => 'john@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        Auth::guard('partner')->login($partnerUser);

        $response = $this->get(route('partner.login'));
        $response->assertRedirect(route('partner.dashboard'));
    }

    /** @test */
    public function test_partner_logout_logs_out_partner_guard()
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'John Doe',
            'email' => 'john@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        Auth::guard('partner')->login($partnerUser);
        $this->assertTrue(Auth::guard('partner')->check());

        $response = $this->post(route('partner.logout'));

        $response->assertRedirect(route('partner.login'));
        $this->assertFalse(Auth::guard('partner')->check());
    }

    /** @test */
    public function test_partner_logout_does_not_logout_web_guard()
    {
        // Login as admin/staff (web guard)
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);
        Auth::guard('web')->login($user);

        // Login as partner (partner guard)
        $partner = Partner::create(['title' => 'Test Partner']);
        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'Partner User',
            'email' => 'partner@test.com',
            'password' => 'password123',
            'is_active' => true,
        ]);
        Auth::guard('partner')->login($partnerUser);

        $this->assertTrue(Auth::guard('web')->check());
        $this->assertTrue(Auth::guard('partner')->check());

        // Logout partner
        $this->post(route('partner.logout'));

        // Web guard should still be authenticated
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertFalse(Auth::guard('partner')->check());
    }

    /** @test */
    public function test_partner_user_cannot_authenticate_through_internal_login()
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'Partner User',
            'email' => 'partner@test.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'partner@test.com',
            'password' => 'password123',
        ]);

        // Should fail - Partner users cannot login through internal auth
        $this->assertFalse(Auth::guard('web')->check());
    }

    /** @test */
    public function test_internal_user_cannot_authenticate_through_partner_guard_without_partner_user_account()
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);

        $response = $this->post(route('partner.login.submit'), [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('partner')->check());
    }

    /** @test */
    public function test_duplicate_partner_user_email_rejected()
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'First User',
            'email' => 'duplicate@test.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'Second User',
            'email' => 'duplicate@test.com',
            'password' => 'password123',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function test_partner_user_password_is_hashed()
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'John Doe',
            'email' => 'john@partner.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $this->assertNotEquals('password123', $partnerUser->password);
        $this->assertTrue(Hash::check('password123', $partnerUser->password));
    }

    /** @test */
    public function test_partner_submission_settings_save_correctly()
    {
        $partner = Partner::create([
            'title' => 'Test Partner',
            'allow_manual_submission' => true,
            'allow_image_submission' => false,
        ]);

        $this->assertTrue($partner->allow_manual_submission);
        $this->assertFalse($partner->allow_image_submission);

        $partner->update([
            'allow_manual_submission' => false,
            'allow_image_submission' => true,
        ]);

        $this->assertFalse($partner->allow_manual_submission);
        $this->assertTrue($partner->allow_image_submission);
    }

    /** @test */
    public function test_dual_guard_deactivated_partner_preserves_web_guard()
    {
        // Login as admin/staff (web guard)
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);
        Auth::guard('web')->login($user);

        // Login as partner (partner guard)
        $partner = Partner::create(['title' => 'Test Partner']);
        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'Partner User',
            'email' => 'partner@test.com',
            'password' => 'password123',
            'is_active' => true,
        ]);
        Auth::guard('partner')->login($partnerUser);

        // Verify both are authenticated
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertTrue(Auth::guard('partner')->check());

        // Deactivate partner user
        $partnerUser->update(['is_active' => false]);

        // Request protected partner route
        $response = $this->get(route('partner.dashboard'));

        // Partner guard should be logged out and redirected
        $response->assertRedirect(route('partner.login'));
        $this->assertFalse(Auth::guard('partner')->check());

        // Web guard MUST still be authenticated
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertEquals($user->id, Auth::guard('web')->id());
    }

    /** @test */
    public function test_dual_guard_partner_logout_preserves_web_guard()
    {
        // Login as admin/staff (web guard)
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);
        Auth::guard('web')->login($user);

        // Login as partner (partner guard)
        $partner = Partner::create(['title' => 'Test Partner']);
        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'Partner User',
            'email' => 'partner@test.com',
            'password' => 'password123',
            'is_active' => true,
        ]);
        Auth::guard('partner')->login($partnerUser);

        // Verify both are authenticated
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertTrue(Auth::guard('partner')->check());

        // Partner logout
        $response = $this->post(route('partner.logout'));

        // Partner guard logged out
        $response->assertRedirect(route('partner.login'));
        $this->assertFalse(Auth::guard('partner')->check());

        // Web guard MUST still be authenticated
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertEquals($user->id, Auth::guard('web')->id());
    }

    /** @test */
    public function test_cannot_edit_partner_user_from_different_partner()
    {
        $partnerA = Partner::create(['title' => 'Partner A']);
        $partnerB = Partner::create(['title' => 'Partner B']);

        $userB = PartnerUser::create([
            'partner_id' => $partnerB->id,
            'name' => 'User B',
            'email' => 'userb@test.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        // Create admin for permission
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);
        $this->actingAs($admin, 'web');

        // Attempt to edit Partner B's user through Partner A's route
        $response = $this->put(route('partners.users.update', [$partnerA, $userB]), [
            'name' => 'Modified Name',
            'email' => 'userb@test.com',
        ]);

        // Should be forbidden
        $response->assertForbidden();
    }

    /** @test */
    public function test_cannot_reset_password_for_partner_user_from_different_partner()
    {
        $partnerA = Partner::create(['title' => 'Partner A']);
        $partnerB = Partner::create(['title' => 'Partner B']);

        $userB = PartnerUser::create([
            'partner_id' => $partnerB->id,
            'name' => 'User B',
            'email' => 'userb@test.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        // Create admin for permission
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);
        $this->actingAs($admin, 'web');

        // Attempt password reset through wrong partner route
        $response = $this->put(route('partners.users.updatePassword', [$partnerA, $userB]), [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        // Should be forbidden
        $response->assertForbidden();
    }

    /** @test */
    public function test_cannot_toggle_status_for_partner_user_from_different_partner()
    {
        $partnerA = Partner::create(['title' => 'Partner A']);
        $partnerB = Partner::create(['title' => 'Partner B']);

        $userB = PartnerUser::create([
            'partner_id' => $partnerB->id,
            'name' => 'User B',
            'email' => 'userb@test.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        // Create admin for permission
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);
        $this->actingAs($admin, 'web');

        // Attempt status toggle through wrong partner route
        $response = $this->patch(route('partners.users.toggleStatus', [$partnerA, $userB]));

        // Should be forbidden
        $response->assertForbidden();
    }

    /** @test */
    public function test_partner_id_comes_from_route_not_request_body()
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        $maliciousPartner = Partner::create(['title' => 'Malicious Partner']);

        // Create admin for permission
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);
        $this->actingAs($admin, 'web');

        // Attempt to create user with malicious partner_id in request body
        $response = $this->post(route('partners.users.store', $partner), [
            'partner_id' => $maliciousPartner->id, // Attempt to override
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => true,
        ]);

        // Should succeed
        $response->assertRedirect(route('partners.users.index', $partner));

        // Verify user belongs to ROUTE partner, not malicious partner
        $createdUser = PartnerUser::where('email', 'test@test.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals($partner->id, $createdUser->partner_id);
        $this->assertNotEquals($maliciousPartner->id, $createdUser->partner_id);
    }

    /** @test */
    public function test_admin_can_deactivate_partner_user()
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'Active User',
            'email' => 'active@test.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        // Create admin for permission
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);
        $this->actingAs($admin, 'web');

        $this->assertTrue($partnerUser->is_active);

        // Deactivate
        $response = $this->patch(route('partners.users.toggleStatus', [$partner, $partnerUser]));

        $response->assertRedirect(route('partners.users.index', $partner));
        $partnerUser->refresh();
        $this->assertFalse($partnerUser->is_active);
    }

    /** @test */
    public function test_admin_can_reactivate_partner_user()
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'Inactive User',
            'email' => 'inactive@test.com',
            'password' => 'password123',
            'is_active' => false,
        ]);

        // Create admin for permission
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 1,
        ]);
        $this->actingAs($admin, 'web');

        $this->assertFalse($partnerUser->is_active);

        // Reactivate
        $response = $this->patch(route('partners.users.toggleStatus', [$partner, $partnerUser]));

        $response->assertRedirect(route('partners.users.index', $partner));
        $partnerUser->refresh();
        $this->assertTrue($partnerUser->is_active);
    }

    /** @test */
    public function test_reactivated_partner_user_can_login()
    {
        $partner = Partner::create(['title' => 'Test Partner']);
        $partnerUser = PartnerUser::create([
            'partner_id' => $partner->id,
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => 'password123',
            'is_active' => false,
        ]);

        // Cannot login when inactive
        $response = $this->post(route('partner.login.submit'), [
            'email' => 'test@test.com',
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('partner')->check());

        // Reactivate
        $partnerUser->update(['is_active' => true]);

        // Can now login
        $response = $this->post(route('partner.login.submit'), [
            'email' => 'test@test.com',
            'password' => 'password123',
        ]);
        $response->assertRedirect(route('partner.dashboard'));
        $this->assertTrue(Auth::guard('partner')->check());
    }

    /** @test */
    public function test_existing_admin_login_still_works_after_partner_auth_changes()
    {
        // Create admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('adminpassword'),
            'role' => 1,
        ]);

        // Admin login through internal /login should still work
        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'adminpassword',
        ]);

        // Should redirect to dashboard
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertEquals($admin->id, Auth::guard('web')->id());
    }
}
