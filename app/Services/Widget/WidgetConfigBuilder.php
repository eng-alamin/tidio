<?php

namespace App\Services\Widget;

use App\Models\OperatingHour;
use App\Models\WidgetTranslation;
use App\Models\Website;
use Carbon\CarbonImmutable;

/**
 * Turns a Website's saved Appearance / Translations / Operating-hours settings into the
 * plain array the loader script and the chat frame render from.
 */
class WidgetConfigBuilder
{
    private const DEFAULT_HEADER = 'Chat with us';

    /** Colour, position and key — the only things the loader (on the customer's page) needs. */
    public function loader(Website $website): array
    {
        $setting = $website->widgetSetting;

        return [
            'key' => $website->widget_key,
            'color' => $this->safeColor($setting?->background_color),
            'position' => $setting?->position === 'left' ? 'left' : 'right',
            'frame_url' => route('widget.frame', ['widgetKey' => $website->widget_key]),
        ];
    }

    /** Everything the chat frame renders. */
    public function frame(Website $website): array
    {
        $setting = $website->widgetSetting;
        $locale = $setting?->default_language ?: 'en';

        $stored = WidgetTranslation::query()
            ->where('website_id', $website->id)
            ->where('locale', $locale)
            ->value('strings') ?? [];

        // The Translations page always saves its stock "Chat with us"; only treat the translated header
        // as an override when someone actually changed it, otherwise Appearance's header would never show.
        $header = $setting?->header ?: self::DEFAULT_HEADER;
        if (! empty($stored['header']) && $stored['header'] !== self::DEFAULT_HEADER) {
            $header = $stored['header'];
        }

        $online = $this->isOnline($website);

        $strings = array_merge([
            'placeholder' => 'Type a message…',
            'send_button' => 'Send',
            'close' => 'Close chat',
            'online' => $setting?->online_status_text ?: 'We are online',
            'offline' => $setting?->offline_status_text ?: 'We are away — leave a message and we will reply by email',
            'email_title' => 'Want a reply by email if you leave?',
            'name_placeholder' => 'Your name',
            'email_placeholder' => 'Your email',
            'email_save' => 'Save',
            'email_skip' => 'Not now',
            'email_thanks' => 'Thanks! We will use this to reply.',
            'retry' => 'Not sent. Tap to retry',
            'sending' => 'Sending…',
            'solved_notice' => 'This conversation was marked as solved. Send a message to start a new one.',
            'error_generic' => 'Something went wrong. Please try again.',
            'unavailable' => 'Chat is not available right now.',
        ], $stored);

        $strings['header'] = $header;

        return [
            'key' => $website->widget_key,
            'color' => $this->safeColor($setting?->background_color),
            'welcome_message' => $setting?->welcome_message ?: 'Hi! How can we help you today?',
            'online' => $online,
            'max_length' => (int) config('widget.message_max_length'),
            'strings' => $strings,
        ];
    }

    /**
     * No operating-hours rows at all = always online. Otherwise online only when "now" (in the
     * workspace's timezone) falls inside one of today's active windows.
     */
    public function isOnline(Website $website): bool
    {
        $workspace = $website->workspace;

        $hours = OperatingHour::query()->where('workspace_id', $workspace->id)->get();

        if ($hours->isEmpty()) {
            return true;
        }

        $now = CarbonImmutable::now($workspace->timezone ?: 'UTC');
        $time = $now->format('H:i:s');

        return $hours
            ->where('day_of_week', $now->dayOfWeek)
            ->where('is_active', true)
            ->contains(function (OperatingHour $row) use ($time) {
                if (! $row->start_time || ! $row->end_time) {
                    return true; // active day with no window = all day
                }

                $start = substr((string) $row->start_time, 0, 8);
                $end = substr((string) $row->end_time, 0, 8);

                return $start <= $end
                    ? ($time >= $start && $time <= $end)
                    : ($time >= $start || $time <= $end); // overnight window
            });
    }

    /** Only ever echo a #rgb / #rrggbb value into CSS / JS. */
    private function safeColor(?string $color): string
    {
        return $color !== null && preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color) === 1
            ? $color
            : '#2B5FE2';
    }
}
