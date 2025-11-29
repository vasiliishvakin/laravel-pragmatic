<?php

declare(strict_types=1);

namespace Pragmatic\StateMachine\Examples;

/**
 * Example: Chat bot state without strict transition rules.
 *
 * This demonstrates level 2 complexity - enum for type safety
 * but flexible transitions (can jump between any states).
 */
enum ChatState: string
{
    case Idle = 'idle';
    case AskingName = 'asking_name';
    case AskingEmail = 'asking_email';
    case AskingLanguage = 'asking_language';
    case AskingReferralCode = 'asking_referral_code';
    case Complete = 'complete';

    /**
     * Get the prompt message for this state.
     */
    public function prompt(): string
    {
        return match ($this) {
            self::Idle => 'Welcome! How can I help you?',
            self::AskingName => 'What is your name?',
            self::AskingEmail => 'What is your email address?',
            self::AskingLanguage => 'What language do you want to learn?',
            self::AskingReferralCode => 'Enter your referral code:',
            self::Complete => 'All set! You can now start using the bot.',
        };
    }

    /**
     * Get placeholder text for input.
     */
    public function placeholder(): string
    {
        return match ($this) {
            self::AskingName => 'Your name',
            self::AskingEmail => 'your@email.com',
            self::AskingLanguage => 'e.g., en, es, he',
            self::AskingReferralCode => 'Referral code',
            default => '',
        };
    }
}
