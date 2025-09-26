<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

// Remove the circular redirects that cause the redirect loop
// Filament will handle its own authentication routes automatically

Route::get('/', function () {
    return view('welcome');
});

// Filament handles its own authentication routes, but we need to ensure
// the login route exists for proper redirects
// The login route will be handled by Filament's authentication system

// Route for downloading all QR codes for a product (for reference)
Route::get('/admin/download-qr-codes/{productId}', function ($productId) {
    // Get the QR codes for the specific product
    $qrCodes = DB::table('reward_codes')
        ->where('product_id', $productId)
        ->select('code', 'sku')
        ->get();
    
    // Get product name for the filename
    $product = DB::table('products')->where('id', $productId)->first();
    
    if (!$product) {
        abort(404, 'Product not found');
    }
    
    // Prepare CSV content (even if no codes exist, we'll return a file with just headers)
    $csvContent = "QR Code,SKU,Product Name\n";
    foreach ($qrCodes as $qrCode) {
        $csvContent .= "\"{$qrCode->code}\",\"{$qrCode->sku}\",\"{$product->name}\"\n";
    }
    
    // Create a temporary file
    $filename = 'qr_codes_' . $productId . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
    
    // Return the CSV response
    return response($csvContent)
        ->header('Content-Type', 'text/csv')
        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
})->whereNumber('productId')->name('admin.download.qr-codes');

// Route for downloading QR codes from a specific session
Route::get('/admin/download-qr-session/{sessionId}', function ($sessionId) {
    // Get the QR code generation session
    $session = \App\Models\QrCodeGenerationSession::with('product')->find($sessionId);
    
    if (!$session) {
        abort(404, 'QR code generation session not found.');
    }
    
    // Prepare CSV content from the session codes
    $csvContent = "QR Code,SKU,Product Name\n";
    foreach ($session->qr_codes as $codeData) {
        $csvContent .= "\"{$codeData['code']}\",\"{$codeData['sku']}\",\"{$session->product->name}\"\n";
    }
    
    // Create a filename that includes session info
    $filename = 'qr_codes_session_' . $session->session_identifier . '.csv';
    
    // Return the CSV response
    return response($csvContent)
        ->header('Content-Type', 'text/csv')
        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
})->whereNumber('sessionId')->name('admin.download.qr-session');