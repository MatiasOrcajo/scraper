<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{

    public function normalizeProductsNamesByGrams()
    {
        $products = Product::all();
//        $products = Product::where('name', 'Protector solar labial humectante NIVEA Sun Protect FPS 50 para todo tipo de piel x 4,8 grs')->get();
        $gramsArray = ['g', 'g.', 'gr.', 'gr', 'grm', 'grm.', 'grs', 'grs.'];


        foreach ($products as $product) {

            $explode = explode(' ', $product->name);

            foreach ($explode as $index => $word) {

                $newProductName = "";

                // 1. Intentar capturar número y unidad pegados
                if (preg_match('/^(\d+(?:,\d+)?)([a-zA-Z.]+)$/', $word, $matches)) {
                    if (in_array(strtolower($matches[2]), $gramsArray)) {
                        dump("Encontrado pegado: " . $matches[1] . " " . $matches[2]);
                        $quantity = $matches[1];

                        $newProductName = str_replace($matches[0], "", $product->name);

                        $product->name = trim($newProductName);
                        $product->quantity = $this->normalizeDecimal($quantity);
                        $product->measure = 'g';
                        $product->save();
                    }
                } // 2. Si la palabra es SOLO un número, mirar la palabra siguiente
                elseif (preg_match('/^\d+(?:,\d+)?$/', $word, $numberMatch)) {
                    $nextWord = $explode[$index + 1] ?? '';
                    if (in_array(strtolower($nextWord), $gramsArray)) {
                        dump("Encontrado separado: " . $numberMatch[0] . " " . $nextWord);
                        $quantity = $numberMatch[0];
                        $newProductName = str_replace([$word, $explode[$index+1]], "", $product->name);
                        $newProductName = trim($newProductName);

                        $product->name = trim($newProductName);
                        $product->quantity = $this->normalizeDecimal($quantity);
                        $product->measure = 'g';
                        $product->save();
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
