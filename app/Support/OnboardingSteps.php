<?php

namespace App\Support;

/**
 * The tenant setup checklist ("Finalize your setup" on the app Dashboard).
 * The keys match what the app Dashboard writes to `onboarding_progress.step_key`.
 */
final class OnboardingSteps
{
    /** @var array<string, array{label:string, icon:string, color:string}> */
    public const STEPS = [
        'install_widget' => ['label' => 'Install chat widget', 'icon' => 'bi-plug-fill', 'color' => '#7EA1F5'],
        'connect_mailbox' => ['label' => 'Connect a mailbox', 'icon' => 'bi-envelope-fill', 'color' => '#FFC93C'],
        'connect_domain' => ['label' => 'Verify email domain', 'icon' => 'bi-globe2', 'color' => '#8B5CF6'],
        'invite_team' => ['label' => 'Invite teammates', 'icon' => 'bi-people-fill', 'color' => '#4ADE80'],
        'create_flow' => ['label' => 'Create a flow', 'icon' => 'bi-diagram-3-fill', 'color' => '#F472B6'],
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::STEPS);
    }

    public static function total(): int
    {
        return count(self::STEPS);
    }
}
