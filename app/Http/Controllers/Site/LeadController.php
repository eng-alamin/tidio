<?php

namespace App\Http\Controllers\Site;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactSalesLead;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Public "Contact sales" and "Contact" forms. Both land in contact_sales_leads, so they show up
 * in the Super Admin Support Inbox; source_page tells them apart. Team size / topic have no
 * column of their own and are written as the first lines of `message`.
 */
class LeadController extends Controller
{
    private const TEAM_SIZES = ['1–10', '11–50', '51–200', '200+'];

    private const TOPICS = ['General question', 'Sales & pricing', 'Technical support', 'Partnership'];

    public function showContactSales(): View
    {
        return view('site.pages.contact-sales');
    }

    public function showContact(): View
    {
        return view('site.pages.contact');
    }

    public function storeContactSales(Request $request): RedirectResponse
    {
        if ($this->isBot($request)) {
            return $this->thanks('site.contact-sales');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'company' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'team_size' => ['nullable', 'string', Rule::in(self::TEAM_SIZES)],
            'message' => ['nullable', 'string', 'max:3000'],
        ]);

        $this->save($data, '/contact-sales', [
            'Team size' => $data['team_size'] ?? null,
        ]);

        return $this->thanks('site.contact-sales');
    }

    public function storeContact(Request $request): RedirectResponse
    {
        if ($this->isBot($request)) {
            return $this->thanks('site.contact');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'topic' => ['required', 'string', Rule::in(self::TOPICS)],
            'message' => ['required', 'string', 'min:5', 'max:3000'],
        ]);

        $this->save($data, '/contact', [
            'Topic' => $data['topic'],
        ]);

        return $this->thanks('site.contact');
    }

    /** Hidden "website" field: humans leave it empty, form-filling bots do not. */
    private function isBot(Request $request): bool
    {
        return filled($request->input('website'));
    }

    /** @param array<string, string|null> $meta */
    private function save(array $data, string $sourcePage, array $meta): ContactSalesLead
    {
        $header = collect($meta)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value, $label) => "{$label}: {$value}")
            ->implode("\n");

        $message = trim($header."\n\n".($data['message'] ?? ''));

        return ContactSalesLead::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'company' => $data['company'] ?? null,
            'phone' => $data['phone'] ?? null,
            'message' => $message !== '' ? $message : null,
            'source_page' => $sourcePage,
            'status' => LeadStatus::New,
        ]);
    }

    private function thanks(string $route): RedirectResponse
    {
        return redirect()->route($route)->with('lead_status', 'Thanks! Your message is in. We usually reply within one business day.');
    }
}
