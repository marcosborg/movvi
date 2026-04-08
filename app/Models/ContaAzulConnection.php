<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContaAzulConnection extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONNECTED = 'connected';
    public const STATUS_ERROR = 'error';
    public const STATUS_DISCONNECTED = 'disconnected';

    public $table = 'conta_azul_connections';

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'token_payload' => 'array',
        'oauth_meta' => 'array',
        'expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'last_refreshed_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected $fillable = [
        'company_id',
        'status',
        'access_token',
        'refresh_token',
        'token_type',
        'scope',
        'expires_at',
        'connected_at',
        'last_refreshed_at',
        'last_synced_at',
        'token_payload',
        'oauth_meta',
        'last_error',
        'receivable_contact_id',
        'receivable_financial_account_id',
        'receivable_category_id',
        'receivable_payment_method',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
