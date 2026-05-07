<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('products')]
#[Fillable(['name', 'brand', 'sub_sub_category', 'sub_category', 'category'])]
class Product extends Model
{
    //
}
