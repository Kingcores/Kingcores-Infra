#install_mysql

if [ ${USE_TAR_BASENAME} -eq 1 ]
then
    MYSQL_ID_NAME=${MYSQL_TAR_NAME}
else
    MYSQL_ID_NAME=mysql
fi

MYSQL_DIR=${BASE_DIR}/${MYSQL_ID_NAME}
MYSQL_TMP_DIR=${TMP_BASE_DIR}/${MYSQL_ID_NAME}
MYSQL_LOG_DIR=${LOG_BASE_DIR}/${MYSQL_ID_NAME}
MYSQL_DATA_DIR=${DATA_BASE_DIR}/${MYSQL_ID_NAME}

function install_mysql()
{
    echo
	echo "Installing ${MYSQL_TAR_NAME} to ${MYSQL_DIR} ......"
	echo

	[ ${MYSQL_USER} == 'root' ] && exit_with_error "'root' cannot be used as the mysql user!"

	#check mysql running status
	echo
    echo "Checking running ${MYSQL_ID_NAME} service ..."
    echo
	ensure_service_stopped ${MYSQL_ID_NAME}

	#check and backup if necessary
	if [ ${NO_BACKUP} -eq 1 ]
	then
		[ -d ${MYSQL_DIR} ] && rm -rf ${MYSQL_DIR}/*
	else
		ensure_backup ${MYSQL_DIR} ${BACKUP_DIR} ${NO_PROMPT}
	fi

	#create user and group
	create_nologin_user ${MYSQL_USER} ${MYSQL_GROUP}

	#check whether the package exists
    if [ ${AUTO_DOWNLOAD} -eq 1 ]
    then
    	prepare_package ${PACKAGE_DIR} ${MYSQL_TAR_NAME} ${PACKAGE_SOURCE_URL}
    else
    	prepare_package ${PACKAGE_DIR} ${MYSQL_TAR_NAME}
    fi

	echo
	echo "Checking make environment ..."
	echo
	./configure --prefix=${MYSQL_DIR} \
	--enable-assembler \
	--with-charset=utf8 \
	--with-extra-charsets=none \
	--enable-thread-safe-client \
	--with-big-tables \
	--with-client-ldflags=-all-static \
	--with-mysqld-ldflags=-all-static \
	--with-readline \
	--with-ssl \
	--with-embedded-server \
	--enable-local-infile \
	--with-plugins=partition,innobase,myisammrg \
	--without-ndb-debug
	[ ! $? -eq 0 ] && exit_with_error "Missing dependencies for ${MYSQL_TAR_NAME}!"

	echo
	echo "Building ${MYSQL_TAR_NAME} ..."
	echo
	make -s && make -s install
	[ ! $? -eq 0 ] && exit_with_error "Building ${MYSQL_TAR_NAME} failed!"

	#install mysql database
	if [ ${NO_BACKUP} -eq 1 ]; then
		[ -d ${MYSQL_DATA_DIR} ] && rm -rf ${MYSQL_DATA_DIR}
	else
		ensure_backup ${MYSQL_DATA_DIR} ${BACKUP_DIR} ${NO_PROMPT}
	fi

	[ -d ${MYSQL_DATA_DIR} ] || mkdir -p ${MYSQL_DATA_DIR}
	chown -R ${MYSQL_USER}:${MYSQL_GROUP} ${MYSQL_DATA_DIR}

	[ ! -d ${MYSQL_LOG_DIR} ] && mkdir -p ${MYSQL_LOG_DIR}
	chown -R ${MYSQL_USER}:${MYSQL_GROUP} ${MYSQL_LOG_DIR}

	echo
	echo "Updating my.cnf ..."
	echo
	/bin/cp -f ${CONFIG_DIR}/my.cnf ${MYSQL_DATA_DIR}/my.cnf
	sed -i "s:__MYSQL_DIR__:${MYSQL_DIR}:g" ${MYSQL_DATA_DIR}/my.cnf
	sed -i "s:__MYSQL_DATA_DIR__:${MYSQL_DATA_DIR}:g" ${MYSQL_DATA_DIR}/my.cnf
	sed -i "s:__MYSQL_LOG_DIR__:${MYSQL_LOG_DIR}:g" ${MYSQL_DATA_DIR}/my.cnf
	sed -i "s:__MYSQL_TMP_DIR__:${MYSQL_TMP_DIR}:g" ${MYSQL_DATA_DIR}/my.cnf
	sed -i "s:__MYSQL_PORT__:${MYSQL_PORT}:g" ${MYSQL_DATA_DIR}/my.cnf
	sed -i "s:__MYSQL_USER__:${MYSQL_USER}:g" ${MYSQL_DATA_DIR}/my.cnf

	${MYSQL_DIR}/bin/mysql_install_db \
	--user=${MYSQL_USER} \
	--defaults-file=${MYSQL_DATA_DIR}/my.cnf \
	--basedir=${MYSQL_DIR} \
	--datadir=${MYSQL_DATA_DIR}

	chown -R ${MYSQL_USER}:${MYSQL_GROUP} ${MYSQL_DIR}

	/bin/cp -f ${INITD_DIR}/mysql /etc/init.d/${MYSQL_ID_NAME}
	sed -i "s:__MYSQL_DIR__:${MYSQL_DIR}:g" /etc/init.d/${MYSQL_ID_NAME}
	sed -i "s:__MYSQL_LOG_DIR__:${MYSQL_LOG_DIR}:g" /etc/init.d/${MYSQL_ID_NAME}
	sed -i "s:__MYSQL_DATA_DIR__:${MYSQL_DATA_DIR}:g" /etc/init.d/${MYSQL_ID_NAME}
	sed -i "s:__MYSQL_TMP_DIR__:${MYSQL_TMP_DIR}:g" /etc/init.d/${MYSQL_ID_NAME}
	sed -i "s:__MYSQL_USER__:${MYSQL_USER}:g" /etc/init.d/${MYSQL_ID_NAME}
	sed -i "s:__MYSQL_GROUP__:${MYSQL_GROUP}:g" /etc/init.d/${MYSQL_ID_NAME}
	chmod +x /etc/init.d/${MYSQL_ID_NAME}

	chkconfig --add ${MYSQL_ID_NAME}
	chkconfig --level 235 ${MYSQL_ID_NAME} on

	service ${MYSQL_ID_NAME} start
	if [ $? -eq 0 ]; then
		echo "${MYSQL_TAR_NAME} is installed successfully."
		echo

		${MYSQL_DIR}/bin/mysqladmin -u root password "${MYSQL_PASSWORD}"
	else
		exit_with_error "${MYSQL_TAR_NAME} cannot be started!"
	fi
	service ${MYSQL_ID_NAME} stop

	#rename old mysql lib files
	if [ $(uname -m) == "x86_64" ]; then
		[ -d /usr/lib64/mysql ] && mv /usr/lib64/mysql /usr/lib64/mysql_bak
	elif [ $(uname -m) == "x86" ]; then
		[ -d /usr/lib/mysql ] && mv /usr/lib/mysql /usr/lib/mysql_bak
	fi

	#add mysql lib path to variable LD_LIBRARY_PATH
	add_custom_lib_path "${MYSQL_DIR}/lib/mysql"

	#add mysql bin path to ENVIRONMENT varivle PATH
	add_custom_bin_path "${MYSQL_DIR}/bin"
}

if [ ${ALL_REINSTALL} -eq 1 ] || [ ! -d ${MYSQL_DIR} ]; then
	install_mysql
else
	echo "${MYSQL_TAR_NAME} has already installed."
	echo "Nothing to do."
	echo
fi
