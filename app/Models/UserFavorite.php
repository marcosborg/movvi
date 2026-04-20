<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFavorite extends Model
{
    use HasFactory;

    public $table = 'user_favorites';

    protected $fillable = [
        'user_id',
        'label',
        'url',
        'route_name',
        'route_params',
        'active_pattern',
        'icon',
        'sort_order',
    ];

    protected $casts = [
        'route_params' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
