<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Email\ModerateViaEmail;
use App\Actions\Email\Unsubscribe;
use App\Actions\Email\VerifyEmail;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;

class EmailController extends Controller
{
    public function verify(string $token): RedirectResponse
    {
        $verified = VerifyEmail::run($token);

        if ($verified) {
            return redirect('/')->with('success', 'Your email has been verified!');
        }

        return redirect('/')->with('error', 'Invalid or expired verification link.');
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $unsubscribed = Unsubscribe::run($token);

        if ($unsubscribed) {
            return redirect('/')->with('success', 'You have been unsubscribed from reply notifications.');
        }

        return redirect('/')->with('error', 'Invalid unsubscribe link.');
    }

    public function approve(Comment $comment, string $token): RedirectResponse
    {
        $approved = ModerateViaEmail::make()->approve($comment, $token);

        if ($approved) {
            return redirect('/admin/comments')->with('success', 'Comment approved successfully.');
        }

        return redirect('/admin/comments')->with('error', 'Invalid moderation link.');
    }

    public function delete(Comment $comment, string $token): RedirectResponse
    {
        $deleted = ModerateViaEmail::make()->delete($comment, $token);

        if ($deleted) {
            return redirect('/admin/comments')->with('success', 'Comment deleted successfully.');
        }

        return redirect('/admin/comments')->with('error', 'Invalid moderation link.');
    }
}
