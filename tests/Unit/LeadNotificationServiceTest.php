<?php

namespace Tests\Unit;

use App\Models\PageForm;
use App\Notifications\pageContact;
use App\Services\LeadNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Notification as BaseNotification;
use Tests\TestCase;

class LeadNotificationServiceTest extends TestCase
{
    public function test_it_sends_a_lead_notification_to_the_configured_email(): void
    {
        Notification::fake();
        config(['leads.notification_email' => 'leads@example.test']);
        $lead = new PageForm(['name' => 'Teste', 'email' => 'lead@example.test', 'phone' => '910000000']);
        $lead->id = 123;

        $sent = app(LeadNotificationService::class)->send($lead, new pageContact($lead));

        $this->assertTrue($sent);
        Notification::assertSentOnDemand(pageContact::class, function ($notification, $channels, $notifiable) {
            return $notifiable->routes['mail'] === 'leads@example.test';
        });
    }

    public function test_it_uses_the_movvi_mailbox_when_cached_config_does_not_include_leads(): void
    {
        Notification::fake();
        config()->offsetUnset('leads');
        $lead = new PageForm(['name' => 'Teste', 'email' => 'lead@example.test', 'phone' => '910000000']);

        $this->assertTrue(app(LeadNotificationService::class)->send($lead, new pageContact($lead)));
        Notification::assertSentOnDemand(pageContact::class, function ($notification, $channels, $notifiable) {
            return $notifiable->routes['mail'] === 'movvi.pt@gmail.com';
        });
    }

    public function test_an_invalid_recipient_does_not_interrupt_the_lead_flow(): void
    {
        Notification::fake();
        Log::shouldReceive('warning')->once();
        config(['leads.notification_email' => 'invalid']);
        $lead = new PageForm(['name' => 'Teste', 'email' => 'lead@example.test', 'phone' => '910000000']);

        $this->assertFalse(app(LeadNotificationService::class)->send($lead, new pageContact($lead)));
        Notification::assertNothingSent();
    }

    public function test_a_mail_failure_does_not_interrupt_the_lead_flow(): void
    {
        config(['leads.notification_email' => 'leads@example.test']);
        Log::shouldReceive('warning')->once();
        $lead = new PageForm(['name' => 'Teste', 'email' => 'lead@example.test', 'phone' => '910000000']);
        $failingNotification = new class extends BaseNotification {
            public function via($notifiable): array
            {
                return ['mail'];
            }

            public function toMail($notifiable)
            {
                throw new \RuntimeException('SMTP indisponível');
            }
        };

        $this->assertFalse(app(LeadNotificationService::class)->send($lead, $failingNotification));
    }
}
