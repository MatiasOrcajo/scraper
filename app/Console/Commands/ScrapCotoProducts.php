<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('app:scrap-coto-products')]
#[Description('Command description')]
class ScrapCotoProducts extends Command
{

    public function handle()
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://www.cotodigital.com.ar/rest/model/atg/actors/cBackOfficeActor/getCategorias?pushSite=CotoDigital&_dynSessConf=4116455685428717426',
            (object)[]
        );

        $data = $response->json();

        if (!isset($data['output'])) {
            $this->error('No se encontró la clave "output".');
            return;
        }

        $cotoCategoriesArray = [];

        for ($i = 1; $i < count($data['output']); $i++) {
            $topLevelCategory = $data['output'][$i]['topLevelCategory'];
            $topLevelCategoryId = $topLevelCategory['categoryId'];
            $topLevelCategoryName = $topLevelCategory['displayName'];
            $unwantedCategories = ['Electro', 'Textil', 'Hogar', 'Aire Libre'];

            if (in_array($topLevelCategoryName, $unwantedCategories)) continue;

            $forCategoryArray = [
                'topLevelCategoryId' => $topLevelCategoryId,
                'topLevelCategoryName' => $topLevelCategoryName,
                'subCategories' => [],
            ];

            if (!empty($topLevelCategory['subCategories']) ) {
                foreach ($topLevelCategory['subCategories'] as $subCategory) {

                    $subCategoryData = [
                        'subCategoryId'   => $subCategory['categoryId'],
                        'subCategoryName' => $subCategory['displayName'],
                    ];

                    if (!empty($subCategory['subCategories2'])) {
                        $subCategoryData['subCategories'] = [];
                        foreach ($subCategory['subCategories2'] as $subSubCategory) {
                            $subCategoryData['subCategories'][] = [
                                'subSubCategoryId'   => $subSubCategory['categoryId'],
                                'subSubCategoryName' => $subSubCategory['displayName'],
                            ];
                        }
                    }

                    $forCategoryArray['subCategories'][] = $subCategoryData;
                }
            }

            $cotoCategoriesArray[] = $forCategoryArray;

        }

        dd($cotoCategoriesArray);

    }
}
