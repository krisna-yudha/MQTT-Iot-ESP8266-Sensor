<?php

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Get the latest data from file
$data = @file_get_contents('latest_data.json');

// Check if file exists and has valid content
if ($data === false || empty($data)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No data available',
        'suhu' => null,
        'kelembaban' => null,
        'timestamp' => null
    ]);
    exit;
}

// Parse JSON
$jsonData = json_decode($data, true);

// Check if data is fresh (less than 30 seconds old)
$isFresh = false;
if ($jsonData['timestamp'] !== null) {
    $isFresh = (time() - $jsonData['timestamp']) < 30;
}

echo json_encode([
    'status' => $isFresh ? 'success' : 'stale',
    'message' => $isFresh ? 'Data is current' : 'Data is stale',
    'suhu' => $jsonData['suhu'],
    'kelembaban' => $jsonData['kelembaban'],
    'timestamp' => $jsonData['timestamp'],
    'time' => $jsonData['timestamp'] ? date('Y-m-d H:i:s', $jsonData['timestamp']) : null
]);
?>