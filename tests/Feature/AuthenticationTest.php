<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_and_registration_is_disabled(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/tasks')->assertRedirect('/login');
        $this->get('/register')->assertNotFound();
    }

    public function test_invalid_credentials_show_one_generic_error(): void
    {
        $this->post('/login', ['email' => 'nobody@example.test', 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => 'The email or password is incorrect.']);
    }

    public function test_each_role_is_sent_to_its_main_screen(): void
    {
        $officer = User::factory()->create(['role' => 'officer', 'password' => 'password']);
        $supervisor = User::factory()->create(['role' => 'supervisor', 'password' => 'password']);

        $this->post('/login', ['email' => $officer->email, 'password' => 'password'])->assertRedirect('/my-tasks');
        $this->post('/logout');
        $this->post('/login', ['email' => $supervisor->email, 'password' => 'password'])->assertRedirect('/supervisor');
    }

    public function test_intended_page_is_restored_after_login(): void
    {
        $officer = User::factory()->create(['role' => 'officer', 'password' => 'password']);
        $this->get('/tasks/create')->assertRedirect('/login');
        $this->post('/login', ['email' => $officer->email, 'password' => 'password'])->assertRedirect('/tasks/create');
    }

    public function test_logout_invalidates_session_and_prevents_cached_task_pages(): void
    {
        $user = User::factory()->create(['role' => 'officer']);
        $this->actingAs($user)->post('/logout')->assertRedirect('/login')->assertSessionHas('status', 'You have been logged out.');
        $this->assertGuest();
        $this->get('/tasks')->assertRedirect('/login');
    }
}
