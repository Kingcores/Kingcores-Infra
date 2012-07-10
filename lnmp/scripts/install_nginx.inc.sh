# install_nginx

if [ ${USE_TAR_BASENAME} -eq 1 ]
then
    NGINX_ID_NAME=${NGINX_TAR_NAME}
else
    NGINX_ID_NAME=nginx
fi

NGINX_DIR=${BASE_DIR}/${NGINX_ID_NAME}
NGINX_TMP_DIR=${TMP_BASE_DIR}/${NGINX_ID_NAME}
NGINX_LOG_DIR=${LOG_BASE_DIR}/${NGINX_ID_NAME}

function install_nginx()
{    
    prepare_package ${NGINX_ID_NAME} ${PACKAGE_DIR} ${NGINX_TAR_NAME} ${NGINX_DIR} ${DOWNLOAD_BASE_URL} \
        ${BACKUP_DIR_FLAG} ${NO_PROMPT} ${NGINX_USER} ${NGINX_GROUP}

    echo
    echo Update make script to production env ...
    echo
    sed -i 's/CFLAGS="$CFLAGS -g"/#CFLAGS="$CFLAGS -g"/' ${PACKAGE_DIR}/${NGINX_TAR_NAME}/auto/cc/gcc

    echo
    echo "Create runtime folders ..."
    echo
    for FOLDER in client proxy fastcgi uwsgi scgi
    do
        if [ ! -d ${NGINX_TMP_DIR}/${FOLDER} ]
        then
            mkdir -p ${NGINX_TMP_DIR}/${FOLDER}
            chown -R ${NGINX_USER}:${NGINX_GROUP} ${NGINX_TMP_DIR}/${FOLDER}
        fi
    done
    
    if [ ! -d ${NGINX_WEB_ROOT} ]
    then
        mkdir -p ${NGINX_WEB_ROOT}
        chown -R ${NGINX_USER}:${NGINX_GROUP} ${NGINX_WEB_ROOT}
    fi    
    
    install_package ${PACKAGE_DIR}/${NGINX_TAR_NAME} ${NGINX_DIR} ${NGINX_TAR_NAME} \
        --user=${NGINX_USER} --group=${NGINX_GROUP} \
        --with-http_flv_module --with-http_ssl_module --with-http_stub_status_module --with-http_gzip_static_module \
        --pid-path=${NGINX_LOG_DIR}/nginx.pid \
        --error-log-path=${NGINX_LOG_DIR}/error.log \
        --http-log-path=${NGINX_LOG_DIR}/access.log \
        --http-client-body-temp-path=${NGINX_TMP_DIR}/client \
        --http-proxy-temp-path=${NGINX_TMP_DIR}/proxy \
        --http-fastcgi-temp-path=${NGINX_TMP_DIR}/fastcgi \
        --http-uwsgi-temp-path=${NGINX_TMP_DIR}/uwsgi \
        --http-scgi-temp-path=${NGINX_TMP_DIR}/scgi    

    echo
    echo "Updating nginx.conf ..."
    echo
    /bin/cp -f ${CONFIG_DIR}/nginx.conf ${NGINX_DIR}/conf/nginx.conf
    sed -i "s:__NGINX_WEB_ROOT__:"${NGINX_WEB_ROOT}":g" ${NGINX_DIR}/conf/nginx.conf
    sed -i "s:__NGINX_USER__:"${NGINX_USER}":g" ${NGINX_DIR}/conf/nginx.conf
    sed -i "s:__NGINX_GROUP__:"${NGINX_GROUP}":g" ${NGINX_DIR}/conf/nginx.conf
    sed -i "s:__NGINX_LOG_DIR__:"${NGINX_LOG_DIR}":g" ${NGINX_DIR}/conf/nginx.conf

    /bin/cp -f ${CONFIG_DIR}/fastcgi.conf ${NGINX_DIR}/conf/fastcgi.conf

    echo
    echo "Setting up ${NGINX_ID_NAME} service ..."
    echo
    /bin/cp -f ${INITD_DIR}/${NGINX_ID_NAME} /etc/init.d/${NGINX_ID_NAME}
    sed -i "s:__NGINX_DIR__:"${NGINX_DIR}":g" /etc/init.d/${NGINX_ID_NAME}
    sed -i "s:__NGINX_LOG_DIR__:"${NGINX_LOG_DIR}":g" /etc/init.d/${NGINX_ID_NAME}
    chmod +x /etc/init.d/${NGINX_ID_NAME}

    chkconfig --add ${NGINX_ID_NAME}
    chkconfig --level 235 ${NGINX_ID_NAME} on

    service ${NGINX_ID_NAME} start
    if [ $? -eq 0 ]; then
        echo "${NGINX_TAR_NAME} is installed successfully."
        echo
    else
        exit_with_error "${NGINX_TAR_NAME} cannot be started!"
    fi

    service ${NGINX_ID_NAME} stop
}

if [ ${ALL_REINSTALL} -eq 1 ] || [ ! -d ${NGINX_DIR} ]
then
    install_nginx
else
    echo "${NGINX_TAR_NAME} has already installed."
    echo "Nothing to do."
    echo
fi
