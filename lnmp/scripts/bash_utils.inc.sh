# exit_with_error <error message>
function exit_with_error()
{
    echo $1
    echo
    exit 1
}

# create a nologin user
# create_nologin_user <user name> <group name>
function create_nologin_user()
{
    if id "$1" >& /dev/null;
    then
		echo "User '$1' already exists"
		/usr/sbin/usermod -g "$2" "$1"
    else
		if [ "$#" -lt 2 ]; then
            echo "Adding user "$1" ..."
            /usr/sbin/useradd -M -s /sbin/nologin "$1"
        elif [ "$#" -eq 2 ]; then
            echo "Adding user "$1" to group "$2" ..."
            create_group_if_not_exist "$2"
            /usr/sbin/useradd -M -s /sbin/nologin "$1" -g "$2"
        fi

        [ "$?" -eq 0 ] || exit_with_error "Adding user "$1" failed!"
        echo "User "$1" is added."
	fi
}

# create_group_if_not_exist <group name>
function create_group_if_not_exist()
{
    uc=`grep $1: /etc/group | wc -l`
    [ "$uc" -gt "0" ] || /usr/sbin/groupadd $1
}

# require_dep <dependency>
function require_dep()
{
    uc=`yum list installed|grep "^$1"|wc -l`
    [ "$uc" -gt "0" ] || yum -y install $1
}

# ensure_backup <source_dir> <backup_dir> [<no_prompt: 0|1>]
function ensure_backup()
{
    if [ -d $1 ]
    then
        echo "$1 already exists. The script will move all old data into $2."
        echo

        if [ $3 -eq 0 ]
        then
            read -p "Continue?[y|n]:" ANSWER
            [ "${ANSWER}" != "y" ] && exit_with_error "Cancelled by user."
        fi

        td="$2/$(date "+%Y%m%d_%H%M%S")"

        [ ! -e ${td} ] && mkdir -p ${td}
        mv -f $1 ${td}
        [ $? -eq 0 ] || exit_with_error "Backup $1 to ${td} failed!"

        echo "$1 is backuped to ${td}."
	echo
    fi
}

# check whether the package exists and extract the package
# if not exist, wget from download_url
# ensure_package <package_dir> <package_name> [<download_url: url>]
function prepare_package()
{
    if [ $# -gt 2 ]
    then
        [ -f $1/$2.tar.gz ] || wget $3/$2.tar.gz
    fi

    [ -f $1/$2.tar.gz ] || exit_with_error "$2.tar.gz not found!"

    [ -d $1/$2 ] && rm -rf ./$2
    echo
    echo "Extracting $2 package ..."
    echo
    tar xf $1/$2.tar.gz -C $1
    cd $1/$2
}

# ensure_service_stopped <service_name>
function ensure_service_stopped()
{
    wc=`ps u -C $1|wc -l`

    if [ $wc -gt 1 ]
    then
        if [ -f /etc/init.d/$1 ]
        then
            /etc/init.d/$1 stop
        else
            killall $1
        fi
    fi

	wc=`ps u -C $1|wc -l`

    if [ $wc -gt 1 ]
    then
        exit_with_error "Failed to stop service [$1]!"
    fi
}

# ensure_lib <package_dir> <package_name>
function ensure_lib()
{
  echo "Installing $2......"

  ensure_package $1 $2

  /sbin/ldconfig
  ./configure --prefix=$BASE_DIR
  make && make install
}

#add custom lib path to ENVRIONMENT variable LD_LIBRARY_PATH
function add_custom_lib_path()
{
	if ! grep "$1" /etc/ld.so.conf >& /dev/null; then
		echo "$1" >> /etc/ld.so.conf
		ldconfig
	fi
}

#add custom bin path to ENVIRONMENT variable PATH
function add_custom_bin_path()
{
	if ! grep "$1" /etc/profile >& /dev/null; then
		echo PATH="$1"':$PATH' >> /etc/profile
		source /etc/profile
	fi
}
