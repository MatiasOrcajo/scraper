<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{


    private function create(Product $product, string $name, int $quantity, string $measure)
    {
        $product->name = trim($name);
        $product->quantity = $this->normalizeDecimal($quantity);
        $product->measure = $measure;
        $product->save();
    }

    public function normalizeProductsNamesByGrams()
    {
        $products = Product::all();
        $gramsArray = ['g', 'g.', 'gr.', 'gr', 'grm', 'grm.', 'grs', 'grs.'];

        foreach ($products as $product) {
            $explode = explode(' ', $product->name);
            foreach ($explode as $index => $word) {
                // 1. Intentar capturar número y unidad pegados
                if (preg_match('/^(\d+(?:,\d+)?)([a-zA-Z.]+)$/', $word, $matches)) {
                    if (in_array(strtolower($matches[2]), $gramsArray)) {
                        dump("Encontrado pegado: " . $matches[1] . " " . $matches[2]);
                        $quantity = $matches[1];
                        $newProductName = str_replace($matches[0], "", $product->name);
                        $this->create($product, $newProductName, $this->normalizeDecimal($quantity), 'g');
                    }
                } // 2. Si la palabra es SOLO un número, mirar la palabra siguiente
                elseif (preg_match('/^\d+(?:,\d+)?$/', $word, $numberMatch)) {
                    $nextWord = $explode[$index + 1] ?? '';
                    if (in_array(strtolower($nextWord), $gramsArray)) {
                        dump("Encontrado separado: " . $numberMatch[0] . " " . $nextWord);
                        $quantity = $numberMatch[0];
                        $newProductName = str_replace([$word, $explode[$index+1]], "", $product->name);
                        $this->create($product, $newProductName, $this->normalizeDecimal($quantity), 'g');
                    }
                }
            }
        }
    }


    function normalizeDecimal($value)
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
