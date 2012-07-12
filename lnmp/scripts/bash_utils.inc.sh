# exit_with_error <1:error_message>
function exit_with_error()
{
    echo $1
    echo
    exit 1
}

# create_service_user_if_not_exist <1:user> <2:group>
function create_service_user_if_not_exist()
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

# create_group_if_not_exist <1:group_name>
function create_group_if_not_exist()
{
    uc=`grep $1: /etc/group | wc -l`
    [ "$uc" -gt "0" ] || /usr/sbin/groupadd $1
}

# require_yum_package <1:yum_package_name>
function require_yum_package()
{
    uc=`yum list installed|grep "^$1"|wc -l`
    [ "$uc" -gt "0" ] || yum -y install $1
}

# backup <1:source_dir> <2:backup_dir> [<3:no_prompt: 0|1>]
function backup()
{
    if [ -d $1 ]
    then
        echo
        echo "$1 already exists. The script will backup $1 into $2."
        echo

        if [ $3 -eq 0 ]
        then
            read -p "Continue?[y|n]:" ANSWER
            [ "${ANSWER}" != "y" ] && exit_with_error "Cancelled by user."
        fi        

        [ ! -e $2 ] && mkdir -p $2
        mv -f $1 $2
        [ $? -eq 0 ] || exit_with_error "Backup $1 to $2 failed!"

        echo
        echo "$1 is backuped to $2."
        echo
	echo
    fi
}

# stop_service <1:service_name>
function stop_service()
{
    wc=`ps u -C $1|wc -l`

    if [ $wc -gt 1 ]
    then
		echo
		echo "Stopping service $1 ..."
		echo		
	
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

# prepare_package <1:package_id: lib|service_name> <2:package_dir> <3:package_name> <4:target_dir> 
# <5:download_url> <6:backup: dir|no_backup> <7:prompt_user: 0|1> [<8:user>] [<9:group>]
function prepare_package()
{
	echo
    echo "Installing $3 to $4 ..."
    echo

	if [ ! $1 == 'lib' ]
	then
	
		#check $1 running status
		echo
		echo "Checking running $1 service ..."
		echo
		stop_service $1
		
		#check and backup if necessary
		if [ $6 == "no_backup" ]
		then
			[ -d $4 ] && rm -rf $4/*
		else
			backup $4 $6 $7
		fi
		
		if [ $# -gt 7 ] 
		then
			[ $8 == 'root' ] && exit_with_error "'root' cannot be used as the $1 user!"
			
			#create user and group
			create_service_user_if_not_exist $8 $9
		fi			
		
	fi

	echo
	echo "Preparing package $3 ..."
	echo
    [ $5 != "no_auto_download" ] && [ ! -f $2/$3.tar.gz ] && wget $5/$3.tar.gz
    [ -f $2/$3.tar.gz ] || exit_with_error "$3.tar.gz not found!"
    [ -d $2/$3 ] && rm -rf $2/$3
	
    echo
    echo "Extracting $3 package ..."
    echo
    tar xf $2/$3.tar.gz -C $2    
}

# install_package: <1:source_dir> <2:target_dir> <3:package_name> [<4:make_flag:no_auto_make>] [<*:flag>]
function install_package()
{
	/sbin/ldconfig

    echo
    echo "Configuring $3 make environment ..."
    echo
	cd $1
	PREFIX=$2
    PACKAGE=$3
    AUTO_MAKE=1
    if [ $# -gt 3 ] && [ $4 == "no_auto_make" ]
    then
        AUTO_MAKE=0
        shift 4
    else   
        shift 3
    fi
	./configure --prefix=${PREFIX} $*
	[ ! $? -eq 0 ] && exit_with_error "Missing dependencies for ${PACKAGE} package!"
	
    if [ ${AUTO_MAKE} -eq 1 ]
    then
        echo
        echo "Building ${PACKAGE} package ..."
        echo
        make -s && make -s install
        [ ! $? -eq 0 ] && exit_with_error "Building ${PACKAGE} package failed!"
    fi
}

#add_custom_lib_path <1:lib_path>
function add_custom_lib_path()
{
	if ! grep "$1" /etc/ld.so.conf >& /dev/null; then
        echo
        echo "Adding '$1' to lib loading path ..."
        echo
		echo "$1" >> /etc/ld.so.conf		
	fi
}

#add_custom_bin_path <1:bin_path>
function add_custom_bin_path()
{
	if ! grep "$1" /etc/profile >& /dev/null; then
        echo
        echo "Adding '$1' to environment path ..."
        echo
		echo PATH="$1"':$PATH' >> /etc/profile		
	fi
}
