<?php

namespace App\Services\Tte;

interface SignatureProviderInterface
{
    /**
     * Send a signed PDF request to the provider and return parsed provider response.
     */
    public function requestSign(
        string $filePath,
        string $nik,
        string $passphrase,
        int $page,
        int $xAxis,
        int $yAxis,
        int $width,
        int $height,
        string $linkQr
    ): array|string;

    /**
     * Download the signed PDF from the provider.
     */
    public function downloadSignedPdf(string $signedDocumentId): string;
}
