<?php

declare(strict_types=1);

use Cepuder\FountainDrinks\Classes\Codes;
use Cepuder\FountainDrinks\Models\Code;

// One code's QR as a PNG, for the order email. Signed URLs only, so codes can't be guessed.
Route::get('cepuder-fountaindrinks/qr/{code}.png', function(string $code) {
    $row = Code::where('token_id', $code)->firstOrFail();

    return response(Codes::qrPng($row->payload), 200, [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'private, max-age=86400',
    ]);
})->middleware(['web', 'signed'])->name('cepuder.fountaindrinks.qr');
