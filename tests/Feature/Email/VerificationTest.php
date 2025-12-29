<?php

declare(strict_types=1);

namespace Tests\Feature\Email;

use App\Actions\Email\SendVerificationEmail;
use App\Actions\Email\VerifyEmail;
use App\Mail\CommentVerificationMail;
use App\Models\Comment;
use App\Models\EmailVerification;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_verification_email(): void
    {
        Mail::fake();

        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'email' => 'test@example.com',
            'email_verified' => false,
        ]);

        $verification = SendVerificationEmail::run($comment);

        $this->assertNotNull($verification);
        $this->assertEquals('test@example.com', $verification->email);
        $this->assertNotNull($verification->token);
        $this->assertTrue($verification->expires_at->isFuture());

        Mail::assertSent(CommentVerificationMail::class, function ($mail) use ($comment) {
            return $mail->comment->id === $comment->id;
        });
    }

    public function test_does_not_send_verification_email_if_no_email(): void
    {
        Mail::fake();

        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'email' => null,
        ]);

        $verification = SendVerificationEmail::run($comment);

        $this->assertNull($verification);
        Mail::assertNothingSent();
    }

    public function test_does_not_send_verification_email_if_already_verified(): void
    {
        Mail::fake();

        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'email' => 'test@example.com',
            'email_verified' => true,
        ]);

        $verification = SendVerificationEmail::run($comment);

        $this->assertNull($verification);
        Mail::assertNothingSent();
    }

    public function test_verify_email_marks_comments_as_verified(): void
    {
        $thread = Thread::factory()->create();

        // Create verification token
        $verification = EmailVerification::create([
            'email' => 'test@example.com',
            'token' => 'test-token-123',
            'expires_at' => now()->addDays(7),
        ]);

        // Create comments with this email
        $comment1 = Comment::factory()->create([
            'thread_id' => $thread->id,
            'email' => 'test@example.com',
            'email_verified' => false,
        ]);
        $comment2 = Comment::factory()->create([
            'thread_id' => $thread->id,
            'email' => 'test@example.com',
            'email_verified' => false,
        ]);

        $result = VerifyEmail::run('test-token-123');

        $this->assertTrue($result);

        $comment1->refresh();
        $comment2->refresh();
        $verification->refresh();

        $this->assertTrue($comment1->email_verified);
        $this->assertTrue($comment2->email_verified);
        $this->assertNotNull($verification->verified_at);
    }

    public function test_verify_email_fails_with_invalid_token(): void
    {
        $result = VerifyEmail::run('invalid-token');

        $this->assertFalse($result);
    }

    public function test_verify_email_fails_with_expired_token(): void
    {
        EmailVerification::create([
            'email' => 'test@example.com',
            'token' => 'expired-token',
            'expires_at' => now()->subDay(),
        ]);

        $result = VerifyEmail::run('expired-token');

        $this->assertFalse($result);
    }

    public function test_verification_endpoint_redirects_on_success(): void
    {
        EmailVerification::create([
            'email' => 'test@example.com',
            'token' => 'valid-token',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->get('/verify/valid-token');

        $response->assertRedirect('/');
        $response->assertSessionHas('success');
    }

    public function test_verification_endpoint_redirects_on_failure(): void
    {
        $response = $this->get('/verify/invalid-token');

        $response->assertRedirect('/');
        $response->assertSessionHas('error');
    }

    public function test_reuses_existing_verification_token(): void
    {
        Mail::fake();

        $verification = EmailVerification::create([
            'email' => 'test@example.com',
            'token' => 'existing-token',
            'expires_at' => now()->addDays(7),
        ]);

        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'email' => 'test@example.com',
            'email_verified' => false,
        ]);

        $newVerification = SendVerificationEmail::run($comment);

        $this->assertEquals($verification->id, $newVerification->id);
        $this->assertEquals('existing-token', $newVerification->token);
    }

    public function test_regenerates_expired_verification_token(): void
    {
        Mail::fake();

        $verification = EmailVerification::create([
            'email' => 'test@example.com',
            'token' => 'expired-token',
            'expires_at' => now()->subDay(),
        ]);

        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'email' => 'test@example.com',
            'email_verified' => false,
        ]);

        $newVerification = SendVerificationEmail::run($comment);

        $this->assertEquals($verification->id, $newVerification->id);
        $this->assertNotEquals('expired-token', $newVerification->token);
        $this->assertTrue($newVerification->expires_at->isFuture());
    }
}
