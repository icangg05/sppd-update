<?php

namespace App\Http\Controllers;

use App\Enums\SignatureDocumentType;
use App\Enums\SignatureStatus;
use App\Jobs\SendTteSignRequestJob;
use App\Models\SppdDigitalSignature;
use App\Models\SppdRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SppdDigitalSignatureController extends Controller
{
    public function request(Request $request, SppdRequest $sppd, string $type)
    {
        $documentType = SignatureDocumentType::tryFrom($type);
        if (!$documentType) {
            return back()->with('error', 'Tipe dokumen tanda tangan elektronik tidak dikenali.');
        }

        $validated = $request->validate([
            'passphrase' => 'required|string|min:4|max:255',
        ]);

        $signer = Auth::user();
        if (!$signer?->nik) {
            return back()->with('error', 'NIK penandatangan belum tersedia. Silakan lengkapi profil Anda terlebih dahulu.');
        }

        $signature = $sppd->digitalSignatures()->updateOrCreate(
            [
                'signer_id' => $signer->id,
                'document_type' => $documentType->value,
            ],
            array_merge(
                [
                    'sppd_request_id' => $sppd->id,
                    'signer_id' => $signer->id,
                    'document_type' => $documentType->value,
                    'status' => SignatureStatus::PENDING,
                    'error_message' => null,
                    'signed_file_path' => null,
                    'provider_name' => config('tte.default_provider'),
                ],
                $this->defaultCoordinates($documentType)
            )
        );

        SendTteSignRequestJob::dispatch($signature, $validated['passphrase']);

        return back()->with('success', 'Permintaan TTE dikirim ke antrian. Mohon tunggu proses penandatanganan selesai.');
    }

    public function status(SppdRequest $sppd, SppdDigitalSignature $signature)
    {
        abort_if($signature->sppd_request_id !== $sppd->id, 404);

        return response()->json([
            'status' => $signature->status->value,
            'signed_at' => $signature->signed_at?->toDateTimeString(),
            'error_message' => $signature->error_message,
            'signed_file_path' => $signature->signed_file_path,
        ]);
    }

    public function download(SppdRequest $sppd, SppdDigitalSignature $signature)
    {
        abort_if($signature->sppd_request_id !== $sppd->id, 404);

        if (!$signature->signed_file_path) {
            return back()->with('error', 'Dokumen tanda tangan elektronik belum tersedia.');
        }

        return response()->download(
            storage_path('app/public/' . $signature->signed_file_path),
            basename($signature->signed_file_path),
            ['Content-Type' => 'application/pdf']
        );
    }

    protected function defaultCoordinates(SignatureDocumentType $documentType): array
    {
        return match ($documentType) {
            SignatureDocumentType::SPPD => [
                'sign_page' => 1,
                'sign_x' => 220,
                'sign_y' => 100,
                'sign_width' => 545,
                'sign_height' => 130,
            ],
            SignatureDocumentType::SPT => [
                'sign_page' => 1,
                'sign_x' => 220,
                'sign_y' => 100,
                'sign_width' => 545,
                'sign_height' => 130,
            ],
            SignatureDocumentType::KUITANSI => [
                'sign_page' => 1,
                'sign_x' => 220,
                'sign_y' => 100,
                'sign_width' => 545,
                'sign_height' => 130,
            ],
        };
    }
}
