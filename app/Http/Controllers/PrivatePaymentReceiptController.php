<?php

namespace App\Http\Controllers;

use App\Models\PaymentReceipt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PrivatePaymentReceiptController
{
    public function __invoke(PaymentReceipt $receipt): BinaryFileResponse
    {
        Gate::authorize('payments.view');

        $disk = Storage::disk('receipts');
        abort_unless($disk->exists($receipt->storage_path), 404);

        $path = $disk->path($receipt->storage_path);
        $mime = mime_content_type($path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        return response()->file($path, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Disposition' => 'inline; filename="receipt-'.$receipt->id.'.'.pathinfo($path, PATHINFO_EXTENSION).'"',
        ]);
    }
}
