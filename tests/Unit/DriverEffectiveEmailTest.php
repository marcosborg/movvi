<?php

namespace Tests\Unit;

use App\Models\Driver;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class DriverEffectiveEmailTest extends TestCase
{
    public function test_it_prefers_the_driver_email(): void
    {
        $driver = new Driver(['email' => 'driver@example.test']);
        $driver->setRelation('user', new User(['email' => 'user@example.test']));

        $this->assertSame('driver@example.test', $driver->effective_email);
    }

    public function test_it_falls_back_to_the_linked_user_email(): void
    {
        $driver = new Driver(['email' => null]);
        $driver->setRelation('user', new User(['email' => 'user@example.test']));

        $this->assertSame('user@example.test', $driver->effective_email);
    }
}
