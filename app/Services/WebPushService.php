<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebPushService
{
    private string $vapidPublicKey;
    private string $vapidPrivateKey;
    private string $vapidSubject;

    public function __construct()
    {
        $this->vapidPublicKey = config('webpush.vapid.public_key', '');
        $this->vapidPrivateKey = config('webpush.vapid.private_key', '');
        $this->vapidSubject = config('webpush.vapid.subject', 'mailto:admin@kendari.go.id');
    }

    /**
     * Send a notification to all push subscriptions of a specific user.
     */
    public function sendToUser(User $user, string $title, string $body, string $url): void
    {
        if (empty($this->vapidPublicKey) || empty($this->vapidPrivateKey)) {
            Log::warning('WebPushService: VAPID keys are not configured. Skipping push notification.');
            return;
        }

        $subscriptions = $user->pushSubscriptions;

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon' => '/img/icon-192.png',
        ]);

        foreach ($subscriptions as $subscription) {
            $this->sendNotification($subscription, $payload);
        }
    }

    /**
     * Send notification to a single subscription.
     */
    private function sendNotification(PushSubscription $subscription, string $payload): void
    {
        try {
            $endpoint = $subscription->endpoint;
            $parsedUrl = parse_url($endpoint);
            if (!isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
                return;
            }

            $audience = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

            // 1. Generate VAPID JWT Headers
            $headers = $this->getVapidHeaders($audience);

            // 2. Encrypt Payload
            $encryptedData = $this->encryptPayload($payload, $subscription->p256dh, $subscription->auth);

            // 3. Make HTTP Request
            $response = Http::withHeaders(array_merge($headers, [
                'Content-Type' => 'application/octet-stream',
                'Content-Encoding' => 'aes128gcm',
                'TTL' => '2419200', // 4 weeks
            ]))->withBody($encryptedData, 'application/octet-stream')
               ->post($endpoint);

            if ($response->status() === 410 || $response->status() === 404) {
                // Subscription is expired or gone. Remove it.
                Log::info("WebPushService: Subscription expired (HTTP {$response->status()}), deleting: {$subscription->id}");
                $subscription->delete();
            } elseif (!$response->successful()) {
                Log::warning('WebPushService: Failed to send notification', [
                    'subscription_id' => $subscription->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('WebPushService: Exception during notification send', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Encrypt push payload according to RFC 8291 (aes128gcm)
     */
    private function encryptPayload(string $payload, string $p256dhB64, string $authB64): string
    {
        $browserPublicKeyBin = $this->base64UrlDecode($p256dhB64);
        $authSecret = $this->base64UrlDecode($authB64);

        // Generate Ephemeral EC Key Pair
        $ephemeralKey = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        $ephemeralDetails = openssl_pkey_get_details($ephemeralKey);
        $ephemeralPublicKeyBin = chr(4) . $ephemeralDetails['ec']['x'] . $ephemeralDetails['ec']['y'];

        // Convert Browser Public Key to PEM
        $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $browserPublicKeyBin;
        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
        $browserKey = openssl_pkey_get_public($pem);

        // Compute Shared Secret
        $sharedSecret = openssl_pkey_derive($browserKey, $ephemeralKey);

        // Derive Keys using HKDF
        // Info: WebPush: info\0 + receiver + sender
        $authInfo = "WebPush: info\x00" . $browserPublicKeyBin . $ephemeralPublicKeyBin;
        $ikm = $this->hkdf($authSecret, $sharedSecret, $authInfo, 32);

        $salt = random_bytes(16);

        $cekInfo = "Content-Encoding: aes128gcm\x00";
        $cek = $this->hkdf($salt, $ikm, $cekInfo, 16);

        $ivInfo = "Content-Encoding: auth-iv\x00";
        $iv = $this->hkdf($salt, $ikm, $ivInfo, 12);

        // Plaintext needs padding delimiter octet (\x02 for the end of the record)
        $plaintext = $payload . "\x02";

        // Encrypt using AES-128-GCM
        $ciphertext = openssl_encrypt($plaintext, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $iv, $tag);

        // Frame format: salt (16 bytes) + record size (4 bytes, e.g. 4096 = 0x00001000) + idlen (1 byte = 65 = 0x41) + ephemeral pubkey (65 bytes) + ciphertext + tag (16 bytes)
        $recordSize = pack('N', 4096);
        $idLen = pack('C', 65);

        return $salt . $recordSize . $idLen . $ephemeralPublicKeyBin . $ciphertext . $tag;
    }

    /**
     * HKDF derivation.
     */
    private function hkdf(string $salt, string $ikm, string $info, int $length): string
    {
        $prk = hash_hmac('sha256', $ikm, $salt, true);
        $infoWithCounter = $info . chr(1);
        $t = hash_hmac('sha256', $infoWithCounter, $prk, true);
        return substr($t, 0, $length);
    }

    /**
     * Create VAPID headers for the push request.
     */
    private function getVapidHeaders(string $audience): array
    {
        $header = json_encode(['alg' => 'ES256', 'typ' => 'JWT']);
        $payload = json_encode([
            'aud' => $audience,
            'exp' => time() + 43200, // 12 hours
            'sub' => $this->vapidSubject,
        ]);

        $jwtData = $this->base64UrlEncode($header) . '.' . $this->base64UrlEncode($payload);

        // Sign JWT using ES256 (VAPID Private Key)
        $privateKeyBin = $this->base64UrlDecode($this->vapidPrivateKey);
        $publicKeyBin = $this->base64UrlDecode($this->vapidPublicKey);

        $der = hex2bin('30770201010420') . $privateKeyBin . hex2bin('a00a06082a8648ce3d030107a144034200') . $publicKeyBin;
        $pem = "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";

        openssl_sign($jwtData, $derSignature, $pem, OPENSSL_ALGO_SHA256);

        $signature = $this->derToSig($derSignature);
        $token = $jwtData . '.' . $this->base64UrlEncode($signature);

        return [
            'Authorization' => 'vapid t=' . $token . ', k=' . $this->vapidPublicKey,
        ];
    }

    /**
     * Convert DER signature format to Raw ES256 R || S signature format.
     */
    private function derToSig(string $der): string
    {
        if (ord($der[0]) !== 0x30) {
            return '';
        }

        $offset = 2;
        
        // Parse R
        if (ord($der[$offset]) !== 0x02) {
            return '';
        }
        $offset++;
        $rLen = ord($der[$offset]);
        $offset++;
        $r = substr($der, $offset, $rLen);
        $offset += $rLen;

        // Parse S
        if (ord($der[$offset]) !== 0x02) {
            return '';
        }
        $offset++;
        $sLen = ord($der[$offset]);
        $offset++;
        $s = substr($der, $offset, $sLen);

        // Strip leading zero byte if coordinate starts with it
        if ($rLen > 32 && ord($r[0]) === 0x00) {
            $r = substr($r, 1);
        }
        if ($sLen > 32 && ord($s[0]) === 0x00) {
            $s = substr($s, 1);
        }

        // Pad to exactly 32 bytes
        $r = str_pad($r, 32, "\x00", STR_PAD_LEFT);
        $s = str_pad($s, 32, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
