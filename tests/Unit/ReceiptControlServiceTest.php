<?php

namespace Tests\Unit;

use App\Models\Receipt;
use App\Services\ReceiptControlService;
use Tests\TestCase;

class ReceiptControlServiceTest extends TestCase
{
    public function test_positive_required_value_without_receipt_is_missing(): void
    {
        $service = new ReceiptControlService();

        $this->assertSame(
            ReceiptControlService::STATUS_MISSING,
            $service->statusFor(null, 10.01)
        );
    }

    public function test_zero_or_negative_required_value_without_receipt_is_not_required(): void
    {
        $service = new ReceiptControlService();

        $this->assertSame(
            ReceiptControlService::STATUS_NOT_REQUIRED,
            $service->statusFor(null, 0.0)
        );

        $this->assertSame(
            ReceiptControlService::STATUS_NOT_REQUIRED,
            $service->statusFor(null, -5.0)
        );
    }

    public function test_receipt_status_reflects_submission_verification_and_payment(): void
    {
        $service = new ReceiptControlService();

        $this->assertSame(
            ReceiptControlService::STATUS_SUBMITTED,
            $service->statusFor(new Receipt(['verified' => false, 'paid' => false]), 0.0)
        );

        $this->assertSame(
            ReceiptControlService::STATUS_VERIFIED,
            $service->statusFor(new Receipt(['verified' => true, 'paid' => false]), 0.0)
        );

        $this->assertSame(
            ReceiptControlService::STATUS_PAID,
            $service->statusFor(new Receipt(['verified' => true, 'paid' => true]), 0.0)
        );
    }
}
