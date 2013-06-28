#!/bin/bash

# 改变当前路径到脚本所在目录
cd "$(dirname "$0")";

/kingcores/local/php/bin/php  send_sina_dingshi_weibo.php  > /dev/null 2>&1 


