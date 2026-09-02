<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketAttachment extends Model
{
    protected $fillable = ['support_ticket_message_id', 'path', 'original_name', 'mime_type', 'size'];

    public function message() { return $this->belongsTo(SupportTicketMessage::class, 'support_ticket_message_id'); }
}
