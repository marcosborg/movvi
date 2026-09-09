<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketReplied;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_and_admin_can_converse_and_close_ticket(): void
    {
        Notification::fake();
        config(['support.email_notifications' => true]);
        $customer = User::create(['name' => 'Ticket Customer', 'email' => uniqid().'@example.test', 'password' => 'secret', 'verified' => 1, 'verification_token' => 'test']);
        $company = Company::create([
            'name' => 'Ticket Company', 'vat' => uniqid('vat-'), 'address' => 'Test',
            'zip' => '0000-000', 'location' => 'Test', 'email' => uniqid().'@example.test',
            'user_id' => $customer->id,
        ]);
        $admin = User::create(['name' => 'Ticket Admin', 'email' => uniqid().'@example.test', 'password' => 'secret', 'verified' => 1, 'verification_token' => 'test']);
        $adminRole = Role::firstOrCreate(['title' => 'Admin']);
        $customer->roles()->attach($adminRole);
        $admin->roles()->attach($adminRole);

        $this->actingAs($customer)->post(route('admin.support-tickets.store'), [
            'subject' => 'Erro no relatório',
            'message' => 'O valor apresentado não está correto.',
        ])->assertRedirect();

        $ticket = SupportTicket::where('company_id', $company->id)->firstOrFail();
        $this->assertSame(SupportTicket::STATUS_AWAITING_TECHNICAL, $ticket->status);

        $this->actingAs($admin)->post(route('admin.support-tickets.reply', $ticket), [
            'message' => 'Estamos a analisar.',
        ])->assertRedirect();
        $this->assertSame(SupportTicket::STATUS_AWAITING_CUSTOMER, $ticket->fresh()->status);
        $this->assertSame($admin->id, $ticket->fresh()->assigned_to);
        Notification::assertSentTo($customer, SupportTicketReplied::class, function ($notification) use ($ticket, $customer) {
            $mail = $notification->toMail($customer);
            $this->assertSame(route('admin.support-tickets.show', $ticket), $mail->actionUrl);
            $this->assertStringContainsString($ticket->number, $mail->subject);
            $this->assertStringContainsString('Ver e responder ao ticket', $mail->render());
            return true;
        });
        Notification::assertCount(1);

        config(['support.email_notifications' => false]);
        $this->actingAs($admin)->post(route('admin.support-tickets.reply', $ticket), [
            'message' => 'Resposta na sandbox, sem email.',
        ])->assertRedirect();
        Notification::assertCount(1);
        config(['support.email_notifications' => true]);

        $this->actingAs($customer)->post(route('admin.support-tickets.reply', $ticket), [
            'message' => 'Obrigado, envio mais informação.',
        ])->assertRedirect();
        $this->assertSame(SupportTicket::STATUS_AWAITING_TECHNICAL, $ticket->fresh()->status);
        Notification::assertCount(1);

        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        $this->actingAs($admin)->post(route('admin.support-tickets.reply', $ticket), [
            'message' => 'Esta resposta persiste mesmo se o email falhar.',
        ])->assertRedirect()->assertSessionHas('error_message');
        $this->assertTrue($ticket->messages()->where('message', 'Esta resposta persiste mesmo se o email falhar.')->exists());

        $this->actingAs($customer)->patch(route('admin.support-tickets.close', $ticket))->assertRedirect();
        $this->assertSame(SupportTicket::STATUS_CLOSED, $ticket->fresh()->status);
        $this->assertSame($customer->id, $ticket->fresh()->closed_by);
        $this->actingAs($admin)->post(route('admin.support-tickets.reply', $ticket), [
            'message' => 'Nao enviar num ticket encerrado.',
        ])->assertStatus(422);
    }

    public function test_customer_cannot_see_another_company_ticket(): void
    {
        $owner = User::create(['name' => 'Owner', 'email' => uniqid().'@example.test', 'password' => 'secret', 'verified' => 1, 'verification_token' => 'owner']);
        $other = User::create(['name' => 'Other', 'email' => uniqid().'@example.test', 'password' => 'secret', 'verified' => 1, 'verification_token' => 'other']);
        $ownerCompany = Company::create(['name' => 'Owner Co', 'vat' => uniqid('vat-'), 'address' => 'X', 'zip' => '0', 'location' => 'X', 'email' => uniqid().'@x.test', 'user_id' => $owner->id]);
        Company::create(['name' => 'Other Co', 'vat' => uniqid('vat-'), 'address' => 'X', 'zip' => '0', 'location' => 'X', 'email' => uniqid().'@x.test', 'user_id' => $other->id]);
        $ticket = SupportTicket::create(['company_id' => $ownerCompany->id, 'opened_by' => $owner->id, 'subject' => 'Private', 'status' => SupportTicket::STATUS_AWAITING_TECHNICAL]);

        $this->actingAs($other)->get(route('admin.support-tickets.show', $ticket))->assertForbidden();
    }
    public function test_staff_can_open_and_follow_their_own_request(): void
    {
        Notification::fake();
        config(['support.email_notifications' => true]);
        $staff = User::create(['name' => 'Staff Requester', 'email' => uniqid().'@example.test', 'password' => 'secret', 'verified' => 1, 'verification_token' => 'test']);
        $responder = User::create(['name' => 'Responder', 'email' => uniqid().'@example.test', 'password' => 'secret', 'verified' => 1, 'verification_token' => 'test']);
        $role = Role::firstOrCreate(['title' => 'Admin']);
        $staff->roles()->attach($role);
        $responder->roles()->attach($role);
        $company = Company::create(['name' => 'Staff Company', 'vat' => uniqid('vat-'), 'address' => 'X', 'zip' => '0', 'location' => 'X', 'email' => uniqid().'@example.test']);
        $this->actingAs($staff)->get(route('admin.support-tickets.index'))->assertOk()->assertSee('Abrir ticket');
        $this->get(route('admin.support-tickets.create'))->assertOk()->assertSee('Staff Company');
        $body = ['subject' => 'Pedido da equipa', 'message' => 'Preciso de ajuda.'];
        $this->postJson(route('admin.support-tickets.store'), $body)->assertUnprocessable();
        $this->postJson(route('admin.support-tickets.store'), $body + ['company_id' => 99999999])->assertUnprocessable();
        $this->post(route('admin.support-tickets.store'), $body + ['company_id' => $company->id])->assertRedirect();
        $ticket = SupportTicket::where('opened_by', $staff->id)->firstOrFail();
        $this->assertEquals($company->id, $ticket->company_id);
        $this->assertEquals($staff->id, $ticket->messages->first()->sender_id);
        $this->assertSame(SupportTicket::STATUS_AWAITING_TECHNICAL, $ticket->status);
        $this->assertNull($ticket->assigned_to);
        $this->actingAs($responder)->post(route('admin.support-tickets.reply', $ticket), ['message' => 'Resposta técnica.'])->assertRedirect();
        Notification::assertSentTo($staff, SupportTicketReplied::class);
        $this->assertSame(SupportTicket::STATUS_AWAITING_CUSTOMER, $ticket->fresh()->status);
        $this->actingAs($staff)->post(route('admin.support-tickets.reply', $ticket), ['message' => 'Mais informação.'])->assertRedirect();
        $this->assertSame(SupportTicket::STATUS_AWAITING_TECHNICAL, $ticket->fresh()->status);
        $this->assertEquals($responder->id, $ticket->fresh()->assigned_to);
        Notification::assertCount(1);
    }

    public function test_customer_cannot_choose_another_company_when_opening_ticket(): void
    {
        $customer = User::create(['name' => 'Customer', 'email' => uniqid().'@example.test', 'password' => 'secret', 'verified' => 1, 'verification_token' => 'test']);
        $company = Company::create(['name' => 'Own Company', 'vat' => uniqid('vat-'), 'address' => 'X', 'zip' => '0', 'location' => 'X', 'email' => uniqid().'@example.test', 'user_id' => $customer->id]);
        $this->actingAs($customer)->post(route('admin.support-tickets.store'), [
            'subject' => 'Pedido', 'message' => 'Ajuda', 'company_id' => 99999999,
        ])->assertRedirect();
        $this->assertEquals($company->id, SupportTicket::where('opened_by', $customer->id)->firstOrFail()->company_id);
    }

}
