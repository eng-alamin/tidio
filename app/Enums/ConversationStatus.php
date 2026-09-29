<?php

namespace App\Enums;

enum ConversationStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case Solved = 'solved';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Pending => 'Pending',
            self::Solved => 'Solved',
            self::Spam => 'Spam',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'blue',
            self::Pending => 'orange',
            self::Solved => 'green',
            self::Spam => 'red',
        };
    }
}
