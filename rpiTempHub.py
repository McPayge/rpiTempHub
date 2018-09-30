#!/usr/bin/env python



import time
from RF24 import *
import RPi.GPIO as GPIO



radio = RF24(RPI_V2_GPIO_P1_22, BCM2835_SPI_CS0, BCM2835_SPI_SPEED_8MHZ)
rxAddress = [0xF0F0F0F0E1]

radio.begin()
radio.enableDynamicPayloads()
radio.setChannel(0x76)
radio.setAutoAck(1)
radio.enableAckPayload()
radio.printDetails()
print(" ")
print(" ")

radio.openReadingPipe(1,rxAddress[0])
radio.startListening()

while 1:
    if radio.available():
        while radio.available():
            len = radio.getDynamicPayloadSize()
            if(len==8):
                # Good length
                received_payload = radio.read(len)
                sensorID=received_payload[0]
                alarmLevel=received_payload[1]
                tempLevel=(received_payload[2]<<8) + received_payload[3]
                humidityLevel = (received_payload[4]<<8) + received_payload[5]
                batteryLevel=(received_payload[6]<<8) + received_payload[7]
                print("Received data from SensorID=0x{:x}\nalarmLevel={:d}\ntempLevel={:d}\nhumidityLevel={:d}\nbatteryLevel={:d}\n".format(sensorID, alarmLevel,tempLevel,humidityLevel,batteryLevel))
                






