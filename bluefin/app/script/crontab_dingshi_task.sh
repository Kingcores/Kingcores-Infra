#!/bin/bash

# 改变当前路径到脚本所在目录
cd "$(dirname "$0")";

/kingcores/local/php/bin/php  execute_dingshi_task.php  > /dev/null 2>&1

