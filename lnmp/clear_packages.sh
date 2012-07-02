#/bin/bash

SCRIPT_DIR=$(cd "$(dirname "$0")"; pwd)

for FOLDER in `ls -l ${SCRIPT_DIR}/packages |grep '^d'|awk '{print $9}'`;do echo "remove ${SCRIPT_DIR}/packages/${FOLDER} ";rm -rf ${SCRIPT_DIR}/packages/${FOLDER} ;done
