<?php

require_once 'phpMQTT.php';
require_once 'koneksi.php';

// Log file untuk debugging
$logFile = __DIR__ . '/mqtt_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

writeLog("MQTT Service starting...");

// MQTT Settings
$server = 'mqtt.revolusi-it.com';
$port = 1883;
$username = 'usm';
$password = 'usmjaya1';
$client_id = 'phpSubscriber_' . uniqid();

// Create MQTT Client
$mqtt = new Bluerhinos\phpMQTT($server, $port, $client_id);

// Connect to MQTT broker
if (!$mqtt->connect(true, NULL, $username, $password)) {
    writeLog("Failed to connect to MQTT Broker");
    exit(1);
}

writeLog("Connected to MQTT Broker");

// Subscribe to topics
$topics['iot/g231220158/suhu'] = array("qos" => 0, "function" => "processSuhu");
$topics['iot/g231220158/kelembaban'] = array("qos" => 0, "function" => "processKelembaban");

$mqtt->subscribe($topics, 0);
writeLog("Subscribed to topics");

// Latest data storage
$latestData = array(
    'suhu' => null,
    'kelembaban' => null,
    'timestamp' => null
);

// Process temperature message
function processSuhu($topic, $msg) {
    global $conn, $latestData;
    writeLog("Received temperature: $msg");
    
    // Update latest data
    $latestData['suhu'] = $msg;
    $latestData['timestamp'] = time();
    
    // Save to file for web access
    saveLatestData($latestData);
    
    // If we have both values, save to database
    if ($latestData['kelembaban'] !== null && 
        $latestData['timestamp'] > time() - 10) { // within 10 seconds
        saveToDatabase($latestData['suhu'], $latestData['kelembaban']);
    }
}

// Process humidity message
function processKelembaban($topic, $msg) {
    global $conn, $latestData;
    writeLog("Received humidity: $msg");
    
    // Update latest data
    $latestData['kelembaban'] = $msg;
    $latestData['timestamp'] = time();
    
    // Save to file for web access
    saveLatestData($latestData);
    
    // If we have both values, save to database
    if ($latestData['suhu'] !== null && 
        $latestData['timestamp'] > time() - 10) { // within 10 seconds
        saveToDatabase($latestData['suhu'], $latestData['kelembaban']);
    }
}

// Save latest data to file for web access
function saveLatestData($data) {
    $jsonData = json_encode($data);
    file_put_contents(__DIR__ . '/latest_data.json', $jsonData);
    writeLog("Latest data saved to file");
}

// Save to database
function saveToDatabase($suhu, $kelembaban) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO monitoring (suhu, kelembaban) VALUES (?, ?)");
    $stmt->bind_param("dd", $suhu, $kelembaban);
    
    if($stmt->execute()) {
        writeLog("Data saved to database: Suhu=$suhu, Kelembaban=$kelembaban");
    } else {
        writeLog("Database error: " . $stmt->error);
    }
}

writeLog("Starting MQTT processing loop");

// Process MQTT messages
while ($mqtt->proc()) {
    // Process will happen in the callback functions
}

// Close connection when done
$mqtt->close();
writeLog("MQTT Service stopped");
?>