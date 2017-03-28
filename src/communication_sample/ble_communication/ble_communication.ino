#include <SoftwareSerial.h>

#include <AltSoftSerial.h>
AltSoftSerial BTSerial; 

char c=' ';
boolean NL = true;

int ledPin = 13;

SoftwareSerial mySerial(7, 8); // RX, TX  
// Connect HM10      Arduino Nano
//     Pin 1/TXD          Pin 7
//     Pin 2/RXD          Pin 8

void setup() {
  Serial.begin( 9600 );    // 9600 is the default baud rate for the serial Bluetooth module
  // If the baudrate of the HM-10 module has been updated,
  // you may need to change 9600 by another value
  // Once you have found the correct baudrate,
  // you can update it using AT+BAUDx command 
  // e.g. AT+BAUD0 for 9600 bauds
  //mySerial.begin(9600);

  
    BTSerial.begin(9600);  
    Serial.println("BTserial started at 9600");
    Serial.println("");

  Serial.println("Start blink test");
  blinkLED(5);
  Serial.println("Finished blink test");
  Serial.println("Ready for messsages");

  
}

void loop() {
  /*
  // listen for the data
  if ( Serial.available() > 0 ) {
    // read a numbers from serial port
    int count = Serial.parseInt();
    
     // print out the received number
    if (count > 0) {
        Serial.print("You have input: ");
        Serial.println(String(count));
        // blink the LED
        blinkLED(count);
    }
  }*/

  /*int c;

//  Serial.println("Wait for input");
  if (mySerial.available()) {
    c = mySerial.read();  
    Serial.println("Got input:");
    if (c != 0)
    {
      // Non-zero input means "turn on LED".
      Serial.println("  on");
      Serial.println(String(c));
      blinkLED(c);
      mySerial.print("You have input: ");
      mySerial.println(String(c));
    }
    else
    {
      // Input value zero means "turn off LED".
      Serial.println("  off");
    }  
  }*/
  
    //delay(1000);


    
    // Read from the Bluetooth module and send to the Arduino Serial Monitor
    if (BTSerial.available())
    {
        c = BTSerial.read();
        Serial.write(c);
    }

    BTSerial.println("Hello");
    Serial.println("Hello");
    delay(1000);
 
}

void blinkLED(int count) {
  for (int i=0; i< count; i++) {
    digitalWrite(ledPin, HIGH);
    delay(500);
    digitalWrite(ledPin, LOW);
    delay(500);
  } 
}
