<?php

namespace App\Models;

use App\Traits\HasCompany;
use App\Traits\CustomFieldsTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Purchase\Entities\PurchaseStockAdjustment;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\Product
 *
 * @property int $id
 * @property string $name
 * @property string $price
 * @property string|null $taxes
 * @property int $allow_purchase
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $description
 * @property int|null $unit_id
 * @property int|null $category_id
 * @property int|null $sub_category_id
 * @property int|null $added_by
 * @property int|null $last_updated_by
 * @property string|null $hsn_sac_code
 * @property string|null $sku
 * @property-read mixed $icon
 * @property-read mixed $total_amount
 * @property-read \App\Models\Tax $tax
 * @method static \Database\Factories\ProductFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereAllowPurchase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereHsnSacCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereLastUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereSubCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereTaxes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereUpdatedAt($value)
 * @property-read \App\Models\ProductCategory|null $category
 * @property string|null $image
 * @property-read mixed $image_url
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereImage($value)
 * @property int $downloadable
 * @property string|null $downloadable_file
 * @property string|null $default_image
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductFiles[] $files
 * @property-read int|null $files_count
 * @property-read mixed $download_file_url
 * @property-read mixed $extras
 * @property-read \App\Models\ProductSubCategory|null $subCategory
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereDefaultImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereDownloadable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereDownloadableFile($value)
 * @property int|null $company_id
 * @property-read \App\Models\Company|null $company
 * @property-read mixed $tax_list
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereCompanyId($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Lead> $leads
 * @property-read int|null $leads_count
 * @property-read \App\Models\UnitType|null $unit
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereUnitId($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderItems> $orderItem
 * @property-read int|null $order_item_count
 * @mixin \Eloquent
 */
class Product extends BaseModel
{
    use HasCompany;
    use HasFactory, CustomFieldsTrait;

    protected $table = 'products';
    const FILE_PATH = 'products';

    /**
     * CAMPOS PERMITIDOS
     */
    protected $fillable = [
        'name',
        'codigo_inmueble',
        'price',
        'description',
        'taxes',
        'allow_purchase',
        'superficie_util',
        'superficie_construida',
        'ambientes',
        'habitaciones',
        'banos',
        'cocheras',
        'ano_construccion',
        'operacion',
        'comision',
        'is_public',
        'category_id',          // <- IMPORTANTE
        'sub_category_id',
        'unit_id',
        'sku',
        'hsn_sac_code',
        'downloadable',
        'downloadable_file',
        'default_image',              // <- IMPORTANTE
        'added_by',             // <- IMPORTANTE
        'last_updated_by',      // <- IMPORTANTE
        'company_id',
    ];

    protected $appends = ['total_amount', 'image_url', 'download_file_url', 'image'];

    protected $with = ['tax'];

    protected $casts = [
        'comision' => 'float',
        'is_public' => 'boolean',
    ];

    const CUSTOM_FIELD_MODEL = 'App\Models\Product';

    public function scopeVisibleTo($query, $user)
    {
        $isAdmin = method_exists($user, 'hasRole') && $user->hasRole('admin');

        if ($isAdmin) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('added_by', $user->id)
              ->orWhere('is_public', 1);
        });
    }

    public function getImageUrlAttribute()
    {
        if (app()->environment(['development', 'demo']) && str_contains($this->default_image, 'http')) {
            return $this->default_image;
        }

        return ($this->default_image) ? asset_url_local_s3(Product::FILE_PATH . '/' . $this->default_image) : '';
    }

    public function getImageAttribute()
    {
        if ($this->default_image) {
            return str($this->default_image)->contains('http')
                ? $this->default_image
                : (Product::FILE_PATH . '/' . $this->default_image);
        }

        return $this->default_image;
    }

    public function getDownloadFileUrlAttribute()
    {
        return ($this->downloadable_file)
            ? asset_url_local_s3(Product::FILE_PATH . '/' . $this->downloadable_file)
            : null;
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class)->withTrashed();
    }

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Deal::class, 'lead_products');
    }

    public static function taxbyid($id)
    {
        return Tax::where('id', $id)->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitType::class, 'unit_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(ProductSubCategory::class, 'sub_category_id');
    }

    public function getTotalAmountAttribute()
    {
        if (!is_null($this->price) && !is_null($this->tax)) {
            return (int)$this->price + ((int)$this->price * ((int)$this->tax->rate_percent / 100));
        }

        return '';
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProductFiles::class, 'product_id')->orderByDesc('id');
    }

    public function orderItem(): HasMany
    {
        return $this->hasMany(OrderItems::class, 'product_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function inventory()
    {
        return $this->hasMany(PurchaseStockAdjustment::class, 'product_id');
    }

    public function customerSuccess()
    {
        return $this->hasMany(CustomerSuccess::class, 'product_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function location()
    {
        return $this->hasOne(ProductLocation::class, 'product_id');
    }

    // Query Scopes para filtros optimizados
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByCategories($query, $categories)
    {
        if (empty($categories)) return $query;

        $categoryIds = is_array($categories) ? $categories : explode(',', $categories);
        return $query->whereIn('category_id', array_filter($categoryIds, 'is_numeric'));
    }

    public function scopeByOperations($query, $operations)
    {
        if (empty($operations)) return $query;

        $operationTypes = is_array($operations) ? $operations : explode(',', $operations);
        return $query->whereIn('operacion', $operationTypes);
    }

    public function scopeByPriceRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }
        return $query;
    }

    public function scopeByCharacteristics($query, $ambientes = null, $habitaciones = null, $banos = null, $cocheras = null)
    {
        if ($ambientes !== null) {
            $query->where('ambientes', $ambientes);
        }
        if ($habitaciones !== null) {
            $query->where('habitaciones', $habitaciones);
        }
        if ($banos !== null) {
            $query->where('banos', $banos);
        }
        if ($cocheras !== null) {
            $query->where('cocheras', $cocheras);
        }
        return $query;
    }

    public function scopeBySurfaceRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('superficie_construida', '>=', $min);
        }
        if ($max !== null) {
            $query->where('superficie_construida', '<=', $max);
        }
        return $query;
    }

    public function scopeByLandSurfaceRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('superficie_util', '>=', $min);
        }
        if ($max !== null) {
            $query->where('superficie_util', '<=', $max);
        }
        return $query;
    }

    public function scopeByCode($query, $codigo)
    {
        if (empty($codigo)) return $query;

        return $query->where(function ($q) use ($codigo) {
            $q->where('codigo_inmueble', 'LIKE', "%{$codigo}%")
              ->orWhere('sku', 'LIKE', "%{$codigo}%");
        });
    }

    public function scopeByPriceRangePreset($query, $rango)
    {
        if (empty($rango)) return $query;

        $ranges = [
            '0-50000' => [null, 50000],
            '50000-100000' => [50000, 100000],
            '100000-200000' => [100000, 200000],
            '200000-500000' => [200000, 500000],
            '500000+' => [500000, null],
        ];

        if (isset($ranges[$rango])) {
            [$min, $max] = $ranges[$rango];
            return $query->byPriceRange($min, $max);
        }

        return $query;
    }

    public function scopeByLocation($query, $ubicacion)
    {
        if (empty($ubicacion)) return $query;

        return $query->whereHas('location', function ($q) use ($ubicacion) {
            $q->where('address', 'LIKE', "%{$ubicacion}%");
        });
    }

    public function scopeWithRelations($query)
    {
        return $query->with([
            'images' => fn($q) => $q->orderBy('order'),
            'location',
            'category',
            'addedBy'
        ]);
    }
}