<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_and_admin_can_converse_and_close_ticket(): void
    {
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

        $this->actingAs($customer)->post(route('admin.support-tickets.reply', $ticket), [
            'message' => 'Obrigado, envio mais informação.',
        ])->assertRedirect();
        $this->assertSame(SupportTicket::STATUS_AWAITING_TECHNICAL, $ticket->fresh()->status);

        $this->actingAs($customer)->patch(route('admin.support-tickets.close', $ticket))->assertRedirect();
        $this->assertSame(SupportTicket::STATUS_CLOSED, $ticket->fresh()->status);
        $this->assertSame($customer->id, $ticket->fresh()->closed_by);
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
}
