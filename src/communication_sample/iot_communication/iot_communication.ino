// WIFI
#include <LWiFi.h>
#include <LWiFiUdp.h>
#include <LWiFiClient.h>
// Time
#include <LDateTime.h> // useful link: http://labs.mediatek.com/api/linkit-one/frames.html?frmname=topic&frmfile=LDateTimeClass__getTime@datetimeInfo__.html
#include <Time.h>
// MQTT
#include <PubSubClient.h> // http://knolleary.net/arduino-client-for-mqtt/

// WIFI Settings
char ssid[] = "wireless4home";  //  your network SSID (name)
char pass[] = "...";       // your network password

unsigned int localPort = 2390;      // local port to listen for UDP packets

// Date Time Settings
//datetimeInfo t;
unsigned int rtc;
boolean gotTime = false;

// NTP Settings
IPAddress timeServer(129, 6, 15, 28); // time.nist.gov NTP server
const int NTP_PACKET_SIZE = 48; // NTP time stamp is in the first 48 bytes of the message
byte packetBuffer[NTP_PACKET_SIZE]; //buffer to hold incoming and outgoing packets

// A UDP instance to let us send and receive packets over UDP
LWiFiUDP Udp;

LWiFiClient wifiClient;

// MQTT Settings
byte mqttBroker[] = { 192, 168, 100, 195 };// IP des MQTT Servers
PubSubClient mqttClient(wifiClient);

void setup() {

  // Open serial communications and wait for port to open:
  Serial.begin(115200);

  Serial.println("setup()");

  // attempt to connect to Wifi network:
  LWiFi.begin();
  while (!LWiFi.connectWPA(ssid, pass))
  {
    delay(1000);
    Serial.println("retry WiFi AP");
  }
  Serial.println("Connected to wifi");
  printWifiStatus();
  
   mqttClient.setServer( mqttBroker, 1883 );
   mqttClient.setCallback( callback );

  delay(10000);

  Serial.println("\nStarting connection to server...");
  Udp.begin(localPort);

  Serial.println("setup() done");
  /*
  setSyncProvider(getNtpTime);
  while(timeStatus()== timeNotSet)   
     ; // wait until the time is set by the sync provider
   */


}

void loop() {

  if(!gotTime){
    Serial.println("sync with NTP and get current time");
    // now sync time from ntp
    getNtpTime();

  } else {
    Serial.println("loop...");
    // get current time 
    delay(1000);
    digitalClockDisplay();
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
     
     // Auslesen der Temperatur
     //TempSensors.requestTemperatures();
     // Bei Änderungen zum vorherigen Wert Publizieren
     //if (tempC != TempSensors.getTempCByIndex(0) ) {
     //tempC = TempSensors.getTempCByIndex(0);
     // Publizierung des Wertes. Vorher Converierung vn float zu String.
     mqttClient.publish("/iotairclean", "TEST");
     //}
     //mqttClient.loop(); // Schleife für MQTT
  }
  

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


/*-------- Clock code ----------*/
void digitalClockDisplay(){
  //LDateTime.getTime(&t);
  //LDateTime.getRtc(&rtc);
  // digital clock display of the time
  /*Serial.print(hour());
  printDigits(minute());
  printDigits(second());
  Serial.print(" ");
  Serial.print(day());
  Serial.print(" ");
  Serial.print(month());
  Serial.print(" ");
  Serial.print(year()); 
  Serial.println(); */
  /*
 Serial.print("Current ");
 Serial.print(t.mon);
 Serial.print("/");
 Serial.print(t.day);
 Serial.print("/");
 Serial.print(t.year);
 Serial.print(" ");
 Serial.print(t.hour);
 Serial.print(":");
 Serial.print(t.min);
 Serial.print(":");
 Serial.print(t.sec);
 Serial.println();*/
}

void printDigits(int digits){
  // utility function for digital clock display: prints preceding colon and leading 0
  Serial.print(":");
  if(digits < 10)
    Serial.print('0');
  Serial.print(digits);
}


/*-------- NTP code ----------*/

unsigned long getNtpTime()
{
  sendNTPpacket(timeServer);
  delay(1000);
  if ( Udp.parsePacket() )
  {
    Serial.println("packet received");
    // We've received a packet, read the data from it
    memset(packetBuffer, 0xcd, NTP_PACKET_SIZE);
    Udp.read(packetBuffer, NTP_PACKET_SIZE); // read the packet into the buffer
    for (int i = 0; i < NTP_PACKET_SIZE; ++i)
    {
      Serial.print(packetBuffer[i], HEX);
    }
    Serial.println();


    //the timestamp starts at byte 40 of the received packet and is four bytes,
    // or two words, long. First, esxtract the two words:
    unsigned long highWord = word(packetBuffer[40], packetBuffer[41]);
    unsigned long lowWord = word(packetBuffer[42], packetBuffer[43]);
    // combine the four bytes (two words) into a long integer
    // this is NTP time (seconds since Jan 1 1900):
    unsigned long secsSince1900 = highWord << 16 | lowWord;
    Serial.print("Seconds since Jan 1 1900 = " );
    Serial.println(secsSince1900);

    // now convert NTP time into everyday time:
    Serial.print("Unix time = ");
    // Unix time starts on Jan 1 1970. In seconds, that's 2208988800:
    const unsigned long seventyYears = 2208988800UL;
    // subtract seventy years:
    unsigned long epoch = secsSince1900 - seventyYears;
    // print Unix time:
    Serial.println(epoch);

    //String l_line = epoch.toString();
    time_t t = epoch; 
    //struct tm *aTime = localtime(&t);
    Serial.println(t);

    /*
    datetimeInfo now;
    now.year = 2017;
    now.mon =  2;
    now.day =  10;
    now.hour = (epoch  % 86400L) / 3600;
    now.min = (epoch  % 3600) / 60;
    now.sec = epoch % 60;
*/
    //setTime(2);
    // set current time
    //LDateTime.setTime(&now);
    gotTime = true;
    return epoch;
  }
  return 0; // return 0 if unable to get the time
}

// send an NTP request to the time server at the given address
unsigned long sendNTPpacket(IPAddress& address)
{
  Serial.println("sendNTPpacket");
  // set all bytes in the buffer to 0
  memset(packetBuffer, 0, NTP_PACKET_SIZE);
  // Initialize values needed to form NTP request
  // (see URL above for details on the packets)
  //Serial.println("2");
  packetBuffer[0] = 0b11100011;   // LI, Version, Mode
  packetBuffer[1] = 0;     // Stratum, or type of clock
  packetBuffer[2] = 6;     // Polling Interval
  packetBuffer[3] = 0xEC;  // Peer Clock Precision
  // 8 bytes of zero for Root Delay & Root Dispersion
  packetBuffer[12]  = 49;
  packetBuffer[13]  = 0x4E;
  packetBuffer[14]  = 49;
  packetBuffer[15]  = 52;

  //Serial.println("3");

  // all NTP fields have been given values, now
  // you can send a packet requesting a timestamp:
  Udp.beginPacket(address, 123); //NTP requests are to port 123
  //Serial.println("4");
  Udp.write(packetBuffer, NTP_PACKET_SIZE);
  //Serial.println("5");
  Udp.endPacket();
  //Serial.println("6");
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


/*-------- Measurement code ----------*/


void handleMeasurement() {

  Serial.println("TEST");
  
}
