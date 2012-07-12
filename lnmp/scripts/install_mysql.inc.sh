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
    #backup my.cnf
    if [ ${NO_BACKUP} -eq 0 ] && [ -f /etc/my.cnf ]
    then		
		backup /etc/my.cnf ${BACKUP_DIR}/etc ${NO_PROMPT}
	fi

    prepare_package ${MYSQL_ID_NAME} ${PACKAGE_DIR} ${MYSQL_TAR_NAME} ${MYSQL_DIR} ${DOWNLOAD_BASE_URL} \
        ${BACKUP_DIR_FLAG} ${NO_PROMPT} ${MYSQL_USER} ${MYSQL_GROUP}    
    
    install_package ${PACKAGE_DIR}/${MYSQL_TAR_NAME} ${MYSQL_DIR} ${MYSQL_TAR_NAME} \
        --enable-assembler \
        --with-charset=utf8 \
        --with-extra-charsets=complex \
        --enable-thread-safe-client \
        --with-big-tables \
        --with-mysqld-ldflags=-all-static \
        --with-readline \
        --with-ssl \
        --with-embedded-server \
        --enable-local-infile \
        --with-plugins=partition,innobase,myisammrg \
        --without-ndb-debug   			

	#install mysql database
	if [ ${NO_BACKUP} -eq 1 ]; then
		[ -d ${MYSQL_DATA_DIR} ] && rm -rf ${MYSQL_DATA_DIR}
	else
		backup ${MYSQL_DATA_DIR} ${BACKUP_DIR}/data ${NO_PROMPT}
	fi

	[ -d ${MYSQL_DATA_DIR} ] || mkdir -p ${MYSQL_DATA_DIR}
	chown -R ${MYSQL_USER}:${MYSQL_GROUP} ${MYSQL_DATA_DIR}

	[ -d ${MYSQL_LOG_DIR} ] || mkdir -p ${MYSQL_LOG_DIR}
	chown -R ${MYSQL_USER}:${MYSQL_GROUP} ${MYSQL_LOG_DIR}

	echo
	echo "Updating my.cnf ..."
	echo
	/bin/cp -f ${CONFIG_DIR}/my-${MYSQL_MEM}.cnf /etc/my.cnf
	sed -i "s:__MYSQL_DIR__:${MYSQL_DIR}:g" /etc/my.cnf
	sed -i "s:__MYSQL_DATA_DIR__:${MYSQL_DATA_DIR}:g" /etc/my.cnf
	sed -i "s:__MYSQL_LOG_DIR__:${MYSQL_LOG_DIR}:g" /etc/my.cnf
	sed -i "s:__MYSQL_TMP_DIR__:${MYSQL_TMP_DIR}:g" /etc/my.cnf
	sed -i "s:__MYSQL_PORT__:${MYSQL_PORT}:g" /etc/my.cnf
	sed -i "s:__MYSQL_USER__:${MYSQL_USER}:g" /etc/my.cnf

    echo
    echo "Installing mysql db ..."
    echo
	${MYSQL_DIR}/bin/mysql_install_db \
	--basedir=${MYSQL_DIR} \
	--datadir=${MYSQL_DATA_DIR}

	chown -R ${MYSQL_USER}:${MYSQL_GROUP} ${MYSQL_DIR}

    echo
    echo "Setting up ${MYSQL_ID_NAME} service ..."
    echo
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
    [ $? -eq 0 ] || "${MYSQL_TAR_NAME} cannot be started!"
    
	echo
    echo "${MYSQL_TAR_NAME} is installed successfully."
	echo

	${MYSQL_DIR}/bin/mysqladmin -u root password "${MYSQL_PASSWORD}"	
    [ $? -eq 0 ] || "Failed to set mysql root password!"
    
    cat > /tmp/mysql_secure_script<<EOF
use mysql;
update user set password=password('$mysqlrootpwd') where user='root';
delete from user where not (user='root') ;
delete from user where user='root' and password=''; 
drop database test;
DROP USER ''@'%';
flush privileges;
EOF

    ${MYSQL_DIR}/bin/mysql -u root -p${MYSQL_PASSWORD} -h localhost < /tmp/mysql_secure_script
    [ $? -eq 0 ] || "Failed to secure mysql root account!"

    rm -f /tmp/mysql_secure_script

	service ${MYSQL_ID_NAME} stop

    [ -f /etc/ld.so.conf.d/mysql-x86_64.conf ] && rm -f /etc/ld.so.conf.d/mysql-x86_64.conf

	#add mysql lib path to variable LD_LIBRARY_PATH
	add_custom_lib_path "${MYSQL_DIR}/lib/mysql"
    /sbin/ldconfig

	#add mysql bin path to ENVIRONMENT varivle PATH
	add_custom_bin_path "${MYSQL_DIR}/bin"
    source /etc/profile
}

if [ ${ALL_REINSTALL} -eq 1 ] || [ ! -d ${MYSQL_DIR} ]; then
	install_mysql
else
	echo "${MYSQL_TAR_NAME} has already installed."
	echo "Nothing to do."
	echo
fi
