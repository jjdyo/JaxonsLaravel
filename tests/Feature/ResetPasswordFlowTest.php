<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_password_reset_and_login_with_new_password(): void
    {
        Notification::fake();

        // Create a verified user with a known password
        $oldPassword = 'OldPassword123!';
        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make($oldPassword),
            'email_verified_at' => now(),
        ]);

        // Request a reset link (this should dispatch our ResetPasswordEmail notification)
        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertSessionHasNoErrors();

        // Grab the token either from the notification mail URL or from the DB via broker
        $token = Password::createToken($user);

        // Visit the reset form (GET) to ensure it's accessible
        $formResponse = $this->get(route('password.reset', [
            'password_callback' => $token,
            'email' => $user->email,
        ]));
        $formResponse->assertStatus(200);

        // Submit the new password
        $newPassword = 'NewPassword456!';
        $resetResponse = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        // After successful reset, app redirects to login page with status
        $resetResponse->assertRedirect(route('login'));

        // Old password should fail
        $this->post(route('login.process'), [
            'email' => $user->email,
            'password' => $oldPassword,
        ])->assertSessionHasErrors('email');

        // New password should work
        $this->post(route('login.process'), [
            'email' => $user->email,
            'password' => $newPassword,
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }
}
