<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'codigo_inmueble',
        'price',
        'superficie_util',
        'superficie_construida',
        'ambientes',
        'habitaciones',
        'banos',
        'cocheras',
        'ano_construccion',
        'operacion',
        'comision',
        'taxes',
        'allow_purchase',
        'is_public',
        'downloadable',
        'downloadable_file',
        'description',
        'category_id',
        'added_by',
        'last_updated_by',
        'hsn_sac_code',
        'default_image',
        'sku',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'superficie_util' => 'decimal:2',
        'superficie_construida' => 'decimal:2',
        'comision' => 'decimal:2',
        'taxes' => 'decimal:2',
        'allow_purchase' => 'boolean',
        'is_public' => 'boolean',
        'downloadable' => 'boolean',
        'ano_construccion' => 'integer',
    ];

    /**
     * Relación uno a uno con ProductLocation
     */
    public function location(): HasOne
    {
        return $this->hasOne(ProductLocation::class);
    }

    /**
     * Relación con categoría
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Relación con usuario que agregó
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Relación con último usuario que actualizó
     */
    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * Scope para productos públicos
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope para productos con ubicación
     */
    public function scopeWithLocation($query)
    {
        return $query->has('location');
    }

    /**
     * Verificar si tiene ubicación
     */
    public function hasLocation(): bool
    {
        return $this->location()->exists();
    }
}