<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use SoftDeletes;

    public const STATUS_AWAITING_TECHNICAL = 'awaiting_technical';
    public const STATUS_AWAITING_CUSTOMER = 'awaiting_customer';
    public const STATUS_CLOSED = 'closed';

    public const STATUS_LABELS = [
        self::STATUS_AWAITING_TECHNICAL => 'Aguarda resposta técnica',
        self::STATUS_AWAITING_CUSTOMER => 'Aguarda resposta do cliente',
        self::STATUS_CLOSED => 'Encerrado',
    ];

    protected $fillable = [
        'company_id', 'opened_by', 'assigned_to', 'closed_by', 'subject',
        'status', 'last_message_at', 'closed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function opener() { return $this->belongsTo(User::class, 'opened_by'); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function closer() { return $this->belongsTo(User::class, 'closed_by'); }
    public function messages() { return $this->hasMany(SupportTicketMessage::class)->orderBy('created_at'); }

    public function getNumberAttribute(): string
    {
        return 'TKT-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
