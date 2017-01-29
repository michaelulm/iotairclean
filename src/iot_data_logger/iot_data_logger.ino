
#include <LFlash.h>         // Internal Flash
#include "DHT.h"            // DHT Sensor (Humidity and Temperature)
#include <Grove_LED_Bar.h>  // LED-Bar
#define Drv LFlash          // for usage with Internal Flash
#define DEBUG 0             // for usage with Co2 Sensor

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
#define DHTPIN 2          // what digital pin we're connected to
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
  LFile dataFile = Drv.open(file, FILE_WRITE);
  if (dataFile)
  {
    dataFile.println("---------- START NEW SENSOR LOGGING ---------- ");
    dataFile.close();
    Serial.println("File written.");
  }
  
  // starts DHT Sensor
  dht.begin();
  // nothing to initialize for LED-Bar
  bar.begin();
  
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
  } else if(tmpCO2 >=1800){
    bar.setBits(0b000001111111110); // up to 9 Lights are on    
  } else if(tmpCO2 >=1600){
    bar.setBits(0b000001111111100); // up to 8 Lights are on    
  } else if(tmpCO2 >=1400){
    bar.setBits(0b000001111111000); // up to 7 Lights are on    
  } else if(tmpCO2 >=1200){
    bar.setBits(0b000001111110000); // up to 6 Lights are on    
  } else if(tmpCO2 >=1000){
    bar.setBits(0b000001111100000); // up to 5 Lights are on    
  } else if(tmpCO2 >=800){
    bar.setBits(0b000001111000000); // up to 4 Lights are on    
  } else if(tmpCO2 >=600){
    bar.setBits(0b000001110000000); // up to 3 Lights are on    
  } else if(tmpCO2 >=400){
    bar.setBits(0b000001100000000); // up to 2 Lights are on    
  } else if(tmpCO2 >=200){
    bar.setBits(0b000001000000000); // up to 1 Lights are on    
  } else {
    bar.setBits(0b000000000000000); // ALL Lights are OFF    
  }

  // log ID
  logID++;

  // write log file
  LFile dataFile = Drv.open(file, FILE_WRITE);
  if (dataFile)
  {
    // log current data
    dataFile.print(logID);
    dataFile.print(";");
    dataFile.print(tmpH);
    dataFile.print(";");
    dataFile.print(tmpT);
    dataFile.print(";");
    dataFile.print(tmpCO2);
    dataFile.println(";");
    dataFile.close();
    // debug to console current data
    Serial.print("File written. Current Sensor Data: \t");
    Serial.print("Humidity: ");
    Serial.print(tmpH);
    Serial.print("%\t");
    Serial.print("Temperature: ");
    Serial.print(tmpT);
    Serial.print(" *C ");
    Serial.print("\t");
    Serial.print("CO2: ");
    Serial.print(tmpCO2);
    // TODO add timestamp for easier analysing measurement logs.
    Serial.println("");
  }
  else Serial.println("Error opening file.");

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

