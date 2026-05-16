<?php

namespace Tests\Unit;

use App\Rules\EmailList;
use Filament\Forms\Components\TextInput;
use PHPUnit\Framework\TestCase;

class EmailListRuleTest extends TestCase
{
    public function test_valid_email_list_passes(): void
    {
        $failures = [];

        (new EmailList)->validate(
            'email',
            'one@example.com, two@example.com; three@example.com',
            function (string $message) use (&$failures): void {
                $failures[] = $message;
            },
        );

        $this->assertSame([], $failures);
    }

    public function test_invalid_email_list_fails(): void
    {
        $failures = [];

        (new EmailList)->validate(
            'email',
            'one@example.com, not-an-email',
            function (string $message) use (&$failures): void {
                $failures[] = $message;
            },
        );

        $this->assertNotSame([], $failures);
    }

    public function test_rule_can_be_used_by_filament_text_input(): void
    {
        $rules = TextInput::make('email')
            ->rule(new EmailList)
            ->getValidationRules();

        $this->assertContainsOnlyInstancesOf(EmailList::class, [$rules[1]]);
    }
}
