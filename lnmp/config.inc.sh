#Installation configuration

#components to install
#nginx nginx_google_perf
#mysql_lib_only mysql
#redis
#php52 php53 php54
export COMPONENTS="nginx mysql php54"

#define the install path, backup path, data path, web root path and etc.
export BASE_DIR=/kingcores/local
export LIB_DIR=${BASE_DIR}
export BACKUP_DIR=/kingcores/backup
export DATA_BASE_DIR=/kingcores/data
export LOG_BASE_DIR=/kingcores/log
export TMP_BASE_DIR=/kingcores/tmp

#define url of package source
export PACKAGE_SOURCE_URL=http://

#define nginx configuration
export NGINX_USER=www
export NGINX_GROUP=www
export NGINX_WEB_ROOT=/kingcores/www

#define php configuration
export PHP_USER=www
export PHP_GROUP=www

#define the user and group  for starting mysqld deamon
export MYSQL_USER=mysql
export MYSQL_GROUP=mysql
export MYSQL_PORT=3306
export MYSQL_PASSWORD=root

#define the user and group  for staring  redis deamon
export REDIS_USER=redis
export REDIS_GROUP=redis

#define passwords
export MYSQL_ROOT_PASSWD=root@mysql
export REDIS_SECRET_CODE=secret@redis

#define names of the packages which will be included in this installation
export NGINX_TAR_NAME=nginx-1.2.1
export MYSQL_TAR_NAME=mysql-5.5.2-m2
export LIBICONV_TAR_NAME=libiconv-1.14
export LIBMCRYPT_TAR_NAME=libmcrypt-2.5.8
export MHASH_TAR_NAME=mhash-0.9.9.9
export MCRYPT_TAR_NAME=mcrypt-2.6.8
export PHP54_TAR_NAME=php-5.4.4
export EACCELERATOR_TAR_NAME=eaccelerator-0.9.6.1
export IMAGEMAGICK_TAR_NAME=ImageMagick-6.7.0-10
export IMAGICK_TAR_NAME=imagick-3.1.0b1
export REDIS_TAR_NAME=redis-2.2.11
