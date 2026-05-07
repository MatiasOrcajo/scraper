<?php

namespace App\Console\Commands;

use App\Services\ProductService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:normalize-products-by-grams')]
#[Description('Command description')]
class NormalizeProductsByGrams extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ProductService $productService)
    {
        $productService->normalizeProductsNamesByGrams();
    }
}
