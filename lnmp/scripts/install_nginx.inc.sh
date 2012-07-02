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
	echo
    echo "Installing ${NGINX_TAR_NAME} to ${NGINX_DIR} ..."
    echo

    [ ${NGINX_USER} == 'root' ] && exit_with_error "'root' cannot be used as the nginx user!"

    #check nginx running status
    echo
    echo "Checking running ${NGINX_ID_NAME} service ..."
    echo
    ensure_service_stopped ${NGINX_ID_NAME}

    #check and backup if necessary
    if [ ${NO_BACKUP} -eq 1 ]
    then
        [ -d ${NGINX_DIR} ] && rm -rf ${NGINX_DIR}/*
    else
        ensure_backup ${NGINX_DIR} ${BACKUP_DIR} ${NO_PROMPT}
    fi

    #create user and group
    create_nologin_user ${NGINX_USER} ${NGINX_GROUP}

    #check whether the package exists
    if [ ${AUTO_DOWNLOAD} -eq 1 ]
    then
    	prepare_package ${PACKAGE_DIR} ${NGINX_TAR_NAME} ${PACKAGE_SOURCE_URL}
    else
    	prepare_package ${PACKAGE_DIR} ${NGINX_TAR_NAME}
    fi

    for FOLDER in client proxy fastcgi uwsgi sgi
    do
		if [ ! -d ${NGINX_TMP_DIR}/${FOLDER} ]
		then
			mkdir -p ${NGINX_TMP_DIR}/${FOLDER}
			chown -R ${NGINX_USER}:${NGINX_GROUP} ${NGINX_TMP_DIR}/${FOLDER}
		fi
    done

    sed -i 's/CFLAGS="$CFLAGS -g"/#CFLAGS="$CFLAGS -g"/' ./auto/cc/gcc

    echo
    echo "Configuring make environment ..."
    echo
    ./configure --prefix=${NGINX_DIR} \
	--user=${NGINX_USER} \
	--group=${NGINX_GROUP} \
	${NGINX_CONFIGURE_FLAG} \
	--pid-path=${NGINX_LOG_DIR}/nginx.pid \
	--error-log-path=${NGINX_LOG_DIR}/error.log \
	--http-log-path=${NGINX_LOG_DIR}/access.log \
	--http-client-body-temp-path=${NGINX_TMP_DIR}/client/ \
	--http-proxy-temp-path=${NGINX_TMP_DIR}/proxy/ \
	--http-fastcgi-temp-path=${NGINX_TMP_DIR}/fastcgi \
	--http-uwsgi-temp-path=${NGINX_TMP_DIR}/uwcgi \
	--http-scgi-temp-path=${NGINX_TMP_DIR}/scgi
    [ ! $? -eq 0 ] && exit_with_error "Missing dependencies for ${NGINX_TAR_NAME}!"

    echo
    echo "Building ${NGINX_TAR_NAME} ..."
    echo
    make -s && make -s install
    [ ! $? -eq 0 ] && exit_with_error "Building ${NGINX_TAR_NAME} failed!"

	echo
	echo "Creating web root directory: ${NGINX_WEB_ROOT}"
    [ -d ${NGINX_WEB_ROOT} ] || mkdir -p ${NGINX_WEB_ROOT}

    echo
    echo "Updating nginx.conf ..."
    echo
    /bin/cp -f ${CONFIG_DIR}/nginx.conf ${NGINX_DIR}/conf/nginx.conf
    sed -i "s:__NGINX_WEB_ROOT__:"${NGINX_WEB_ROOT}":g" ${NGINX_DIR}/conf/nginx.conf
	sed -i "s:__NGINX_USER__:"${NGINX_USER}":g" ${NGINX_DIR}/conf/nginx.conf
	sed -i "s:__NGINX_GROUP__:"${NGINX_GROUP}":g" ${NGINX_DIR}/conf/nginx.conf
	sed -i "s:__NGINX_LOG_DIR__:"${NGINX_LOG_DIR}":g" ${NGINX_DIR}/conf/nginx.conf

	/bin/cp -f ${CONFIG_DIR}/fastcgi.conf ${NGINX_DIR}/conf/fastcgi.conf

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

	service nginx stop
}

if [ ${ALL_REINSTALL} -eq 1 ] || [ ! -d ${NGINX_DIR} ]
then
    install_nginx
else
    echo "${NGINX_TAR_NAME} has already installed."
    echo "Nothing to do."
    echo
fi
