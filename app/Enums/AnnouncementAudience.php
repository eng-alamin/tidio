<?php

namespace App\Enums;

enum AnnouncementAudience: string
{
    case All = 'all';
    case WorkspaceOwners = 'workspace_owners';
    case TrialUsers = 'trial_users';
}
