<?php

namespace App\Traits;

trait ScopedThroughDebtStore
{
    public function scopeForStore($query, int $storeId)
    {
        return $query->whereHas('debt', function ($debtQuery) use ($storeId) {
            $debtQuery->where('store_id', $storeId);
        });
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
            ->whereHas('debt', function ($debtQuery) use ($storeId) {
                $debtQuery->where('store_id', $storeId);
            })
            ->first();
    }
}
