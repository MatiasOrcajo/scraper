<?php

namespace App\Console\Commands;

use App\Services\CotoService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('app:scrap-coto-products')]
#[Description('Command description')]
class ScrapCotoProducts extends Command
{

    public function handle(CotoService $cotoService)
    {

        $cotoService->extractProductsInfoFromCategories();

    }
}
