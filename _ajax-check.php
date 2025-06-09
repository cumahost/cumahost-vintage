<?php
// ajax-check.php — WHMCS domain checker dengan logging debug

// Fungsi logging untuk debug
function log_debug($msg) {
  file_put_contents(__DIR__ . '/domain-check.log', date('Y-m-d H:i:s') . ' ' . $msg . PHP_EOL, FILE_APPEND);
}

// Konfigurasi API WHMCS
$identifier = 'h9AESLr4C11oM8y9BzosiLPnS9VVIw7R';
$secret     = '3hGaEzYWqrVGA5gBK8phVY3BDULjJEVW';
$api_url    = 'https://billing.cumahost.com/includes/api.php';

$domain = $_GET['domain'] ?? '';
$domain = strtolower(trim($domain));

if (!$domain || !preg_match('/^[a-z0-9-]+$/', $domain)) {
  echo json_encode(['status' => 'error', 'message' => 'Domain tidak valid.']);
  exit;
}

// TLD yang akan dicek
$tlds = ['.com', '.id', '.net', '.org', '.xyz', '.info', '.app', '.online', '.club', '.es'];
$results = [];

// Ambil harga TLD dari WHMCS (sekali di depan)
$pricingPost = [
  'identifier' => $identifier,
  'secret' => $secret,
  'action' => 'GetTLDPricing',
  'responsetype' => 'json',
];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($pricingPost));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$pricingRaw = curl_exec($ch);
curl_close($ch);
$pricing = json_decode($pricingRaw, true);
log_debug('[PRICING] ' . $pricingRaw);

foreach ($tlds as $ext) {
  $fullDomain = $domain . $ext;

  $postfields = [
    'identifier' => $identifier,
    'secret'     => $secret,
    'action'     => 'DomainWhois',
    'domain'     => $fullDomain,
    'responsetype' => 'json',
  ];

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $api_url);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($ch);
  curl_close($ch);

  log_debug("[$fullDomain] RESPONSE: $response");

  $data = json_decode($response, true);
  $available = isset($data['result']) && $data['result'] === 'available';

  // Ambil harga dari hasil pricing
  $extKey = ltrim($ext, '.');
  $price = '-';
  if (!empty($pricing['pricing'][$extKey]['register']['1'])) {
    $price = $pricing['pricing'][$extKey]['register']['1'];
  }

  $results[] = [
    'domain' => $fullDomain,
    'available' => $available,
    'price' => $price
  ];
}

header('Content-Type: application/json');
echo json_encode(['status' => 'ok', 'domains' => $results]);
