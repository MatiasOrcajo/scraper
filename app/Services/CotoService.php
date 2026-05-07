<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;

class CotoService
{
    private function generateProductsByCategoryUrl(string $categoryCode, int $page = 1): string
    {
        return "https://api.coto.com.ar/api/v1/ms-digital-sitio-bff-web/api/v1/products/categories/" . $categoryCode . "?page=" . $page . "&key=key_r6xzz4IAoTWcipni&num_results_per_page=200&pre_filter_expression=%7B%22name%22:%22store_availability%22,%22value%22:%22200%22%7D&c=cio-fe-web-coto-3.3.1&i=aef1c44d-e940-4596-aa37-ba079b5d51e0&s=11&origin_referrer=/sitios/cdigi/productos/categorias/x/" . $categoryCode;
    }

    public function extractCategories(): array
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://www.cotodigital.com.ar/rest/model/atg/actors/cBackOfficeActor/getCategorias?pushSite=CotoDigital&_dynSessConf=4116455685428717426',
            (object)[]
        );

        $data = $response->json();

        if (!isset($data['output'])) {
            throw new \Error('No se encontró la clave "output".');
        }

        $cotoCategoriesArray = [];

        for ($i = 1; $i < count($data['output']); $i++) {
            $topLevelCategory = $data['output'][$i]['topLevelCategory'];
            $topLevelCategoryId = $topLevelCategory['categoryId'];
            $topLevelCategoryName = $topLevelCategory['displayName'];
            $unwantedCategories = ['Electro', 'Textil', 'Hogar', 'Aire Libre', 'Panaderia Propia'];

            echo "🔍 Scraping top level category: $topLevelCategoryName" . PHP_EOL;

            if (in_array($topLevelCategoryName, $unwantedCategories)) continue;

            $forCategoryArray = [
                'topLevelCategoryId' => $topLevelCategoryId,
                'topLevelCategoryName' => $topLevelCategoryName,
                'subCategories' => [],
            ];

            if (!empty($topLevelCategory['subCategories'])) {
                foreach ($topLevelCategory['subCategories'] as $subCategory) {

                    if (in_array($subCategory['displayName'], $unwantedCategories)) continue;

                    $subCategoryData = [
                        'subCategoryId'   => $subCategory['categoryId'],
                        'subCategoryName' => $subCategory['displayName'],
                    ];

                    if (in_array($subCategory['displayName'], $unwantedCategories)) continue;

                    echo "  └─ Subcategoría: $topLevelCategoryName -> " . $subCategory['displayName'] . PHP_EOL;

                    if (!empty($subCategory['subCategories2'])) {
                        $subCategoryData['subCategories'] = [];
                        foreach ($subCategory['subCategories2'] as $subSubCategory) {

                            if (in_array($subSubCategory['displayName'], $unwantedCategories)) continue;

                            $subCategoryData['subCategories'][] = [
                                'subSubCategoryId'   => $subSubCategory['categoryId'],
                                'subSubCategoryName' => $subSubCategory['displayName'],
                            ];

                            echo "     └─ Sub-subcategoría: $topLevelCategoryName -> " . $subCategory['displayName'] . " -> " . $subSubCategory['displayName'] . PHP_EOL;
                        }
                    }

                    $forCategoryArray['subCategories'][] = $subCategoryData;
                }
            }

            $cotoCategoriesArray[] = $forCategoryArray;
        }

        echo "✅ Categorías obtenidas correctamente." . PHP_EOL . PHP_EOL;

        return $cotoCategoriesArray;
    }

    public function extractProductsInfoFromCategories(): array
    {

        echo "====== INICIO DEL SCRAPING DE PRODUCTOS ======" . PHP_EOL . PHP_EOL;

        $categories = $this->extractCategories();

        foreach ($categories as $category) {
            if (!empty($category['subCategories'])) {
                foreach ($category['subCategories'] as $subCategory) {
                    if (!empty($subCategory['subCategories'])) {
                        foreach ($subCategory['subCategories'] as $subSubCategory) {
                            $pagesCounter = 1;

                            echo "📌 Procesando: {$category['topLevelCategoryName']} → {$subCategory['subCategoryName']} → {$subSubCategory['subSubCategoryName']}" . PHP_EOL;

                            for (; ;) {
                                try {
                                    echo "   ⏳ Página $pagesCounter - Esperando 2 segundos..." . PHP_EOL;
                                    sleep(2);

                                    $response = Http::withHeaders([
                                        'Accept' => 'application/json',
                                        'Content-Type' => 'application/json',
                                    ])
                                        ->get($this->generateProductsByCategoryUrl(
                                            $subSubCategory['subSubCategoryId'],
                                            $pagesCounter
                                        ));

                                    if ($response->successful()) {
                                        echo "   ✅ Respuesta OK para: {$category['topLevelCategoryName']} → {$subCategory['subCategoryName']} → {$subSubCategory['subSubCategoryName']}, página $pagesCounter" . PHP_EOL;

                                        $results = $response["response"]["results"];

                                        if (empty($results)) {
                                            echo "   🏁 No hay más productos. Fin de paginación." . PHP_EOL;
                                            break;
                                        }

                                        echo "   📦 Productos encontrados: " . count($results) . PHP_EOL;

                                        foreach ($results as $product) {
                                            $productName  = $product['value'];
                                            $productBrand = $product['data']['product_brand'] ?? 'Sin marca';
                                            $imageUrl = $product['data']['image_url'] ?? 'Sin imagen';

                                            $batch[] = [
                                                'name'             => $productName,
                                                'brand'            => $productBrand,
                                                'sub_sub_category' => $subSubCategory['subSubCategoryName'],
                                                'sub_category'     => $subCategory['subCategoryName'],
                                                'category'         => $category['topLevelCategoryName'],
                                                'image_url'        => $imageUrl,
                                                'created_at'       => now(),
                                                'updated_at'       => now(),
                                            ];
                                        }

                                        if (!empty($batch)) {

                                            foreach ($batch as $chunk) {
                                                Product::insert($chunk);
                                            }

                                            echo "      💾 Lote de " . count($batch) . " productos guardado." . PHP_EOL;
                                            $batch = [];
                                        }
                                    } else {
                                        echo "   ❌ Error HTTP " . $response->status() . " en página $pagesCounter" . PHP_EOL;
                                        break;
                                    }
                                } catch (\Exception $e) {
                                    echo "   ❌ Excepción: " . $e->getMessage() . PHP_EOL;
                                }

                                $pagesCounter++;
                            }

                            echo PHP_EOL;
                        }
                    }
                }
            }
        }

        echo "====== SCRAPING FINALIZADO ======" . PHP_EOL;

    }
}
