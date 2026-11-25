import crypto from 'node:crypto';

export class AzaBazaarClient {
  constructor(apiKey, { sandbox = false, timeoutMs = 30000 } = {}) {
    this.apiKey = apiKey;
    this.baseUrl = sandbox ? 'https://sandbox.azabazaar.com' : 'https://api.azabazaar.com';
    this.timeoutMs = timeoutMs;
  }

  async request(method, path, body = null, idempotencyKey = null) {
    const headers = {
      'Authorization': `Bearer ${this.apiKey}`,
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };
    if (idempotencyKey) headers['X-Idempotency-Key'] = idempotencyKey;

    const res = await fetch(`${this.baseUrl}${path}`, {
      method,
      headers,
      body: body ? JSON.stringify(body) : undefined,
      signal: AbortSignal.timeout(this.timeoutMs),
    });
    return res.json();
  }

  getBalance() { return this.request('GET', '/api/wallet/balance'); }
  getNetworks() { return this.request('GET', '/api/vtu/networks'); }
  getDataPlans(networkId) { return this.request('GET', `/api/vtu/plans?network_id=${networkId}&category=data`); }

  buyAirtime({ phone, amount, networkId, idempotencyKey = `air_${crypto.randomUUID()}` }) {
    return this.request('POST', '/api/vtu/purchase', {
      service_category: 'airtime', phone, amount, network_id: networkId
    }, idempotencyKey);
  }

  buyData({ phone, serviceId, networkId, idempotencyKey = `data_${crypto.randomUUID()}` }) {
    return this.request('POST', '/api/vtu/purchase', {
      service_category: 'data', phone, service_id: serviceId, network_id: networkId
    }, idempotencyKey);
  }

  getVtuStatus(reference) {
    return this.request('GET', `/api/vtu/status?reference=${encodeURIComponent(reference)}`);
  }

  static verifyWebhook(rawBody, signatureHeader, webhookSecret) {
    const expected = crypto.createHmac('sha256', webhookSecret).update(rawBody).digest('hex');
    const a = Buffer.from(expected, 'utf8');
    const b = Buffer.from(signatureHeader || '', 'utf8');
    return a.length === b.length && crypto.timingSafeEqual(a, b);
  }
}
