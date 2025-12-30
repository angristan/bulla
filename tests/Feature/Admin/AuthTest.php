<?php

declare(strict_types=1);

use App\Actions\Admin\SetupAdmin;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    // Setup admin for auth tests
    SetupAdmin::run('admin', 'admin@example.com', 'password123', 'Test Site', 'https://example.com');
});

describe('Admin Authentication', function (): void {
    it('shows login page', function (): void {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Auth/Login'));
    });

    it('authenticates with valid credentials', function (): void {
        $response = $this->post('/admin/login', [
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertTrue(session('admin_authenticated'));
    });

    it('rejects invalid username', function (): void {
        $response = $this->post('/admin/login', [
            'username' => 'wrong',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['username']);
        $this->assertNull(session('admin_authenticated'));
    });

    it('rejects invalid password', function (): void {
        $response = $this->post('/admin/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors(['username']);
        $this->assertNull(session('admin_authenticated'));
    });

    it('redirects unauthenticated users to login', function (): void {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    });

    it('allows access to dashboard when authenticated', function (): void {
        $this->post('/admin/login', [
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $response = $this->get('/admin');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Dashboard'));
    });

    it('logs out successfully', function (): void {
        $this->post('/admin/login', [
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $response = $this->post('/admin/logout');

        $response->assertRedirect('/admin/login');
        $this->assertNull(session('admin_authenticated'));
    });
});
