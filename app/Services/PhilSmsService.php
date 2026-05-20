<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PhilSmsService
{
    /**
     * @return array{success: bool, message?: string, data?: mixed}
     */
    public function send(string $recipient, string $message, ?string $senderId = null): array
    {
        $token = trim((string) config('philsms.api_token', ''));
        if ($token === '') {
            return [
                'success' => false,
                'message' => 'PhilSMS API token is not configured. Set PHILSMS_API_TOKEN in .env.',
            ];
        }

        $normalized = $this->normalizePhone($recipient);
        if ($normalized === null) {
            return [
                'success' => false,
                'message' => 'Invalid phone number.',
            ];
        }

        $sender = $senderId ?: config('philsms.sender_id');
        if (! $sender) {
            return [
                'success' => false,
                'message' => 'PhilSMS sender ID is not configured.',
            ];
        }

        $url = rtrim((string) config('philsms.base_url'), '/').'/sms/send';

        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->post($url, [
                'recipient' => $normalized,
                'sender_id' => $sender,
                'type' => 'plain',
                'message' => $message,
            ]);

        $body = $response->json();
        if (! is_array($body)) {
            $body = ['raw' => $response->body()];
        }

        if ($response->successful() && ($body['status'] ?? '') === 'success') {
            return [
                'success' => true,
                'data' => $body['data'] ?? $body,
            ];
        }

        $message = (string) ($body['message'] ?? $response->body() ?: 'SMS send failed.');
        if (stripos($message, 'unauthenticated') !== false) {
            $message = 'PhilSMS rejected your API token (Unauthenticated). Use PHILSMS_BASE_URL=https://dashboard.philsms.com/api/v3, copy a fresh token from dashboard.philsms.com → Developers, then run: php artisan config:clear';
        }

        return [
            'success' => false,
            'message' => $message,
            'data' => $body,
        ];
    }

    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        if (Str::startsWith($digits, '63') && strlen($digits) === 12) {
            return $digits;
        }

        if (Str::startsWith($digits, '0') && strlen($digits) === 11) {
            return '63'.substr($digits, 1);
        }

        if (Str::startsWith($digits, '9') && strlen($digits) === 10) {
            return '63'.$digits;
        }

        if (strlen($digits) >= 10 && strlen($digits) <= 15) {
            return $digits;
        }

        return null;
    }
}
