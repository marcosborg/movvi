<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Card extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'cards';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'type',
        'code',
        'company_id',
        'vehicle_item_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public const TYPE_RADIO = [
        'Cartão Prio Frota' => 'Cartão Prio Frota',
        'Cartão Prio Eletric' => 'Cartão Prio Eletric',
        'Cartão Prio Virtual' => 'Cartão Prio Virtual',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function vehicle_item()
    {
        return $this->belongsTo(VehicleItem::class, 'vehicle_item_id');
    }

    // App\Models\Card.php

    public function combustionTransactions()
    {
        // foreign key na tabela de abastecimentos = 'card' (código)
        // local key na tabela de cartões = 'code'
        return $this->hasMany(CombustionTransaction::class, 'card', 'code');
    }

}
