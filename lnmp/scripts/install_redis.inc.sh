#install_redis

if [ ${USE_TAR_BASENAME} -eq 1 ]
then
    REDIS_ID_NAME=${REDIS_TAR_NAME}
else
    REDIS_ID_NAME=redis
fi

REDIS_DIR=${BASE_DIR}/${REDIS_ID_NAME}
REDIS_LOG_DIR=${LOG_BASE_DIR}/${REDIS_ID_NAME}
REDIS_DATA_DIR=${DATA_BASE_DIR}/${REDIS_ID_NAME}

function install_redis()
{
    prepare_package ${REDIS_ID_NAME} ${PACKAGE_DIR} ${REDIS_TAR_NAME} ${REDIS_DIR} ${DOWNLOAD_BASE_URL} \
        ${BACKUP_DIR_FLAG} ${NO_PROMPT} ${REDIS_USER} ${REDIS_GROUP}

    if [ ${NO_BACKUP} -eq 1 ]; then
        [ -d ${REDIS_DATA_DIR} ] && rm -rf ${REDIS_DATA_DIR}
    else
        backup ${REDIS_DATA_DIR} ${BACKUP_DIR}/data ${NO_PROMPT}
    fi

    echo
    echo "Creating directories for redis ..."
    echo
    [ -d ${REDIS_DATA_DIR} ] || mkdir -p ${REDIS_DATA_DIR}
    chown -R ${REDIS_USER}:${REDIS_GROUP} ${REDIS_DATA_DIR}

    [ -d ${REDIS_LOG_DIR} ] || mkdir -p ${REDIS_LOG_DIR}
    chown -R ${REDIS_USER}:${REDIS_GROUP} ${REDIS_LOG_DIR}

    echo
    echo "Building ${REDIS_TAR_NAME} package ..."
    echo
    cd ${PACKAGE_DIR}/${REDIS_TAR_NAME}
    make -s && make PREFIX=${REDIS_DIR} -s install
    [ ! $? -eq 0 ] && exit_with_error "Building ${REDIS_TAR_NAME} package failed!"

    echo
    echo "Updating redis.conf ..."
    echo
    [ -d ${REDIS_DIR}/conf ] || mkdir -p ${REDIS_DIR}/conf
    /bin/cp -f ${CONFIG_DIR}/redis.conf ${REDIS_DIR}/conf/redis.conf
    sed -i "s:__REDIS_DATA_DIR__:${REDIS_DATA_DIR}:g"  ${REDIS_DIR}/conf/redis.conf
    sed -i "s:__REDIS_LOG_DIR__:${REDIS_LOG_DIR}:g"  ${REDIS_DIR}/conf/redis.conf
    sed -i "s:__REDIS_PORT__:${REDIS_PORT}:g"  ${REDIS_DIR}/conf/redis.conf
    sed -i "s:__REDIS_PASSWORD__:${REDIS_SECRET_CODE}:g"  ${REDIS_DIR}/conf/redis.conf

    echo ${REDIS_SECRET_CODE} > /etc/${REDIS_ID_NAME}_password
    chmod 600 /etc/${REDIS_ID_NAME}_password

    echo
    echo "Setting up ${REDIS_ID_NAME} service ..."
    echo
    /bin/cp -f ${INITD_DIR}/redis /etc/init.d/${REDIS_ID_NAME}
    sed -i "s:__REDIS_DIR__:"${REDIS_DIR}":g" /etc/init.d/${REDIS_ID_NAME}
    sed -i "s:__REDIS_LOG_DIR__:"${REDIS_LOG_DIR}":g" /etc/init.d/${REDIS_ID_NAME}
    sed -i "s:__REDIS_PORT__:${REDIS_PORT}:g"  /etc/init.d/${REDIS_ID_NAME}
    sed -i "s:__REDIS_PASSWORD__:${REDIS_SECRET_CODE}:g" /etc/init.d/${REDIS_ID_NAME}
    sed -i "s:__REDIS_PASSWORD_FILE__:/etc/${REDIS_ID_NAME}_password:g" /etc/init.d/${REDIS_ID_NAME}

    chmod +x /etc/init.d/${REDIS_ID_NAME}

    chkconfig --add ${REDIS_ID_NAME}
    chkconfig --level 235 ${REDIS_ID_NAME} on

    service ${REDIS_ID_NAME} start
    [ $? -eq 0 ] || "${REDIS_TAR_NAME} cannot be started!"

    echo
    echo "${REDIS_TAR_NAME} is installed successfully."
    echo

    service ${REDIS_ID_NAME} stop
}

if [ ${ALL_REINSTALL} -eq 1 ] || [ ! -d ${REDIS_DIR} ]; then
    install_redis
else
    echo "${REDIS_TAR_NAME} has already installed."
    echo "Nothing to do."
    echo
fi
