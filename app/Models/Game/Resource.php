<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'village_id',
        'type',
        'amount',
        'production_rate',
        'storage_capacity',
        'level',
        'last_updated',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];

    public function village()
    {
        return $this->belongsTo(Village::class);
    }
}
