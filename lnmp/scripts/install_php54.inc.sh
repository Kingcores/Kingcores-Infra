#install php54
    
if [ ${USE_TAR_BASENAME} -eq 1 ]
then
    PHP54_ID_NAME=${PHP54_TAR_NAME}
else
    PHP54_ID_NAME=php
fi

PHP54_DIR=${BASE_DIR}/${PHP54_ID_NAME}
PHP54_TMP_DIR=${TMP_BASE_DIR}/${PHP54_ID_NAME}
PHP54_LOG_DIR=${LOG_BASE_DIR}/${PHP54_ID_NAME}
    
function install_php54() 
{
    #libiconv
    if [ ! -f ${BASE_DIR}/lib/libiconv.so ]
    then
        prepare_package lib ${PACKAGE_DIR} ${LIBICONV_TAR_NAME} ${BASE_DIR} ${DOWNLOAD_BASE_URL}    
        install_package ${PACKAGE_DIR}/${LIBICONV_TAR_NAME} ${BASE_DIR} ${LIBICONV_TAR_NAME}
    fi

    #libmcrypt
    if [ ! -f ${BASE_DIR}/lib/libmcrypt.so ]
    then
        prepare_package lib ${PACKAGE_DIR} ${LIBMCRYPT_TAR_NAME} ${BASE_DIR} ${DOWNLOAD_BASE_URL}    
        install_package ${PACKAGE_DIR}/${LIBMCRYPT_TAR_NAME} ${BASE_DIR} ${LIBMCRYPT_TAR_NAME} --disable-posix-threads
        if [ ! -f ${BASE_DIR}/lib/libltdl.a ]
        then
            install_package ${PACKAGE_DIR}/${LIBMCRYPT_TAR_NAME}/libltdl ${BASE_DIR} libltdl --enable-ltdl-install
        fi
    fi
    
    #mhash
    if [ ! -f ${BASE_DIR}/lib/libmhash.so ]
    then
        prepare_package lib ${PACKAGE_DIR} ${MHASH_TAR_NAME} ${BASE_DIR} ${DOWNLOAD_BASE_URL}    
        install_package ${PACKAGE_DIR}/${MHASH_TAR_NAME} ${BASE_DIR} ${MHASH_TAR_NAME}
    fi
    
    #mcrypt
    if [ ! -f ${BASE_DIR}/bin/mcrypt ]
    then
        prepare_package lib ${PACKAGE_DIR} ${MCRYPT_TAR_NAME} ${BASE_DIR} ${DOWNLOAD_BASE_URL}     
    
        add_custom_lib_path "${BASE_DIR}/lib"

        /sbin/ldconfig
        
        export LD_LIBRARY_PATH=${BASE_DIR}/lib:${LD_LIBRARY_PATH}
        export LDFLAGS="-L${BASE_DIR}/lib -I${BASE_DIR}/include"  
        export CFLAGS="-I${BASE_DIR}/include"
        
        echo
        echo "Configuring ${MCRYPT_TAR_NAME} make environment ..."
        echo
        cd ${PACKAGE_DIR}/${MCRYPT_TAR_NAME}
        ./configure --prefix=${BASE_DIR} --with-libmcrypt-prefix=${BASE_DIR}  
        [ ! $? -eq 0 ] && exit_with_error "Missing dependencies for ${MCRYPT_TAR_NAME}!"
        
        echo
        echo "Building ${MCRYPT_TAR_NAME} package ..."
        echo
        make -s && make -s install
        [ ! $? -eq 0 ] && exit_with_error "Building ${PACKAGE} package failed!"
    fi

	prepare_package ${PHP54_ID_NAME} ${PACKAGE_DIR} ${PHP54_TAR_NAME} ${PHP54_DIR} ${DOWNLOAD_BASE_URL} \
        ${BACKUP_DIR_FLAG} ${NO_PROMPT} ${PHP54_USER} ${PHP54_GROUP}
        
    if [ ${USE_TAR_BASENAME} -eq 1 ]
    then
        PHP_MYSQL_DIR=${BASE_DIR}/${MYSQL_TAR_NAME}
        if [ ! -d ${PHP_MYSQL_DIR} ]
        then
            PHP_MYSQL_DIR=${BASE_DIR}/mysql
        fi
    else
        PHP_MYSQL_DIR=${BASE_DIR}/mysql
        if [ ! -d ${PHP_MYSQL_DIR} ]
        then
            PHP_MYSQL_DIR=${BASE_DIR}/${MYSQL_TAR_NAME}
        fi
    fi
    
    [ -d ${PHP_MYSQL_DIR} ] || exit_with_error "'mysql' is needed for installing ${PHP54_TAR_NAME}!"
    
    install_package ${PACKAGE_DIR}/${PHP54_TAR_NAME} ${PHP54_DIR} ${PHP54_TAR_NAME} no_auto_make \
        --with-config-file-path=${PHP54_DIR}/etc \
        --with-iconv=${BASE_DIR} \
        --with-mhash=${BASE_DIR} \
        --with-mcrypt=${BASE_DIR} \
        --with-mysql=${PHP_MYSQL_DIR} \
        --with-pdo-mysql=${PHP_MYSQL_DIR} \
        --with-freetype-dir \
        --with-gd \
        --with-gettext \
        --with-jpeg-dir \
        --with-png-dir \
        --with-zlib \
        --with-libxml-dir \
        --with-curlwrappers \
        --with-openssl \
        --with-xmlrpc \
        --with-curl --with-curlwrappers \
        --enable-fpm \
        --enable-sockets \
        --enable-pcntl \
        --enable-gd-native-ttf \
        --enable-soap \
        --enable-pdo \
        --enable-inline-optimization \
        --enable-mbregex --enable-mbstring \
        --enable-zip \
        --enable-xml \
        --enable-bcmath --enable-shmop --enable-sysvsem \
        --disable-rpath
    
    echo
    echo "Building ${PHP54_TAR_NAME} package ..."
    echo
    make -s ZEND_EXTRA_FILE='-liconv' && make -s install
    [ ! $? -eq 0 ] && exit_with_error "Building ${PHP54_TAR_NAME} package failed!"
    
    /bin/cp -f ${CONFIG_DIR}/php.ini ${PHP54_DIR}/etc/php.ini
    sed -i "s:__PHP54_DIR__:${PHP54_DIR}:g" ${PHP54_DIR}/etc/php.ini
    
    /bin/cp -f ${CONFIG_DIR}/php-fpm.conf ${PHP54_DIR}/etc/php-fpm.conf
	[ ! -d ${PHP54_LOG_DIR} ] && mkdir -p ${PHP54_LOG_DIR}
	chown -R ${PHP_USER}:${PHP_USER} ${PHP54_LOG_DIR}
    sed -i "s:__PHP54_LOG_DIR__:${PHP54_LOG_DIR}:g" ${PHP54_DIR}/etc/php-fpm.conf
    sed -i "s:__PHP54_USER__:${NGINX_USER}:g" ${PHP54_DIR}/etc/php-fpm.conf
    sed -i "s:__PHP54_GROUP__:${NGINX_GROUP}:g" ${PHP54_DIR}/etc/php-fpm.conf
    
    /bin/cp -f ${INITD_DIR}/php-fpm /etc/init.d/${PHP54_ID_NAME}
    sed -i "s:__PHP54_DIR__:${PHP54_DIR}:g" /etc/init.d/${PHP54_ID_NAME}
    sed -i "s:__PHP54_LOG_DIR__:${PHP54_LOG_DIR}:g" /etc/init.d/${PHP54_ID_NAME}
    chmod +x /etc/init.d/${PHP54_ID_NAME}
    
    chkconfig --add ${PHP54_ID_NAME}
    chkconfig --level 235 ${PHP54_ID_NAME} on

	#add php bin directory to ENVIRONMENT variable PATH
	add_custom_bin_path ${PHP54_DIR}/sbin
	add_custom_bin_path ${PHP54_DIR}/bin
    
    service ${PHP54_ID_NAME} start
    if [ $? -eq 0 ]; then 
    	echo "${PHP54_TAR_NAME} is installed successfully."
    	echo        
    else
    	exit_with_error "${PHP54_TAR_NAME} cannot be started!"
    fi
    service ${PHP54_ID_NAME} stop
    
    # prepare_package lib ${PACKAGE_DIR} ${EACCELERATOR_TAR_NAME} ${BASE_DIR} ${DOWNLOAD_BASE_URL} 
    
    # echo
    # echo "Building ${EACCELERATOR_TAR_NAME} package ..."
    # echo
    # cd ${PACKAGE_DIR}/${EACCELERATOR_TAR_NAME}
    # ${PHP54_DIR}/bin/phpize
    # ./configure --with-php-config=${PHP54_DIR}/bin/php-config --enable-eaccelerator=shared
    # [ ! $? -eq 0 ] && exit_with_error "Missing dependencies for ${EACCELERATOR_TAR_NAME}!"
    
    # echo
    # echo "Building ${EACCELERATOR_TAR_NAME} package ..."
    # echo
    # make -s && make -s install
    # [ ! $? -eq 0 ] && exit_with_error "Building ${EACCELERATOR_TAR_NAME} failed!"
    
	# sed -i '/eaccelerator\.so/{s/^;//}' ${PHP54_DIR}/etc/php.ini
    
    for((j=0;j<=10;j++))
	do
		if [ -f ${BASE_DIR}/php/lib/php/extensions/no-debug-non-zts-20100525/uuid.so ]; then
			sed -i '/uuid\.so/{s/^;//}' ${PHP54_DIR}/etc/php.ini
			break
		else
    		echo -e '\n'|pecl install uuid
		fi
	done
}
  
if [ ${ALL_REINSTALL} -eq 1 ] || [ ! -d ${PHP54_DIR} ]; then    
    install_php54
else
	echo "${PHP54_TAR_NAME} has already installed."
    echo "Nothing to do."
    echo
fi
    
