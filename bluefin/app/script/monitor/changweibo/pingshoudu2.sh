#!/bin/bash

datestring=`date +%Y-%m-%d`
filename=/kingcores/data/ping/pingshoudu_2_${datestring}.ping
ping -c 10 www.kingcores.com >>$filename
