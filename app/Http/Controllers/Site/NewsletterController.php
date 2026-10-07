<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $back = $this->backToForm($request);

        // Honeypot: pretend it worked.
        if (filled($request->input('website'))) {
            return $back->with('newsletter_status', 'Thanks for subscribing!');
        }

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc', 'max:190'],
        ]);

        if ($validator->fails()) {
            return $back->withErrors($validator, 'newsletter')->withInput($request->only('email'));
        }

        $email = Str::lower(trim($request->input('email')));
        $subscriber = NewsletterSubscriber::where('email', $email)->first();

        if (! $subscriber) {
            NewsletterSubscriber::create(['email' => $email, 'subscribed_at' => now()]);
        } elseif ($subscriber->unsubscribed_at !== null) {
            // Came back after unsubscribing.
            $subscriber->update(['subscribed_at' => now(), 'unsubscribed_at' => null]);
        }
        // Already subscribed: say the same thing, so the form cannot be used to probe who is on the list.

        return $back->with('newsletter_status', 'Thanks for subscribing!');
    }

    /** Target of a signed link; nothing sends these yet. Build it with URL::signedRoute('site.newsletter.unsubscribe', $subscriber). */
    public function unsubscribe(NewsletterSubscriber $subscriber): RedirectResponse
    {
        if ($subscriber->unsubscribed_at === null) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        return redirect()->route('home')->with('newsletter_status', 'You have been unsubscribed.');
    }

    /** Back to the page the form was on, scrolled to the footer form; same-site referrers only. */
    private function backToForm(Request $request): RedirectResponse
    {
        $previous = strtok((string) url()->previous(), '#');
        $target = Str::startsWith($previous, url('/')) ? $previous : url('/');

        return redirect()->to($target.'#newsletter');
    }
}
