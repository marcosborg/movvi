<?php

namespace Tests\Unit;

use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use Tests\TestCase;

class DriverRequiredFieldsRequestTest extends TestCase
{
    /** @dataProvider requestProvider */
    public function test_contact_and_tax_fields_are_required(string $requestClass): void
    {
        $rules = (new $requestClass)->rules();

        foreach (['email', 'phone', 'driver_vat'] as $field) {
            $this->assertContains('required', $rules[$field]);
        }

        $this->assertContains('email', $rules['email']);
    }

    public function requestProvider(): array
    {
        return [
            'create' => [StoreDriverRequest::class],
            'update' => [UpdateDriverRequest::class],
        ];
    }
}
