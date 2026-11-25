<?php declare(strict_types=1);

require_once __DIR__ . '/AzaBazaarClient.php';

// Switch $sandbox = false when going Live with your azb_live_ key
$azb = new AzaBazaarClient('azb_test_YOUR_SANDBOX_KEY', sandbox: true);

print_r($azb->getBalance());
print_r($azb->buyAirtime('08069309367', 100, 1));
print_r($azb->buyData('08069309367', 'MT-0112', 1));
