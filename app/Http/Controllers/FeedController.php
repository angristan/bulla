<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Feed\GenerateAtomFeed;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function thread(string $uri): Response
    {
        // Normalize URI
        if (! str_starts_with($uri, '/')) {
            $uri = '/'.$uri;
        }

        // Remove .atom suffix if present
        $uri = preg_replace('/\.atom$/', '', $uri);

        $feed = GenerateAtomFeed::make()->forThread($uri);

        return response($feed, 200, [
            'Content-Type' => 'application/atom+xml; charset=UTF-8',
        ]);
    }

    public function recent(): Response
    {
        $feed = GenerateAtomFeed::make()->recent();

        return response($feed, 200, [
            'Content-Type' => 'application/atom+xml; charset=UTF-8',
        ]);
    }
}
