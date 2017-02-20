#include <Arduino.h>
const int buzzer = 9; //buzzer to arduino pin 9

// link: https://tkkrlab.nl/wiki/Arduino_KY-006_Small_passive_buzzer_module

void setup(){
 
  pinMode(buzzer, OUTPUT); // Set buzzer - pin 9 as an output

}

void loop(){
 
  //buzz(buzzer, 1000, 1000); // Send 1KHz sound signal...
  delay(1000);        // ...for 1 sec
  unsigned char i, j ;// define variables
  
    for (i = 0; i <100; i++) 
    {
      digitalWrite (buzzer, HIGH) ;// send voice
      delay (25) ;// Delay 1ms
      digitalWrite (buzzer, LOW) ;// do not send voice
      delay (25) ;// delay ms
      
    }
    
    for (i = 0; i <100; i++) 
    {
      digitalWrite (buzzer, HIGH) ;// send voice
      delay (2) ;// delay 2ms
      digitalWrite (buzzer, LOW) ;// do not send voice
      delay (2) ;// delay 2ms
    }
  
}

void buzz(int targetPin, long frequency, long length) {
  digitalWrite(13, HIGH);
  long delayValue = 1000000 / frequency / 2; // calculate the delay value between transitions
  //// 1 second's worth of microseconds, divided by the frequency, then split in half since
  //// there are two phases to each cycle
  long numCycles = frequency * length / 1000; // calculate the number of cycles for proper timing
  //// multiply frequency, which is really cycles per second, by the number of seconds to
  //// get the total number of cycles to produce
  for (long i = 0; i < numCycles; i++) { // for the calculated length of time...
    digitalWrite(targetPin, HIGH); // write the buzzer pin high to push out the diaphram
    delayMicroseconds(delayValue); // wait for the calculated delay value
    digitalWrite(targetPin, LOW); // write the buzzer pin low to pull back the diaphram
    delayMicroseconds(delayValue); // wait again or the calculated delay value
  }
  digitalWrite(13, LOW);

}
