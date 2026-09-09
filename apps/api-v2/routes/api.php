<?php

use App\Http\Controllers\BrandController;
use Illuminate\Support\Facades\Route;

// Mounted at /api/v2 by bootstrap/app.php. Every path here is described first in
// packages/openapi-v2; a route without a description is not finished.

// Bound by code, not id: the description addresses a brand by its code, and a code that
// matches no brand is the 404 that showBrand declares.
Route::get('brands/{brand:code}', [BrandController::class, 'show'])->name('brands.show');
