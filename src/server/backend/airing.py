#!/usr/bin/env python

# original author: Elisabeth Haberl, thanks for supporting the project

def main(data, maxListLen, ppmFalling, db, location, station):
    tmpItem         = {"co2": 0};
    co2Falling      = False
    airingDetected  = False
    tmpList         = []
    airingList      = []
    airingValid     = 0 # is 0 or smaller when valid -> prevents that one airing is noted als multiple airings

    # iterate all items to find airing
    for item in data:

        # detect if co2 falling
        if item["co2"] < tmpItem["co2"]:
            co2Falling = True
        else:
            co2Falling = False

        # react on co2-state
        if co2Falling:
            airingList.append(item)
        else:
            # airing was detected and co2 is increasing again
            if airingDetected and tmpItem["co2"] < 600:
                print "\n+++ AIRING FINISHED"
                print "start:   ",airingList[0]['measured']
                print "end:     ",airingList[len(airingList)-1]['measured']
                airingDetected  = False

                # store in airing db
                data = db.airing.insert_one({
                    "location"  : location,
                    "station"   : station,
                    "startTime" : airingList[0]['measured'],
                    "endTime"   : airingList[len(airingList)-1]['measured'],
                    "startCo2"  : airingList[0]['co2'],
                    "endCo2"    : airingList[len(airingList)-1]['co2']
                })

            airingList = []


        # delete oldest item if max-length is reached
        if len(tmpList) >= maxListLen:
            tmpList.pop(0)
        tmpList.append(item["co2"])

        # start comparing measurements
        if len(tmpList) == maxListLen:
            airingValid -= 1
            if tmpList[0] - tmpList[maxListLen-1] >= ppmFalling and tmpList[maxListLen-1] <= 600:
                if airingValid <= 0:
                    airingDetected = True
                    print '\n+++ AIRING DETECTED:',item["measured"]
                    airingValid = maxListLen # set list size to prevent next value to be airing too

        tmpItem["co2"] = item["co2"] # prepare for next iteration
