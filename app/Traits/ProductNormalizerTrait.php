<?php

namespace App\Traits;

use App\Models\Product;

trait ProductNormalizerTrait
{

    private function create(Product $product, string $name, int $quantity, string $measure) : void
    {
        $product->name = trim($name);
        $product->quantity = $this->normalizeDecimal($quantity);
        $product->measure = $measure;
        $product->save();
    }

    function normalizeDecimal($value) : float|null
    {
        if (is_null($value)) return null;
        $value = trim((string) $value);
        if ($value === '') return 0.0;

        if (!str_contains($value, ',') && !str_contains($value, '.')) {
            return (float) $value;
        }

        if (str_contains($value, ',') && !str_contains($value, '.')) {
            return (float) str_replace(',', '.', $value);
        }

        if (str_contains($value, '.') && !str_contains($value, ',')) {
            return (float) $value;
        }

        $lastCommaPos = strrpos($value, ',');
        $lastDotPos = strrpos($value, '.');

        $decimalSepPos = max($lastCommaPos, $lastDotPos);


        $clean = '';
        for ($i = 0; $i < strlen($value); $i++) {
            $char = $value[$i];
            if ($i == $decimalSepPos) {
                $clean .= '.';
            } elseif ($char !== ',' && $char !== '.') {
                $clean .= $char; // dígitos u otros
            }
        }
        return (float) $clean;
    }


}
