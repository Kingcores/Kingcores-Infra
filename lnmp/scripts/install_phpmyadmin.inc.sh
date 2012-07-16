#install phpmyadmin

PHPMYADMIN_DIR=${NGINX_WEB_ROOT}/${PHPMYADMIN_DIR_NAME}
    
function install_phpmyadmin() 
{    
    [ -d ${NGINX_WEB_ROOT} ] || exit_with_error "'web root' directory must be created first before installing ${PHPMYADMIN_TAR_NAME}!"  
        
    [ -d ${PHPMYADMIN_DIR} ] && rm -rf ${PHPMYADMIN_DIR}
    
    echo
    echo "Preparing package ${PHPMYADMIN_TAR_NAME} ..."
    echo
    download_untar ${PACKAGE_DIR} ${PHPMYADMIN_TAR_NAME} ${DOWNLOAD_BASE_URL}
    rm -rf ${PACKAGE_DIR}/${PHPMYADMIN_TAR_NAME}/examples
    rm -rf ${PACKAGE_DIR}/${PHPMYADMIN_TAR_NAME}/setup
    mv -f ${PACKAGE_DIR}/${PHPMYADMIN_TAR_NAME} ${PHPMYADMIN_DIR}
        
    echo
    echo "Updating php.ini ..."
    echo
    /bin/cp -f ${CONFIG_DIR}/config.inc.php ${PHPMYADMIN_DIR}/config.inc.php
    sed -i "s:__MYSQL_HOST__:${PHPMYADMIN_DB_HOST}:g" ${PHPMYADMIN_DIR}/config.inc.php
    sed -i "s:__MYSQL_PORT__:${PHPMYADMIN_DB_PORT}:g" ${PHPMYADMIN_DIR}/config.inc.php
    sed -i "s:__MYSQL_SOCK__:${PHPMYADMIN_DB_SOCK}:g" ${PHPMYADMIN_DIR}/config.inc.php    
    
    echo
    echo "${PHPMYADMIN_TAR_NAME} is installed successfully."
    echo    
}
  
if [ ${ALL_REINSTALL} -eq 1 ] || [ ! -d ${PHPMYADMIN_DIR} ]; then    
    install_phpmyadmin
else
    echo "${PHPMYADMIN_TAR_NAME} has already installed."
    echo "Nothing to do."
    echo
fi
    
