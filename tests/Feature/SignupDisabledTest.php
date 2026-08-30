<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SignupDisabledTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('role')->default(2);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function test_login_page_does_not_offer_signup(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Sign up', false)
            ->assertDontSee('Don\'t have an account', false)
            ->assertDontSee(route('register'), false);
    }

    public function test_register_routes_redirect_to_login_and_do_not_create_users(): void
    {
        $this->get(route('register'))
            ->assertRedirect(route('login'));

        $this->post(route('register'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('login'));

        $this->assertSame(0, User::count());
        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }
}
