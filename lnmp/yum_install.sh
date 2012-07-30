#!/bin/bash

yum -y install \
    yum-utils \
    make autoconf gcc gcc-c++ \
    libjpeg libjpeg-devel libpng libpng-devel libpng10 libpng10-devel \
    freetype freetype-devel \
    libxml2 libxml2-devel \
    zlib zlib-devel glibc glibc-devel glib2 glib2-devel bzip2 bzip2-devel \
    fonts-chinese gettext gettext-devel \
    ncurses ncurses-devel \
    curl curl-devel \
    e2fsprogs e2fsprogs-devel \
    krb5 krb5-devel \
    libidn libidn-devel \
    openssl openssl-devel \
    openldap openldap-devel openldap-clients \
    pcre pcre-devel \
    gd gd-devel \
    libevent libevent-devel \
    libpcap libpcap-devel \
    wget \
    rsync \
    libuuid libuuid-devel
