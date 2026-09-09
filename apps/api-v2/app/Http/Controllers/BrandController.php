<?php

namespace App\Http\Controllers;

use App\Http\Resources\BrandResource;
use App\Models\Brand;

class BrandController extends Controller
{
    /**
     * Retrieve a brand.
     */
    public function show(Brand $brand): BrandResource
    {
        return new BrandResource($brand);
    }
}
