<?php

namespace App\Services\Tte;

use App\Enums\SignatureDocumentType;
use App\Enums\SignatureStatus;
use App\Models\SppdDigitalSignature;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SignatureService
{
    protected PdfGeneratorService $pdfGenerator;
    protected SignatureProviderInterface $provider;

    public function __construct(PdfGeneratorService $pdfGenerator)
    {
        $this->pdfGenerator = $pdfGenerator;
        $this->provider = $this->resolveProvider();
    }

    public function sign(SppdDigitalSignature $signature, string $passphrase): bool|array
    {
        $draftFile = $this->pdfGenerator->generateDraft($signature);
        $linkQr = url('/verify/' . $signature->document_type . '/' . $signature->sppdRequest->id . '/' . md5($signature->sppdRequest->document_number . $signature->sppdRequest->id));

        $result = $this->provider->requestSign(
            $draftFile,
            $signature->signer->nik,
            $passphrase,
            $signature->sign_page,
            $signature->sign_x,
            $signature->sign_y,
            $signature->sign_width,
            $signature->sign_height,
            $linkQr
        );

        if (is_array($result) && isset($result['error'])) {
            $message = 'Provider request failed: ' . json_encode($result, JSON_UNESCAPED_UNICODE);
            $signature->update([
                'status' => SignatureStatus::REJECTED,
                'error_message' => $message,
            ]);

            return ['details' => $result];
        }

        $documentId = null;
        if (is_array($result) && isset($result['id_dokumen'])) {
            $documentId = $result['id_dokumen'];
        }

        $signedPdf = null;
        if ($documentId) {
            try {
                $signedPdf = $this->provider->downloadSignedPdf($documentId);
            } catch (\Throwable $e) {
                $signature->update([
                    'status' => SignatureStatus::REJECTED,
                    'error_message' => 'Provider download failed: ' . $e->getMessage(),
                ]);

                return ['details' => ['exception' => $e->getMessage()]];
            }
        } elseif (is_string($result) && str_contains($result, '%PDF')) {
            $signedPdf = $result;
        }

        if (empty($signedPdf)) {
            $signature->update([
                'status' => SignatureStatus::REJECTED,
                'error_message' => 'Provider returned no PDF output.',
            ]);

            return ['details' => 'Provider returned no PDF output.'];
        }

        $signedPath = $this->storeSignedPdf($signature, $signedPdf);

        $signature->update([
            'status' => SignatureStatus::SIGNED,
            'signed_at' => now(),
            'provider_id' => $documentId,
            'signed_file_path' => $signedPath,
            'error_message' => null,
        ]);

        return true;
    }

    protected function storeSignedPdf(SppdDigitalSignature $signature, string $pdfContents): string
    {
        $disk = Storage::disk(config('tte.storage.disk'));

        $directory = config('tte.storage.paths.signed');
        if (! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $filename = strtoupper($signature->document_type) . '-' . Str::slug($signature->sppdRequest->document_number ?: $signature->sppdRequest->id) . '-' . now()->format('YmdHis') . '.pdf';
        $relativePath = config('tte.storage.paths.signed') . '/' . $filename;
        $disk->put($relativePath, $pdfContents);

        return $relativePath;
    }

    protected function resolveProvider(): SignatureProviderInterface
    {
        $provider = config('tte.default_provider');

        return match ($provider) {
            'bssn' => throw new \RuntimeException('BSSN provider not implemented yet.'),
            default => new LocalProxySignService(),
        };
    }
}
