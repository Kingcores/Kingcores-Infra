#!/bin/bash

datestring=`date +%Y-%m-%d`
filename=/kingcores/data/ping/pingshoudu_3_${datestring}.ping
ping -c 10 www.weibotui.com >>$filename
