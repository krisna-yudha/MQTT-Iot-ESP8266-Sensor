<?php
// Pastikan error reporting diaktifkan untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'phpMQTT.php';

header('Content-Type: application/json');

// Debug log dengan path absolut
$logFile = __DIR__ . '/command_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

writeLog("Command script started");

// Check if command was sent
if (!isset($_POST['command'])) {
    writeLog("No command specified");
    echo json_encode(['status' => 'error', 'message' => 'No command specified']);
    exit;
}

$command = $_POST['command'];
writeLog("Command received: $command");

// Validate command
$validCommands = ['ON1', 'OFF1', 'ON2', 'OFF2', 'ON3', 'OFF3'];
if (!in_array($command, $validCommands)) {
    writeLog("Invalid command: $command");
    echo json_encode(['status' => 'error', 'message' => 'Invalid command']);
    exit;
}

try {
    // MQTT Settings - HARUS PERSIS sama dengan Arduino
    $server = 'mqtt.revolusi-it.com';
    $port = 1883;
    $username = 'usm';
    $password = 'usmjaya1';
    // Gunakan client ID dengan format yang sama seperti di Arduino
    $client_id = 'PHPClient-' . rand(10000, 99999);
    $topic = 'iot/g231220158/kendali'; // Persis seperti di Arduino

    writeLog("Connecting to MQTT server: $server:$port as $client_id");

    // Create MQTT Client dan pastikan semua parameter sesuai
    $mqtt = new Bluerhinos\phpMQTT($server, $port, $client_id);
    
    // Connect dengan clean session true (seperti di Arduino)
    if (!$mqtt->connect(true, NULL, $username, $password)) {
        writeLog("MQTT connection failed");
        echo json_encode(['status' => 'error', 'message' => 'Failed to connect to MQTT broker']);
        exit;
    }
    
    writeLog("Connected to MQTT broker successfully");
    
    // Publish command dengan QoS 0 (sesuai Arduino)
    $publishResult = $mqtt->publish($topic, $command, 0);
    
    if ($publishResult) {
        writeLog("Command published successfully: $command to $topic");
        // Tunggu 100ms untuk memastikan pesan terkirim
        usleep(100000);
        $mqtt->close();
        echo json_encode(['status' => 'success', 'message' => "Command $command sent"]);
    } else {
        writeLog("Failed to publish command");
        $mqtt->close();
        echo json_encode(['status' => 'error', 'message' => 'Failed to send command']);
    }
} catch (Exception $e) {
    writeLog("Exception occurred: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>