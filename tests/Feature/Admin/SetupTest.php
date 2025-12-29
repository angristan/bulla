<?php

declare(strict_types=1);

use App\Models\Setting;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Admin Setup', function (): void {
    it('shows setup page when not configured', function (): void {
        $response = $this->get('/admin/setup');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Setup'));
    });

    it('redirects to setup from login when not configured', function (): void {
        $response = $this->get('/admin/login');

        $response->assertRedirect('/admin/setup');
    });

    it('completes setup successfully', function (): void {
        $response = $this->post('/admin/setup', [
            'site_name' => 'My Blog',
            'site_url' => 'https://myblog.com',
            'username' => 'admin',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/admin');

        $this->assertDatabaseHas('settings', [
            'key' => 'admin_username',
            'value' => 'admin',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'site_name',
            'value' => 'My Blog',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'setup_complete',
            'value' => 'true',
        ]);
    });

    it('requires all fields', function (): void {
        $response = $this->post('/admin/setup', []);

        $response->assertSessionHasErrors(['site_name', 'site_url', 'username', 'password']);
    });

    it('validates password confirmation', function (): void {
        $response = $this->post('/admin/setup', [
            'site_name' => 'My Blog',
            'site_url' => 'https://myblog.com',
            'username' => 'admin',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors(['password']);
    });

    it('validates minimum password length', function (): void {
        $response = $this->post('/admin/setup', [
            'site_name' => 'My Blog',
            'site_url' => 'https://myblog.com',
            'username' => 'admin',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
    });

    it('redirects to dashboard when already setup', function (): void {
        Setting::setValue('setup_complete', 'true');

        $response = $this->get('/admin/setup');

        $response->assertRedirect('/admin');
    });

    it('auto-logs in after setup', function (): void {
        $this->post('/admin/setup', [
            'site_name' => 'My Blog',
            'site_url' => 'https://myblog.com',
            'username' => 'admin',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response = $this->get('/admin');

        $response->assertOk();
    });
});
