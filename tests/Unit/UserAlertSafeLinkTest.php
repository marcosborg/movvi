<?php

namespace Tests\Unit;

use App\Models\UserAlert;
use PHPUnit\Framework\TestCase;

class UserAlertSafeLinkTest extends TestCase
{
    /** @dataProvider validLinks */
    public function test_it_accepts_safe_alert_links(string $link): void
    {
        $alert = new UserAlert(['alert_link' => $link]);

        $this->assertSame($link, $alert->safe_link);
    }

    /** @dataProvider invalidLinks */
    public function test_it_rejects_invalid_alert_links(string $link): void
    {
        $alert = new UserAlert(['alert_link' => $link]);

        $this->assertNull($alert->safe_link);
    }

    public static function validLinks(): array
    {
        return [
            'internal' => ['/admin/driver-alerts'],
            'https' => ['https://movvi.com.pt/admin/driver-alerts'],
        ];
    }

    public static function invalidLinks(): array
    {
        return [
            'plain text' => ['teste de alerta 25_08'],
            'protocol relative' => ['//example.com/path'],
            'javascript' => ['javascript:alert(1)'],
        ];
    }
}

