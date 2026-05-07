<?php

namespace App\Services;

use App\Models\Product;
use App\Traits\ProductNormalizerTrait;

class ProductService
{

    use ProductNormalizerTrait;

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


}
