<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateVapidKeysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webpush:vapid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $signature_description = 'Generate VAPID keys for Web Push Notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating VAPID keys using OpenSSL...');

        if (!extension_loaded('openssl')) {
            $this->error('OpenSSL extension is required but not loaded.');
            return 1;
        }

        $config = [
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ];

        $key = openssl_pkey_new($config);
        if (!$key) {
            $this->error('Failed to generate key pair: ' . openssl_error_string());
            return 1;
        }

        $details = openssl_pkey_get_details($key);
        if (!isset($details['ec'])) {
            $this->error('Curve prime256v1 is not supported by your OpenSSL installation.');
            return 1;
        }

        $x = $details['ec']['x'];
        $y = $details['ec']['y'];
        $d = $details['ec']['d'];

        // VAPID keys are base64url encoded representation of the public key (uncompressed point format) and private key.
        // Public key is 65 bytes: 0x04 (uncompressed indicator) + X coordinate (32 bytes) + Y coordinate (32 bytes).
        $publicKey = chr(4) . $x . $y;
        $privateKey = $d;

        $publicKeyB64 = $this->base64UrlEncode($publicKey);
        $privateKeyB64 = $this->base64UrlEncode($privateKey);

        $this->info('Keys generated successfully:');
        $this->line('<fg=yellow>VAPID_PUBLIC_KEY=</>' . $publicKeyB64);
        $this->line('<fg=yellow>VAPID_PRIVATE_KEY=</>' . $privateKeyB64);
        $this->line('<fg=yellow>VAPID_SUBJECT=</>mailto:admin@kendari.go.id');

        $envFile = base_path('.env');
        if (File::exists($envFile)) {
            if ($this->confirm('Do you want to append/update these keys in your .env file?', true)) {
                $this->writeToEnv($envFile, $publicKeyB64, $privateKeyB64);
                $this->info('.env file updated successfully!');
            }
        }

        return 0;
    }

    /**
     * Base64 URL encode helper.
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Update .env file keys.
     */
    private function writeToEnv(string $path, string $pubKey, string $privKey): void
    {
        $content = File::get($path);

        $keys = [
            'VAPID_PUBLIC_KEY' => $pubKey,
            'VAPID_PRIVATE_KEY' => $privKey,
            'VAPID_SUBJECT' => 'mailto:admin@kendari.go.id',
        ];

        foreach ($keys as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        File::put($path, trim($content) . "\n");
    }
}
