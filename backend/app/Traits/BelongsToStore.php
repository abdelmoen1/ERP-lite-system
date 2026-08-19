<?php

namespace App\Traits;

trait BelongsToStore
{
    public function scopeForStore($query, int $storeId)
    {
        return $query->where($this->getTable() . '.store_id', $storeId);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $storeId = auth()->user()?->store_id;

        if (!$storeId) {
            return null;
        }

        $field = $field ?: $this->getRouteKeyName();

        return static::query()
            ->where($field, $value)
            ->where('store_id', $storeId)
            ->first();
    }
}
