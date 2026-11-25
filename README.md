# AzaBazaar Enterprise X — Official API SDKs & Postman Collection

Official **PHP 8.1+** and **Node.js 18+** SDKs (zero dependencies) plus a ready-to-import **Postman Collection** for the [AzaBazaar Enterprise REST API](https://developer.azabazaar.com).

- **API Developer Tier:** 100% Free (No setup or monthly fee)
- **Instant Sandbox:** ₦100,000 mock float immediately upon key generation (`sandbox.azabazaar.com`)
- **Live Rails:** Debits your Main Wallet Balance directly (`api.azabazaar.com`)
- **PIN-Free Server Calls:** Secured via Bearer API Keys, CIDR IP Whitelisting, Idempotency Keys & HMAC SHA-256 Webhooks

---

## Supported Services
- **Telecom VTU:** Airtime & Data Bundles (MTN, Airtel, Glo, T2 Mobile/9mobile, Vitel) with automatic multi-gateway failover & auto-refunds
- **Airtime to Cash:** 3-Step Carrier OTP Liquidation, USSD Share 'N' Sell & Recharge PIN Vouchers
- **Utility Bills:** Electricity DISCOs (IKEDC, EKEDC, AEDC, etc.), Cable TV (DStv, GOtv, Startimes)
- **Banking & NIP Payouts:** Full Nigerian Bank Directory, NUBAN Account Name Resolution & Direct NIP Transfers
- **PVA Virtual Numbers:** International SMS OTP numbers for WhatsApp, Telegram, TikTok & more

---

## Quick Links
- **Full API Documentation & Live Tariffs:** https://developer.azabazaar.com
- **Generate Free API Keys:** https://azabazaar.com/settings?tab=api

---

## Base URLs
| Environment | Base URL | Key Prefix |
| :--- | :--- | :--- |
| **Sandbox (Mock Float)** | `https://sandbox.azabazaar.com` | `azb_test_...` |
| **Live Production** | `https://api.azabazaar.com` | `azb_live_...` |

---

## Quick Start — PHP (Zero Dependencies)

~~~php
<?php
require_once __DIR__ . '/php/AzaBazaarClient.php';

// Set sandbox: false when switching to your azb_live_ key
$azb = new AzaBazaarClient('azb_test_YOUR_SANDBOX_KEY', sandbox: true);

// 1. Check Balance
$balance = $azb->getBalance();

// 2. Buy MTN Airtime (₦100)
$airtime = $azb->buyAirtime('08069309367', 100, 1);

// 3. List MTN Data Plans & Purchase
$plans = $azb->getDataPlans(1);
$data  = $azb->buyData('08069309367', 'MT-0112', 1);
~~~

---

## Quick Start — Node.js 18+ (Zero Dependencies)

~~~javascript
import { AzaBazaarClient } from './nodejs/azabazaar.js';

const azb = new AzaBazaarClient('azb_test_YOUR_SANDBOX_KEY', { sandbox: true });

console.log(await azb.getBalance());
console.log(await azb.buyAirtime({ phone: '08069309367', amount: 100, networkId: 1 }));
~~~

---

## Postman Collection
1. Download [`postman/AzaBazaar_API.postman_collection.json`](./postman/AzaBazaar_API.postman_collection.json).
2. Open Postman ➔ **Import** ➔ select the JSON file.
3. Set the `api_key` collection variable to your `azb_test_...` key and start testing immediately.

---

## Webhook Signature Verification (PHP)

~~~php
$rawBody   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_AZABAZAAR_SIGNATURE'] ?? '';

if (!AzaBazaarClient::verifyWebhookSignature($rawBody, $signature, 'whsec_YOUR_WEBHOOK_SECRET')) {
    http_response_code(401);
    exit('Invalid signature');
}

$event = json_decode($rawBody, true);
// Handle $event['event'] (e.g., vtu.completed, vtu.failed, bill.completed)
http_response_code(200);
~~~
