#!/bin/bash

datestring=`date +%Y-%m-%d`
filename=/kingcores/data/ping/pingshoudu_1_${datestring}.ping
ping -c 10 www.changweibo.com >>$filename
