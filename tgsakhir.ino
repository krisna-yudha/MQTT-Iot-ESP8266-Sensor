#include <ESP8266WiFi.h>
#include <PubSubClient.h>
#include <DHT.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// --- WiFi & MQTT
const char* ssid = "p";
const char* password = "123456789";
const char* mqtt_server = "mqtt.revolusi-it.com";
const char* user_mqtt = "usm";
const char* pass_mqtt = "usmjaya1";
const char* topik = "iot/g231220158";
const char* topik_kendali = "iot/g231220158/kendali";

// Pin definisi
#define LED1_PIN D5
#define LED2_PIN D6
#define LED3_PIN D7
#define DHTPIN D4
#define DHTTYPE DHT11
#define LM35_PIN A0

// Inisialisasi objek
WiFiClient espclient;
PubSubClient client(espclient);
DHT dht(DHTPIN, DHTTYPE);
LiquidCrystal_I2C lcd(0x27, 16, 2); // alamat 0x27, 16x2 LCD

// Variabel untuk blink
unsigned long previousMillis[3] = {0, 0, 0}; // Waktu terakhir LED berubah per LED
int ledState[3] = {LOW, LOW, LOW};           // Status LED (LED1, LED2, LED3)
int blinkInterval[3] = {0, 0, 0};            // Interval blink untuk masing-masing LED dalam ms
bool blinkActive[3] = {true, true, true};    // Apakah LED mengikuti mode blink otomatis
bool systemActive = true;                    // Flag status sistem aktif/nonaktif

void setup_wifi() {
  delay(10);
  Serial.println();
  Serial.print("Menghubungkan ke ");
  Serial.println(ssid);

  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nWiFi Terhubung");
  Serial.print("IP: ");
  Serial.println(WiFi.localIP());
}

// Update blink interval dan aktifasi blink berdasarkan level
void setBlinkByLevel(int level) {
  // Reset semua ke default
  blinkInterval[0] = 0; blinkInterval[1] = 0; blinkInterval[2] = 0;
  blinkActive[0] = false; blinkActive[1] = false; blinkActive[2] = false;

  if (level == 1) {
    blinkInterval[0] = 1000; blinkActive[0] = true; // LED1 blink
    // LED2, LED3 mati
  } else if (level == 2) {
    blinkInterval[0] = 500; blinkActive[0] = true;  // LED1 blink
    blinkInterval[1] = 1000; blinkActive[1] = true; // LED2 blink
    // LED3 mati
  } else if (level == 3) {
    blinkInterval[0] = 300; blinkActive[0] = true;  // LED1 blink
    blinkInterval[1] = 500; blinkActive[1] = true;  // LED2 blink
    blinkInterval[2] = 800; blinkActive[2] = true;  // LED3 blink
  }
}

// Callback untuk pesan MQTT masuk (kendali LED)
void callback(char* topic, byte* payload, unsigned int length) {
  String msg = "";
  for (unsigned int i = 0; i < length; i++) msg += (char)payload[i];
  msg.trim();

  Serial.print("Pesan masuk [");
  Serial.print(topic);
  Serial.print("]: ");
  Serial.println(msg);

  if (strcmp(topic, topik_kendali) == 0) {
    if (msg == "ON1") {
      blinkActive[0] = true; // Aktifkan blink otomatis LED1
      Serial.println("LED1 ON (Blink aktif)");
    } else if (msg == "OFF1") {
      blinkActive[0] = false; // Matikan blink otomatis LED1
      digitalWrite(LED1_PIN, LOW);
      ledState[0] = LOW;
      Serial.println("LED1 OFF (Blink nonaktif)");
    } else if (msg == "ON2") {
      blinkActive[1] = true;
      Serial.println("LED2 ON (Blink aktif)");
    } else if (msg == "OFF2") {
      blinkActive[1] = false;
      digitalWrite(LED2_PIN, LOW);
      ledState[1] = LOW;
      Serial.println("LED2 OFF (Blink nonaktif)");
    } else if (msg == "ON3") {
      blinkActive[2] = true;
      Serial.println("LED3 ON (Blink aktif)");
    } else if (msg == "OFF3") {
      blinkActive[2] = false;
      digitalWrite(LED3_PIN, LOW);
      ledState[2] = LOW;
      Serial.println("LED3 OFF (Blink nonaktif)");
    } else if (msg == "AUTO") {
      blinkActive[0] = true; blinkActive[1] = true; blinkActive[2] = true;
      Serial.println("Semua LED kembali ke mode blink otomatis");
    } else if (msg == "TOGGLE") {
      systemActive = !systemActive;
      Serial.println(systemActive ? "Sistem diaktifkan" : "Sistem dinonaktifkan");
      if (!systemActive) {
        digitalWrite(LED1_PIN, LOW);
        digitalWrite(LED2_PIN, LOW);
        digitalWrite(LED3_PIN, LOW);
      }
    }
  }
}

void reconnect() {
  while (!client.connected()) {
    Serial.print("Menghubungkan MQTT ... ");
    String clientId = "ESP8266Client-" + String(ESP.getChipId());
    if (client.connect(clientId.c_str(), user_mqtt, pass_mqtt)) {
      Serial.println("terhubung");
      client.subscribe(topik_kendali); // subscribe ke topik kendali
    } else {
      Serial.print("gagal, rc=");
      Serial.print(client.state());
      Serial.println(" coba lagi dalam 5 detik");
      delay(5000);
    }
  }
}

void setup() {
  Serial.begin(9600);
  pinMode(LED1_PIN, OUTPUT);
  pinMode(LED2_PIN, OUTPUT);
  pinMode(LED3_PIN, OUTPUT);

  setup_wifi();
  client.setServer(mqtt_server, 1883);
  client.setCallback(callback);

  dht.begin();
  lcd.init();
  lcd.backlight();
}

void loop() {
  if (!client.connected()) reconnect();
  client.loop();

  // Baca suhu dari DHT11
  float suhuDHT = dht.readTemperature();
  float kelembaban = dht.readHumidity();
  if (isnan(suhuDHT) || isnan(kelembaban)) {
    Serial.println("Gagal membaca sensor DHT11!");
    suhuDHT = 0; kelembaban = 0;
  }

  // Baca suhu dari LM35
  int nilaiAnalog = analogRead(LM35_PIN);
  float volt = nilaiAnalog * (5.0 / 1023.0);
  float suhuLM35 = volt * 100.0;

  // Tentukan level LED berdasarkan suhu
  int ledLevel;
  if (suhuDHT > 30 || suhuLM35 > 30) ledLevel = 3;
  else if (suhuDHT >= 25 || suhuLM35 >= 25) ledLevel = 2;
  else ledLevel = 1;

  // Set interval blink & aktifasi blink sesuai level
  setBlinkByLevel(ledLevel);

  unsigned long now = millis();

  if (systemActive) {
    for (int i = 0; i < 3; i++) {
      if (blinkActive[i] && blinkInterval[i] > 0) {
        if (now - previousMillis[i] >= blinkInterval[i]) {
          previousMillis[i] = now;
          ledState[i] = !ledState[i];
          digitalWrite(LED1_PIN + i, ledState[i]);
        }
      } else {
        // Jika blink nonaktif, pastikan LED mati (kecuali sudah di-ON manual)
        if (!blinkActive[i]) {
          digitalWrite(LED1_PIN + i, LOW);
          ledState[i] = LOW;
        }
      }
    }
  }

  // Tampilkan suhu di LCD
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("DHT11:");
  lcd.print(suhuDHT, 1);
  lcd.print((char)223);
  lcd.print("C");
  lcd.setCursor(0, 1);
  lcd.print("LM35 :");
  lcd.print(suhuLM35, 1);
  lcd.print((char)223);
  lcd.print("C");

  // Kirim data ke MQTT (per topik, agar cocok dengan dashboard)
  if (client.connected()) {
    client.publish("iot/g231220158/suhu", String(suhuDHT, 1).c_str());
    client.publish("iot/g231220158/kelembaban", String(kelembaban, 1).c_str());
    client.publish("iot/g231220158/level", String(ledLevel).c_str());
    client.publish("iot/g231220158/status", systemActive ? "active" : "inactive");
  }

  delay(100); // Delay kecil agar blink responsif
}