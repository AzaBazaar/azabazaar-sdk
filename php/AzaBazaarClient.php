<?php declare(strict_types=1);

/**
 * AzaBazaar Enterprise X — Official Lightweight PHP SDK
 * Docs: https://developer.azabazaar.com
 * Repo: https://github.com/AzaBazaar/azabazaar-sdk
 */
final class AzaBazaarClient
{
    private string $baseUrl;

    public function __construct(
        private readonly string $apiKey,
        bool $sandbox = false,
        private readonly int $timeout = 30
    ) {
        $this->baseUrl = $sandbox
            ? 'https://sandbox.azabazaar.com'
            : 'https://api.azabazaar.com';
    }

    public function getBalance(): array
    {
        return $this->request('GET', '/api/wallet/balance');
    }

    public function getNetworks(): array
    {
        return $this->request('GET', '/api/vtu/networks');
    }

    public function getDataPlans(int $networkId): array
    {
        return $this->request('GET', "/api/vtu/plans?network_id={$networkId}&category=data");
    }

    public function buyAirtime(string $phone, float $amount, int|string $network = 0, ?string $idempotencyKey = null): array
    {
        $payload = [
            'service_category' => 'airtime',
            'phone'            => $phone,
            'amount'           => $amount,
        ];
        if ($network !== 0 && $network !== '') {
            $payload[is_int($network) ? 'network_id' : 'network'] = $network;
        }
        return $this->request('POST', '/api/vtu/purchase', $payload, $idempotencyKey ?? $this->genIdemp('air'));
    }

    public function buyData(string $phone, string $serviceId, int|string $network = 0, ?string $idempotencyKey = null): array
    {
        $payload = [
            'service_category' => 'data',
            'phone'            => $phone,
            'service_id'       => $serviceId,
        ];
        if ($network !== 0 && $network !== '') {
            $payload[is_int($network) ? 'network_id' : 'network'] = $network;
        }
        return $this->request('POST', '/api/vtu/purchase', $payload, $idempotencyKey ?? $this->genIdemp('data'));
    }

    public function getVtuStatus(string $reference): array
    {
        return $this->request('GET', '/api/vtu/status?reference=' . urlencode($reference));
    }

    public function getBanks(?string $search = null): array
    {
        $qs = $search ? ('?search=' . urlencode($search)) : '';
        return $this->request('GET', '/api/banks' . $qs);
    }

    public function resolveBankAccount(string $accountNumber, string $bankCode): array
    {
        return $this->request('POST', '/api/banks/resolve', [
            'account_number' => $accountNumber,
            'bank_code'      => $bankCode,
        ]);
    }

    public function sendTransfer(string $accountNumber, string $bankCode, float $amount, string $narration = 'Transfer', ?string $idempotencyKey = null): array
    {
        return $this->request('POST', '/api/transfers', [
            'account_number' => $accountNumber,
            'bank_code'      => $bankCode,
            'amount'         => $amount,
            'narration'      => $narration,
        ], $idempotencyKey ?? $this->genIdemp('trf'));
    }

    public static function verifyWebhookSignature(string $rawBody, string $signatureHeader, string $webhookSecret): bool
    {
        if ($signatureHeader === '' || $webhookSecret === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $rawBody, $webhookSecret);
        return hash_equals($expected, $signatureHeader);
    }

    private function genIdemp(string $prefix): string
    {
        return $prefix . '_' . bin2hex(random_bytes(8));
    }

    private function request(string $method, string $path, ?array $body = null, ?string $idempotencyKey = null): array
    {
        $ch = curl_init($this->baseUrl . $path);
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $headers[] = 'X-Idempotency-Key: ' . $idempotencyKey;
        }

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => $method,
        ];
        if ($method === 'POST' && $body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_SLASHES);
        }

        curl_setopt_array($ch, $opts);
        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($raw) || $raw === '') {
            return ['status' => false, 'http_code' => $code, 'message' => $err ?: 'Empty gateway response'];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : ['status' => false, 'http_code' => $code, 'raw' => $raw];
    }
}
