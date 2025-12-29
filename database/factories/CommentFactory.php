<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Thread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        $body = $this->faker->paragraph();

        return [
            'thread_id' => Thread::factory(),
            'parent_id' => null,
            'author' => $this->faker->name(),
            'email' => $this->faker->email(),
            'website' => $this->faker->url(),
            'is_admin' => false,
            'body_markdown' => $body,
            'body_html' => "<p>{$body}</p>",
            'status' => 'approved',
            'email_verified' => false,
            'upvotes' => 0,
            'voters_bloom' => null,
            'notify_replies' => false,
            'remote_addr' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'edit_token' => null,
            'edit_token_expires_at' => null,
            'moderation_token' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function spam(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'spam',
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified' => true,
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'author' => null,
            'email' => null,
            'website' => null,
        ]);
    }

    public function withNotifications(): static
    {
        return $this->state(fn (array $attributes) => [
            'notify_replies' => true,
        ]);
    }
}
