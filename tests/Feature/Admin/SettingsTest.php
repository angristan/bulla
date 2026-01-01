<?php

declare(strict_types=1);

use App\Actions\Admin\SetupAdmin;
use App\Models\Comment;
use App\Models\ImportMapping;
use App\Models\Setting;
use App\Models\Thread;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    SetupAdmin::run('admin', 'admin@example.com', 'password123', 'Test Site', 'https://example.com');

    $this->post('/admin/login', [
        'username' => 'admin',
        'password' => 'password123',
    ]);
});

describe('Admin Settings', function (): void {
    it('shows settings page', function (): void {
        $response = $this->get('/admin/settings');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Settings/Index')
                ->has('settings')
                ->has('settings.site_name')
                ->has('settings.moderation_mode')
        );
    });

    it('updates site name', function (): void {
        $response = $this->post('/admin/settings', [
            'site_name' => 'New Site Name',
        ]);

        $response->assertRedirect();
        expect(Setting::getValue('site_name'))->toBe('New Site Name');
    });

    it('updates moderation mode', function (): void {
        $response = $this->post('/admin/settings', [
            'moderation_mode' => 'all',
        ]);

        $response->assertRedirect();
        expect(Setting::getValue('moderation_mode'))->toBe('all');
    });

    it('updates spam settings', function (): void {
        $response = $this->post('/admin/settings', [
            'rate_limit_per_minute' => 10,
            'blocked_words' => "spam\nviagra",
        ]);

        $response->assertRedirect();
        expect(Setting::getValue('rate_limit_per_minute'))->toBe('10');
        expect(Setting::getValue('blocked_words'))->toBe("spam\nviagra");
    });

    it('validates moderation mode values', function (): void {
        $response = $this->post('/admin/settings', [
            'moderation_mode' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['moderation_mode']);
    });

    it('validates numeric fields', function (): void {
        $response = $this->post('/admin/settings', [
            'max_depth' => 4, // Max is 3
        ]);

        $response->assertSessionHasErrors(['max_depth']);
    });

    it('clears empty values', function (): void {
        Setting::setValue('custom_css', '.test { color: red; }');

        $response = $this->post('/admin/settings', [
            'custom_css' => '',
        ]);

        $response->assertRedirect();
        // Empty string values delete the setting, so getValue returns default (empty string)
        expect(Setting::getValue('custom_css', ''))->toBe('');
    });

    it('updates accent color', function (): void {
        $response = $this->post('/admin/settings', [
            'accent_color' => '#ef4444',
        ]);

        $response->assertRedirect();
        expect(Setting::getValue('accent_color'))->toBe('#ef4444');
    });

    it('wipes all data', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => Comment::STATUS_APPROVED,
        ]);
        ImportMapping::createMapping('isso', '1', 'thread', $thread->id);

        $response = $this->delete('/admin/settings/wipe');

        $response->assertRedirect();
        expect(Thread::count())->toBe(0);
        expect(Comment::count())->toBe(0);
        expect(ImportMapping::count())->toBe(0);
    });

    it('wipes soft-deleted comments', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => Comment::STATUS_APPROVED,
        ]);
        $comment->delete();

        expect(Comment::withTrashed()->count())->toBe(1);

        $this->delete('/admin/settings/wipe');

        expect(Comment::withTrashed()->count())->toBe(0);
    });

    it('preserves settings after wipe', function (): void {
        Setting::setValue('site_name', 'My Site');
        Thread::create(['uri' => '/test']);

        $this->delete('/admin/settings/wipe');

        expect(Setting::getValue('site_name'))->toBe('My Site');
    });
});

describe('Claim Admin Comments', function (): void {
    it('previews comments matching email', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        Comment::create([
            'thread_id' => $thread->id,
            'author' => 'John',
            'email' => 'john@example.com',
            'body_markdown' => 'Test comment',
            'body_html' => '<p>Test comment</p>',
            'status' => Comment::STATUS_APPROVED,
            'is_admin' => false,
        ]);
        Comment::create([
            'thread_id' => $thread->id,
            'author' => 'Jane',
            'email' => 'jane@example.com',
            'body_markdown' => 'Another comment',
            'body_html' => '<p>Another comment</p>',
            'status' => Comment::STATUS_APPROVED,
            'is_admin' => false,
        ]);

        $response = $this->getJson('/admin/settings/claim-admin/preview?email=john@example.com');

        $response->assertOk()
            ->assertJson([
                'count' => 1,
            ])
            ->assertJsonCount(1, 'comments')
            ->assertJsonPath('comments.0.author', 'John');
    });

    it('previews comments matching author name', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        Comment::create([
            'thread_id' => $thread->id,
            'author' => 'John Doe',
            'body_markdown' => 'Test comment',
            'body_html' => '<p>Test comment</p>',
            'status' => Comment::STATUS_APPROVED,
            'is_admin' => false,
        ]);

        $response = $this->getJson('/admin/settings/claim-admin/preview?author=John+Doe');

        $response->assertOk()
            ->assertJson(['count' => 1]);
    });

    it('excludes already admin comments from preview', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        Comment::create([
            'thread_id' => $thread->id,
            'author' => 'John',
            'email' => 'john@example.com',
            'body_markdown' => 'Admin comment',
            'body_html' => '<p>Admin comment</p>',
            'status' => Comment::STATUS_APPROVED,
            'is_admin' => true,
        ]);

        $response = $this->getJson('/admin/settings/claim-admin/preview?email=john@example.com');

        $response->assertOk()
            ->assertJson(['count' => 0]);
    });

    it('returns empty when no criteria provided', function (): void {
        $response = $this->getJson('/admin/settings/claim-admin/preview');

        $response->assertOk()
            ->assertJson(['count' => 0, 'comments' => []]);
    });

    it('claims comments as admin by email', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'author' => 'John',
            'email' => 'john@example.com',
            'body_markdown' => 'Test comment',
            'body_html' => '<p>Test comment</p>',
            'status' => Comment::STATUS_APPROVED,
            'is_admin' => false,
        ]);

        $response = $this->post('/admin/settings/claim-admin', [
            'email' => 'john@example.com',
        ]);

        $response->assertRedirect();
        $comment->refresh();
        expect($comment->is_admin)->toBeTrue();
    });

    it('claims comments as admin by author name', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'author' => 'John Doe',
            'body_markdown' => 'Test comment',
            'body_html' => '<p>Test comment</p>',
            'status' => Comment::STATUS_APPROVED,
            'is_admin' => false,
        ]);

        $response = $this->post('/admin/settings/claim-admin', [
            'author' => 'John Doe',
        ]);

        $response->assertRedirect();
        $comment->refresh();
        expect($comment->is_admin)->toBeTrue();
    });

    it('claims comments matching both email and author', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        Comment::create([
            'thread_id' => $thread->id,
            'author' => 'John',
            'email' => 'john@example.com',
            'body_markdown' => 'Match both',
            'body_html' => '<p>Match both</p>',
            'status' => Comment::STATUS_APPROVED,
            'is_admin' => false,
        ]);
        Comment::create([
            'thread_id' => $thread->id,
            'author' => 'John',
            'email' => 'different@example.com',
            'body_markdown' => 'Match author only',
            'body_html' => '<p>Match author only</p>',
            'status' => Comment::STATUS_APPROVED,
            'is_admin' => false,
        ]);

        $response = $this->post('/admin/settings/claim-admin', [
            'email' => 'john@example.com',
            'author' => 'John',
        ]);

        $response->assertRedirect();
        // Only the first comment should be claimed (matches both)
        expect(Comment::where('is_admin', true)->count())->toBe(1);
    });

    it('returns error when no criteria provided for claim', function (): void {
        $response = $this->post('/admin/settings/claim-admin', []);

        $response->assertRedirect()
            ->assertSessionHas('error');
    });
});
