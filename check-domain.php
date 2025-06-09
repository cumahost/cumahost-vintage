<?php
header('Content-Type: application/json');

$domain = $_GET['domain'] ?? '';
$domain = strtolower(trim($domain));

// Simulasi dummy: kalau mengandung "cuma", dianggap sudah dipakai
if (str_contains($domain, 'cuma')) {
  echo json_encode(['available' => false]);
} else {
  echo json_encode(['available' => true]);
}
