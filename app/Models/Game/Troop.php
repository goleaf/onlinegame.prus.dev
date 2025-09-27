<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use sbamtr\LaravelQueryEnrich\QE;
use function sbamtr\LaravelQueryEnrich\c;

class Troop extends Model
{
    use HasFactory;

    protected $fillable = [
        'village_id',
        'unit_type_id',
        'count',
        'in_village',
        'in_attack',
        'in_defense',
        'in_support',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query->selectRaw('
            troops.*,
            (SELECT COUNT(*) FROM troops t2 WHERE t2.village_id = troops.village_id AND t2.quantity > 0) as total_troops_in_village,
            (SELECT SUM(quantity) FROM troops t3 WHERE t3.village_id = troops.village_id) as total_quantity_in_village,
            (SELECT COUNT(*) FROM troops t4 WHERE t4.unit_type_id = troops.unit_type_id AND t4.quantity > 0) as total_troops_of_type,
            (SELECT SUM(quantity) FROM troops t5 WHERE t5.unit_type_id = troops.unit_type_id) as total_quantity_of_type,
            (SELECT AVG(quantity) FROM troops t6 WHERE t6.unit_type_id = troops.unit_type_id AND t6.quantity > 0) as avg_quantity_of_type
        ');
    }

    public function scopeByVillage($query, $villageId)
    {
        return $query->where('village_id', $villageId);
    }

    public function scopeByUnitType($query, $unitTypeId = null)
    {
        return $query->when($unitTypeId, function ($q) use ($unitTypeId) {
            return $q->where('unit_type_id', $unitTypeId);
        });
    }

    public function scopeAvailable($query)
    {
        return $query->where('in_village', '>', 0);
    }

    public function scopeInAttack($query)
    {
        return $query->where('in_attack', '>', 0);
    }

    public function scopeInDefense($query)
    {
        return $query->where('in_defense', '>', 0);
    }

    public function scopeInSupport($query)
    {
        return $query->where('in_support', '>', 0);
    }

    public function scopeByQuantity($query, $minQuantity = null, $maxQuantity = null)
    {
        return $query->when($minQuantity, function ($q) use ($minQuantity) {
            return $q->where('quantity', '>=', $minQuantity);
        })->when($maxQuantity, function ($q) use ($maxQuantity) {
            return $q->where('quantity', '<=', $maxQuantity);
        });
    }

    public function scopeTopTroops($query, $limit = 10)
    {
        return $query->orderBy('quantity', 'desc')->limit($limit);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->whereHas('unitType', function ($typeQ) use ($searchTerm) {
                $typeQ
                    ->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        });
    }

    public function scopeWithUnitTypeInfo($query)
    {
        return $query->with([
            'unitType:id,name,attack_power,defense_power,speed,cost',
            'village:id,name,player_id'
        ]);
    }
}
