#include <avr/dtostrf.h>
#include <LFlash.h>         // Internal Flash
#include "DHT.h"            // DHT Sensor (Humidity and Temperature)
#include <Grove_LED_Bar.h>  // LED-Bar
#define Drv LFlash          // for usage with Internal Flash
#define DEBUG 0             // for usage with Co2 Sensor

// Date and time functions using a DS1307 RTC connected via I2C and Wire lib
#include <Wire.h>
#include "RTClib.h"
RTC_DS1307 rtc;

// WIFI
#include <LWiFi.h>
#include <LWiFiUdp.h>
#include <LWiFiClient.h>

// WIFI Settings
char ssid[] = "wireless4home";  //  your network SSID (name)
char pass[] = "...";       // your network password

LWiFiClient wifiClient;

// MQTT
#include <PubSubClient.h> // http://knolleary.net/arduino-client-for-mqtt/

// MQTT Settings
byte mqttBroker[] = { 192, 168, 100, 191 };// IP des MQTT Servers
PubSubClient mqttClient(wifiClient);

// define log ID
int logID = 0;

// define file
char file[15] = "";

// defines tmp Values, for easier logging, 
// only new values will be stored in tmpValues
// and those values will be logged
float tmpT = 0.0;
float tmpH = 0.0;
int tmpCO2 = 0;

// Initialize DHT sensor. 
#define DHTTYPE DHT22     // DHT 22 (AM2302)
#define DHTPIN 3          // what digital pin we're connected to
DHT dht(DHTPIN, DHTTYPE);

// Initialize Co2 sensor.
const unsigned char cmd_get_sensor[] =
{
    0xff, 0x01, 0x86, 0x00, 0x00,
    0x00, 0x00, 0x00, 0x79
};
unsigned char dataRevice[9];
int temperature;
int CO2PPM;

// Initialize LED-Bar.
Grove_LED_Bar bar(7, 6, 0);  // Clock pin, Data pin, Orientation

void setup() {

  Serial1.begin(9600);   // Sensor uses Serial1 on Photon
  Serial.begin(115200);  // This is the USB serial port on the Photon => used by sensor
  
  Serial.begin(57600);
  if (! rtc.begin()) {
    Serial.println("Couldn't find RTC");
    while (1);
  }
  // init drive
  pinMode(10, OUTPUT); //needed for SD card
  if(!Drv.begin())
  {
    Serial1.println("Error initalizing memory.");  
    while(true);
  }

  // define file
  String file_name = "data_log";
  // TODO add datetime to string
  // file_name += ""; // DATETIME INSERT
  file_name += ".txt";
  file_name.toCharArray(file, 14);

  // mark new start of sensor logging
  /*LFile dataFile = Drv.open(file, FILE_WRITE);
  if (dataFile)
  {
    dataFile.println("---------- START NEW SENSOR LOGGING ---------- ");
    dataFile.close();
    Serial.println("File written.");
  }*/
  
  // starts DHT Sensor
  dht.begin();
  // nothing to initialize for LED-Bar
  bar.begin();


  /* WIFI CONNECTION */
  // attempt to connect to Wifi network:
  LWiFi.begin();
  while (!LWiFi.connectWPA(ssid, pass))
  {
    delay(1000);
    Serial.println("retry WiFi AP");
  }
  Serial.println("Connected to wifi");
  printWifiStatus();

  /* MQTT CONNECTION */
  mqttClient.setServer( mqttBroker, 1883 );
  mqttClient.setCallback( callback );
}

// TODO Software Architecture => split this method and Refactor into a modular concept
void loop() {

  // Wait a few seconds between measurements.
  delay(1000);

  // Reading temperature or humidity takes about 250 milliseconds!
  // Read humidity as percent value
  float h = dht.readHumidity();
  if (!isnan(h)){
    tmpH = h;
  }
  
  // Read temperature as Celsius (the default)
  float t = dht.readTemperature();
  if (!isnan(t)){
    tmpT = t;
  }

  // Read co2 value by ppm
  if(dataReceiveCo2() && !isnan(CO2PPM)){
    tmpCO2 = CO2PPM;
  }

  // Wait a second for new measure / log entry
  delay(1000);
  // Turn off all LEDs, otherwise all LEDs will always be on
  bar.setBits(0x0);
  // now we can switch the LED-Bar
  if(tmpCO2 >= 2000){
    bar.setBits(0b000001111111111); // ALL Lights are on
  } else if(tmpCO2 >= 1840){
    bar.setBits(0b000001111111110); // up to 9 Lights are on    
  } else if(tmpCO2 >=1680){
    bar.setBits(0b000001111111100); // up to 8 Lights are on    
  } else if(tmpCO2 >=1520){
    bar.setBits(0b000001111111000); // up to 7 Lights are on    
  } else if(tmpCO2 >=1360){
    bar.setBits(0b000001111110000); // up to 6 Lights are on    
  } else if(tmpCO2 >=1200){
    bar.setBits(0b000001111100000); // up to 5 Lights are on    
  } else if(tmpCO2 >=1040){
    bar.setBits(0b000001111000000); // up to 4 Lights are on    
  } else if(tmpCO2 >=880){
    bar.setBits(0b000001110000000); // up to 3 Lights are on    
  } else if(tmpCO2 >=720){
    bar.setBits(0b000001100000000); // up to 2 Lights are on    
  } else if(tmpCO2 >=560){
    bar.setBits(0b000001000000000); // up to 1 Lights are on    
  } else {
    bar.setBits(0b000000000000000); // ALL Lights are OFF    
  }

  // log ID
  logID++;

  // write log file
  //LFile dataFile = Drv.open(file, FILE_WRITE);
  //if (dataFile)
  //{
    /*// write to file
    // log current DATETIME
    dataFile.print(now.year(), DEC);
    dataFile.print('-');
    dataFile.print(now.month(), DEC);
    dataFile.print('-');
    dataFile.print(now.day(), DEC);
    dataFile.print(' ');
    dataFile.print(now.hour(), DEC);
    dataFile.print(':');
    dataFile.print(now.minute(), DEC);
    dataFile.print(':');
    dataFile.print(now.second(), DEC);
    // log current data
    dataFile.print(";");
    dataFile.print(logID);
    dataFile.print(";");
    dataFile.print(tmpH);
    dataFile.print(";");
    dataFile.print(tmpT);
    dataFile.print(";");
    dataFile.print(tmpCO2);
    dataFile.println(";");
    dataFile.close();*/

    
  //}
  //else Serial.println("Error opening file.");

  /* MQTT CONNECTION */
     // Aufbau der Verbindung mit MQTT falls diese nicht offen ist.
     if (!mqttClient.connected()) {       
        while (!mqttClient.connected()) {
          Serial.print("Connecting to MQTT broker ...");
          // Attempt to connect
          if ( mqttClient.connect("LinkIt One Client") ) { // Better use some random name
            Serial.println( "[DONE]" );
            // Publish a message on topic "outTopic"
            mqttClient.publish( "iotairclean","Hello, This is LinkIt One" );
          // Subscribe to topic "inTopic"
            mqttClient.subscribe( "iotairclean" );
          } else {
            Serial.print( "[FAILED] [ rc = " );
            Serial.print( mqttClient.state() );
            Serial.println( " : retrying in 5 seconds]" );
            // Wait 5 seconds before retrying
            delay( 5000 );
          }
        }
     }
    /* MQTT PUBLISHING */
    // Buffer big enough for 7-character float
    char resultT[5]; 
    char resultH[5];
    char resultCO2[7];
    dtostrf(tmpT, 6, 2, resultT); // Leave room for too large numbers!
    dtostrf(tmpH, 6, 2, resultH);
    dtostrf(tmpCO2, 6, 2, resultCO2);

    DateTime now = rtc.now();

     // Create json data to send
    String data = "{\"t\": " + String(resultT) + ", \"h\": " + String(resultH) + ", \"co2\": " + String(resultCO2) + ", \"station\": \"michaelulm@home\", \"measured\": \""+ String(now.year(), DEC) +"-"+ String(now.month(), DEC) +"-"+ String(now.day(), DEC) + " " + String(now.hour(), DEC) +":"+ String(now.minute(), DEC) +":"+ String(now.second(), DEC) + "\" }";
    // Get the data string length
    int length = data.length();
    char msgBuffer[length];
    // Convert data string to character buffer
    data.toCharArray(msgBuffer,length+1);

    // Send information to mqtt topic
    mqttClient.publish("iotairclean", msgBuffer);

    // Call the loop continuously to establish connection to the server
    mqttClient.loop();
}

/*-------- MQTT Code ----------*/
void callback( char* topic, byte* payload, unsigned int length ) {
  Serial.print( "Recived message on Topic:" );
  Serial.print( topic );
  Serial.print( "    Message:");
  for (int i=0;i<length;i++) {
    Serial.print( (char)payload[i] );
  }
  Serial.println();
}

/*-------- WIFI code ----------*/

void printWifiStatus()
{
  // print the SSID of the network you're attached to:
  Serial.print("SSID: ");
  Serial.println(LWiFi.SSID());

  // print your LWiFi shield's IP address:
  IPAddress ip = LWiFi.localIP();
  Serial.print("IP Address: ");
  Serial.println(ip);

  // print the received signal strength:
  long rssi = LWiFi.RSSI();
  Serial.print("signal strength (RSSI):");
  Serial.print(rssi);
  Serial.println(" dBm");
}

bool dataReceiveCo2(void)
{
    byte data[9];
    int i = 0;
 
    //transmit command data
    for(i=0; i<sizeof(cmd_get_sensor); i++){
        Serial1.write(cmd_get_sensor[i]);
    }
    delay(10);
    //begin reveiceing data
    if(Serial1.available()){
        while(Serial1.available()){
            for(int i=0;i<9; i++){
                data[i] = Serial1.read();
            }
        }
    }
 
#if DEBUG
    for(int j=0; j<9; j++){
        Serial.print(data[j]);
        Serial.print(" ");
    }
    Serial.println("");
#endif
 
    if((i != 9) || (1 + (0xFF ^ (byte)(data[1] + data[2] + data[3]
    + data[4] + data[5] + data[6] + data[7]))) != data[8]){
        return false;
    }
    CO2PPM = (int)data[2] * 256 + (int)data[3];
    //temperature = (int)data[4] - 40; // don't needed currently because of other sensor
 
    return true;
}

