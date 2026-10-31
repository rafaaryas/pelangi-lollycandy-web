<?php

namespace App\Support;

final class InventoryNumbers
{
    public static function quantityRules(bool $allowZero = false): array
    {
        return ['required', 'numeric', 'decimal:0,3', $allowZero ? 'min:0' : 'gt:0', 'max:99999999999.999'];
    }

    public static function moneyRules(): array
    {
        return ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'];
    }
}
