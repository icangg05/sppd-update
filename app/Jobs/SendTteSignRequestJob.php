<?php

namespace App\Jobs;

use App\Enums\SignatureStatus;
use App\Models\SppdDigitalSignature;
use App\Services\Tte\SignatureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTteSignRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public SppdDigitalSignature $signature;
    public string $passphrase;
    public int $tries = 3;
    public int $backoff = 300;

    public function __construct(SppdDigitalSignature $signature, string $passphrase)
    {
        $this->signature = $signature;
        $this->passphrase = $passphrase;
    }

    public function handle(SignatureService $signatureService): void
    {
        $signature = SppdDigitalSignature::find($this->signature->id);
        if (!$signature) {
            return;
        }

        if ($signature->status === SignatureStatus::SIGNED) {
            return;
        }

        try {
            $signatureService->sign($signature, $this->passphrase);
        } catch (Throwable $exception) {
            $signature->update([
                'status' => SignatureStatus::REJECTED,
                'error_message' => $exception->getMessage(),
            ]);
            Log::error('SendTteSignRequestJob failed', ['exception' => $exception, 'signature_id' => $signature->id]);
            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $signature = SppdDigitalSignature::find($this->signature->id);
        if (!$signature) {
            return;
        }

        $signature->update([
            'status' => SignatureStatus::REJECTED,
            'error_message' => $exception->getMessage(),
        ]);
    }
}
