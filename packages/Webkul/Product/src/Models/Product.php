<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Activity\Models\ActivityProxy;
use Webkul\Activity\Traits\LogsActivity;
use Webkul\Attribute\Traits\CustomAttribute;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Lead\Models\ProductProxy as LeadProductProxy;
use Webkul\Product\Contracts\Product as ProductContract;
use Webkul\Tag\Models\TagProxy;
use Webkul\User\Models\UserProxy;
use Webkul\Warehouse\Models\LocationProxy;
use Webkul\Warehouse\Models\WarehouseProxy;

class Product extends Model implements ProductContract
{
    use CustomAttribute, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'sku',
        'description',
        'quantity',
        'price',
        'owner_id',
        'assigned_agent_id',
    ];

    /**
     * Get the product warehouses that owns the product.
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(WarehouseProxy::modelClass(), 'product_inventories');
    }

    /**
     * Get the contact person who owns the property.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(PersonProxy::modelClass(), 'owner_id');
    }

    /**
     * Get the CRM user assigned to the property.
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'assigned_agent_id');
    }

    /**
     * Get the lead associations for this property.
     */
    public function interestedLeads(): HasMany
    {
        return $this->hasMany(LeadProductProxy::modelClass(), 'product_id');
    }

    /**
     * Get the product locations that owns the product.
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(LocationProxy::modelClass(), 'product_inventories', 'product_id', 'warehouse_location_id');
    }

    /**
     * Get the product inventories that owns the product.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(ProductInventoryProxy::modelClass());
    }

    /**
     * The tags that belong to the Products.
     */
    public function tags()
    {
        return $this->belongsToMany(TagProxy::modelClass(), 'product_tags');
    }

    /**
     * Get the activities.
     */
    public function activities()
    {
        return $this->belongsToMany(ActivityProxy::modelClass(), 'product_activities');
    }
}
