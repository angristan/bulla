<?php

declare(strict_types=1);

use App\Models\Setting;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('POST /auth/logout', function (): void {
    it('clears commenter session data', function (): void {
        $commenterData = [
            'github_id' => '12345',
            'github_username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ];

        $response = $this->withSession(['commenter' => $commenterData])
            ->post('/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify session is cleared
        $this->assertNull(session('commenter'));
    });

    it('returns success even when not logged in', function (): void {
        $response = $this->post('/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    });

    it('does not require CSRF token', function (): void {
        // This test verifies the CSRF exception is working
        // by making a request without the CSRF token
        $response = $this->post('/auth/logout');

        // Should not return 419 (CSRF mismatch)
        $response->assertStatus(200);
    });
});

describe('POST /api/threads/{uri}/comments with GitHub commenter', function (): void {
    beforeEach(function (): void {
        Setting::setValue('github_client_id', 'test-client-id');
        Setting::setValue('github_client_secret', 'test-client-secret');
        Setting::setValue('enable_github_login', 'true');
    });

    it('stores github_id and github_username on comment', function (): void {
        $commenterData = [
            'github_id' => '12345',
            'github_username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ];

        $response = $this->withSession(['commenter' => $commenterData])
            ->postJson('/api/threads/test-page/comments', [
                'body' => 'Hello from GitHub!',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'is_github_user' => true,
                'github_username' => 'testuser',
                'author' => 'Test User',
            ]);

        // Verify avatar URL is derived from username
        $data = $response->json();
        expect($data['avatar'])->toBe('https://github.com/testuser.png');

        // Verify database
        $this->assertDatabaseHas('comments', [
            'github_id' => '12345',
            'github_username' => 'testuser',
            'author' => 'Test User',
            'email' => 'test@example.com',
        ]);
    });

    it('uses commenter session data over form data', function (): void {
        $commenterData = [
            'github_id' => '12345',
            'github_username' => 'testuser',
            'name' => 'GitHub User',
            'email' => 'github@example.com',
        ];

        $response = $this->withSession(['commenter' => $commenterData])
            ->postJson('/api/threads/test-page/comments', [
                'body' => 'Test comment',
                'author' => 'Form Author',
                'email' => 'form@example.com',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'author' => 'GitHub User', // Uses session, not form
            ]);

        $this->assertDatabaseHas('comments', [
            'author' => 'GitHub User',
            'email' => 'github@example.com',
        ]);
    });
});

describe('GET /api/config with commenter', function (): void {
    beforeEach(function (): void {
        Setting::setValue('github_client_id', 'test-client-id');
        Setting::setValue('github_client_secret', 'test-client-secret');
        Setting::setValue('enable_github_login', 'true');
    });

    it('returns commenter data when logged in via GitHub', function (): void {
        $commenterData = [
            'github_id' => '12345',
            'github_username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ];

        $response = $this->withSession(['commenter' => $commenterData])
            ->getJson('/api/config');

        $response->assertStatus(200)
            ->assertJson([
                'github_auth_enabled' => true,
                'commenter' => $commenterData,
            ]);
    });

    it('returns null commenter when not logged in', function (): void {
        $response = $this->getJson('/api/config');

        $response->assertStatus(200)
            ->assertJson([
                'github_auth_enabled' => true,
                'commenter' => null,
            ]);
    });

    it('returns commenter data even in guest mode', function (): void {
        $commenterData = [
            'github_id' => '12345',
            'github_username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ];

        $response = $this->withSession(['commenter' => $commenterData])
            ->getJson('/api/config?guest=1');

        $response->assertStatus(200)
            ->assertJson([
                'commenter' => $commenterData,
                'is_admin' => false, // Guest mode hides admin
            ]);
    });

    it('returns github_auth_enabled false when disabled', function (): void {
        Setting::setValue('enable_github_login', 'false');

        $response = $this->getJson('/api/config');

        $response->assertStatus(200)
            ->assertJson([
                'github_auth_enabled' => false,
            ]);
    });

});

describe('GET /api/config without GitHub credentials', function (): void {
    it('returns github_auth_enabled false when credentials missing', function (): void {
        // No GitHub credentials set

        $response = $this->getJson('/api/config');

        $response->assertStatus(200)
            ->assertJson([
                'github_auth_enabled' => false,
            ]);
    });
});
