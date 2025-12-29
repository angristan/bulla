<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Thread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Thread>
 */
class ThreadFactory extends Factory
{
    protected $model = Thread::class;

    public function definition(): array
    {
        return [
            'uri' => '/'.$this->faker->slug(),
            'title' => $this->faker->sentence(),
            'url' => $this->faker->url(),
        ];
    }
}
