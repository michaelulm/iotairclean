////////////  IoT AirClean Settings
String room = "testraum3.2";
String location = "teststation3";
// TODO currently high amount of data => MongoDB will get an out of memory error otherwise, we have to find a solution
int intervalMeasurement = 30000; // each half minute
//int intervalMeasurement = 1000; // each second

//////////// INCLUDE ALL WE NEED
#include <SoftwareSerial.h>
#include <Wire.h>
// DHT Sensor
#include "DHT.h"            // DHT Sensor (Humidity and Temperature)
// CO2 Sensor
#include <NDIR_SoftwareSerial.h>
// RTC
//#include "RTClib.h"

//////////// INITIALIZE ALL STUFF WE NEED
// Initialize DHT sensor. 
#define DHTTYPE DHT22     // DHT 22 (AM2302)
#define DHTPIN 5          // what digital pin we're connected to
DHT dht(DHTPIN, DHTTYPE);

// Initialize CO2 sensor. 
// Select 2 digital pins as SoftwareSerial's Rx and Tx. For example, Rx=6 Tx=7
NDIR_SoftwareSerial mySensor(11, 12);

// XBee Modul
SoftwareSerial xbee(8,9); // RX, TX

// Date and time functions using a DS1307 RTC connected via I2C and Wire lib
//RTC_DS1307 rtc;

// defines tmp Values, for easier logging, 
// only new values will be stored in tmpValues
// and those values will be logged, sometimes sensor not delivering data
float tmpT = 0.0;
float tmpH = 0.0;
int tmpCO2 = 0;

// LED RGB Config
int redpin   = 10; // select the pin for the red LED
int greenpin = 7;  // select the pin for the green LED
int bluepin  = 6;  // select the pin for the  blue LED


//////////// START OF APPLICATION 
void setup() {
  Serial.begin(57600);

  // setup rgb pins for write out
  pinMode(redpin, OUTPUT);
  pinMode(bluepin, OUTPUT);
  pinMode(greenpin, OUTPUT);
  led_colortest();
  led_blue();
  
  // start with DHT Sensor
  dht.begin();
    
  // start with xbee communication, and set the data rate for the SoftwareSerial port
  // all other settigns should already be configured before by XBee initialization and setup of modul
  xbee.begin( 9600 );
  
  // start with RTC
  /*if (! rtc.begin()) {
    Serial.println("Couldn't find RTC");
    while (1);
  }*/
  
  // start with co2 sensor
  if (mySensor.begin()) {
    Serial.println("Wait 10 seconds for sensor initialization...");
    delay(10000);
  } else {
    Serial.println("ERROR: Failed to connect to the sensor.");
    while(1);
  }
}

// TODO Software Architecture => split this method and Refactor into a modular concept
void loop() {

  // Wait a few seconds between measurements.
  delay(intervalMeasurement);

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

  led_white();
  // Read co2 value by ppm
  if (mySensor.measure()) {
    Serial.print("CO2 Concentration is ");
    Serial.print(mySensor.ppm);
    Serial.println("ppm");
    tmpCO2 = mySensor.ppm;
  } else {
    led_blink(3);
    Serial.println("Sensor communication error.");
  }

  // get current time from preconfigured RTC
  //DateTime now = rtc.now();

   // Create json data to send
   // with RTC
   //String data = "{\"t\": " + String(tmpT) + ", \"h\": " + String(tmpH) + ", \"co2\": " + String(tmpCO2) + ", \"station\": \"" + room +"@"+location+"\", \"location\": \"" +location+"\", \"room\": \"" + room +"\", \"measured\": \""+ String(now.year(), DEC) +"-"+ String(now.month(), DEC) +"-"+ String(now.day(), DEC) + " " + String(now.hour(), DEC) +":"+ String(now.minute(), DEC) +":"+ String(now.second(), DEC) + "\" }";
   // without RTC
   String data = "{\"t\": " + String(tmpT) + ", \"h\": " + String(tmpH) + ", \"co2\": " + String(tmpCO2) + ", \"station\": \"" + room +"@"+location+"\", \"location\": \"" +location+"\", \"room\": \"" + room +"\" }";

  // visualize current air quality by diagram color schema
  if(tmpCO2 < 450){
    led_white();
  } else if(tmpCO2 < 800){
    led_green();
  } else if(tmpCO2 < 1200){
    led_yellow();
  } else if(tmpCO2 < 1600){
    led_orange();
  }else{
    led_red();
  }
  
  // print for debugging
  Serial.println(data);

  // transfer to Raspberry PI with XBee Communication
  xbee.print( data);
}

// needed led methods
void led_green(){
  led_switch(0,255,0);
}
void led_red(){
  led_switch(255,0,0);
}
void led_orange(){
  led_switch(50,50,0);
}
void led_yellow(){
  led_switch(255,255,0);
}
void led_white(){
  led_switch(255,255,255);
}
void led_off(){
  led_switch(0,0,0);
}
void led_blue(){
  led_switch(0,0,255);
}

// blink x times white - off - white - off ...
void led_blink(int times){
  for(int nr = 0; nr < times; nr++){
    led_white();
    delay(500);
    led_off();
    delay(500);
  }
}

// switch to wanted RGB value
void led_switch(int r, int g, int b){
    analogWrite(redpin, r);
    analogWrite(greenpin, g);
    analogWrite(bluepin, b);
}

// color test for startup to take care RGB Led is working correctly
void led_colortest(){

  led_blink(2);
  
  led_green();
  delay(1000);
  led_yellow();
  delay(1000);
  led_orange();
  delay(1000);
  led_red();
  delay(1000);
  
  led_blink(2);
}


