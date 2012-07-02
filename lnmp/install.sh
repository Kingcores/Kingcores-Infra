#!/bin/bash
SCRIPT_DIR=$(cd "$(dirname "$0")";pwd)

# utils
. ${SCRIPT_DIR}/scripts/bash_utils.inc.sh

# load config
. ${SCRIPT_DIR}/config.inc.sh

# script depended directories
export PACKAGE_DIR=${SCRIPT_DIR}/packages
export CONFIG_DIR=${SCRIPT_DIR}/conf
export INITD_DIR=${SCRIPT_DIR}/init.d
export TEST_DIR=${SCRIPT_DIR}/test

# script options
export NO_PROMPT=0
export ALL_REINSTALL=0
export NO_BACKUP=0
export AUTO_DOWNLOAD=0
export USE_TAR_BASENAME=0

echo "Kingcores LNMP Installation Script"
echo

[ ${UID} -eq 0 ] || exit_with_error "This script must be run with root account!"

# parse arguments
while getopts ":yrfdt" OPTNAME
do
    case "${OPTNAME}" in
      "y")
        echo "Silence mode enabled."
        NO_PROMPT=1
        ;;
      "r")
        echo "All-reinstall mode enabled."
        ALL_REINSTALL=1
        ;;
      "f")
        echo "Force mode (no backup) enabled."
        NO_BACKUP=1
        ;;
	  "d")
	  	echo "Auto-Download mode enabled."
	  	AUTO_DOWNLOAD=1
		;;
	  "t")
	  	echo "Use tar package basename as component id name."
	  	USE_TAR_BASENAME=1
		;;
      "?")
        echo "Unknown option ${OPTARG}"
        cat <<USAGE
Kingcores LNMP Installation Script

Usages:
    install.sh [-y] [-r] [-f] [-d] [-t]
    y: no prompt while installing
    r: all components will be reinstalled
    f: no backup will be performed while installing
    d: auto download package while package not found
    t: to use tar package basename as component id name

Examples:
    install.sh -y

USAGE
        exit 1
        ;;
      ":")
        echo "No argument value for option $OPTARG"
        ;;
      *)
        echo "Unknown error while processing options"
        ;;
    esac
done

function main()
{
	echo
    echo "This script is going to install [${COMPONENTS}]."
	echo
    echo "Target path: ${BASE_DIR}"
    echo "Backup path: ${BACKUP_DIR}"
    echo "Data path: ${DATA_BASE_DIR}"
    echo "Log path: ${LOG_BASE_DIR}"
    echo "Log path: ${LOG_BASE_DIR}"
    echo

    if [ ${NO_PROMPT} -eq 0 ]
    then
        read -p "Continue?[y|n]:" ANSWER
        [ "${ANSWER}" != "y" ] && exit_with_error "Cancelled by user."
    fi

    [ -d ${BASE_DIR} ] || mkdir -p ${BASE_DIR}
    echo

    #add /usr/local/lib to ENVIRONMENT variable LD_LIBRARY_PATH for php to find libnpc.so
	add_custom_lib_path /usr/local/lib

    for COMPONENT in ${COMPONENTS}
    do
        .  ${SCRIPT_DIR}/scripts/install_${COMPONENT}.inc.sh
    done
}

main
