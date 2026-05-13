<?php

namespace App\Services;

use App\Models\Product;
use App\Traits\ProductNormalizerTrait;

class ProductService
{

    use ProductNormalizerTrait;

    public function normalizeProductsNames(): void
    {
        $products = Product::all();
        $measures = [
            ['g', 'g.', 'gr', 'gr.', 'grm', 'grm.', 'grs', 'grs.'],
            // Kilogramos
            ['k', 'k.', 'kg', 'kg.'],
            // Litros
            ['L', 'L.', 'lt', 'lt.', 'ltr', 'ltr.', 'lts', 'lts.'],
            // Mililitros
            ['ml', 'ml.'],
            // Centímetros cúbicos / cc
            ['cc', 'cc.', 'cmq', 'cmq.'],
            // Unidades
            ['u', 'u.', 'un', 'un.', 'uni', 'uni.', 'Unidad', 'Unidades']
        ];

        foreach ($products as $product) {
            $explode = explode(' ', $product->name);
            foreach ($explode as $index => $word) {
                // 1. Intentar capturar número y unidad pegados
                if (preg_match('/^(\d+(?:,\d+)?)([a-zA-Z.]+)$/', $word, $matches)) {

                    foreach ($measures as $measureArray) {
                        if (in_array(strtolower($matches[2]), $measureArray)) {
                            dump("Encontrado pegado: " . $matches[1] . " " . $matches[2]);
                            $quantity = $matches[1];
                            $newProductName = str_replace($matches[0], "", $product->name);
                            $this->create($product, $newProductName, $this->normalizeDecimal($quantity), $measureArray[0]);
                        }
                    }
                } // 2. Si la palabra es SOLO un número, mirar la palabra siguiente
                elseif (preg_match('/^\d+(?:,\d+)?$/', $word, $numberMatch)) {
                    $nextWord = $explode[$index + 1] ?? '';
                    foreach ($measures as $measureArray) {
                        if (in_array(strtolower($nextWord), $measureArray)) {
                            dump("Encontrado separado: " . $numberMatch[0] . " " . $nextWord);
                            $quantity = $numberMatch[0];
                            $newProductName = str_replace([$word, $explode[$index + 1]], "", $product->name);
                            $this->create($product, $newProductName, $this->normalizeDecimal($quantity), $measureArray[0]);
                        }
                    }
                }
            }
        }
    }


}
