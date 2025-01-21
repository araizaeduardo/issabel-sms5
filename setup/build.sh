#!/bin/bash

# Función para manejar errores
error_exit() {
    echo "Error: $1" >&2
    exit 1
}

# Validar que el script se ejecute con permisos de root
if [[ $EUID -ne 0 ]]; then
   error_exit "Este script debe ejecutarse como root usando sudo"
fi

# Verificar dependencias
REQUIRED_COMMANDS=("rpmbuild" "rpmdevtools" "tar" "git")
for cmd in "${REQUIRED_COMMANDS[@]}"; do
    if ! command -v "$cmd" &> /dev/null; then
        error_exit "El comando $cmd no está instalado. Por favor instálalo con: dnf install -y rpm-build rpmdevtools"
    fi
done

# Variables
VERSION=$(date +"%Y.%m.%d")
RELEASE=1
NAME=issabel-sms
ARCH=noarch
BUILDROOTDIR=~/rpmbuild
MODULE_DIR=/var/www/html/modules/sms

# Limpiar directorios de construcción previos
rm -rf ${BUILDROOTDIR}

# Crear estructura de directorios para RPM
mkdir -p ${BUILDROOTDIR}/{SPECS,SOURCES,BUILD,SRPMS,RPMS}

# Crear el archivo spec
cat > ${BUILDROOTDIR}/SPECS/${NAME}.spec << EOF
Summary: Issabel SMS Module with Telnyx Integration
Name: ${NAME}
Version: ${VERSION}
Release: ${RELEASE}
License: GPL-3.0
Group: Applications/Communications
Source0: %{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: ${ARCH}
Requires: issabel-framework >= 4.0.0
Requires: php >= 7.4
Requires: php-pdo
Requires: php-json
Requires: php-sqlite3
Requires: php-cli
Requires: composer
Requires: policycoreutils-python-utils

%description
Advanced SMS Module for Issabel PBX with Telnyx API integration.
Features:
- Send and receive SMS messages
- Web dashboard for message management
- Secure configuration storage
- Detailed message logging

%prep
%setup -q

%install
rm -rf \$RPM_BUILD_ROOT
mkdir -p \$RPM_BUILD_ROOT${MODULE_DIR}
cp -r * \$RPM_BUILD_ROOT${MODULE_DIR}

%clean
rm -rf \$RPM_BUILD_ROOT

%pre
# Backup existing configuration if it exists
if [ -f ${MODULE_DIR}/.env ]; then
    cp ${MODULE_DIR}/.env ${MODULE_DIR}/.env.bak
fi

%post
# Instalar dependencias de Composer
cd ${MODULE_DIR}
composer install --no-dev --optimize-autoloader

# Ejecutar el instalador
php setup/installer.php

# Configurar permisos
chown -R asterisk:asterisk ${MODULE_DIR}
chmod -R 755 ${MODULE_DIR}
touch ${MODULE_DIR}/.env
chmod 600 ${MODULE_DIR}/.env
chown asterisk:asterisk ${MODULE_DIR}/.env

# Configurar SELinux
semanage fcontext -a -t httpd_sys_rw_content_t "${MODULE_DIR}/.env"
semanage fcontext -a -t httpd_sys_rw_content_t "${MODULE_DIR}/vendor(/.*)?"
semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/db/sms.db"
restorecon -Rv ${MODULE_DIR}
restorecon -Rv /var/www/db

# Permitir a Apache escribir en la base de datos y conectarse a Internet
setsebool -P httpd_unified 1
setsebool -P httpd_can_network_connect 1

# Reiniciar Apache
systemctl restart httpd

%preun
# Limpiar configuraciones al desinstalar
if [ \$1 -eq 0 ]; then
    rm -rf ${MODULE_DIR}
    rm -f /var/www/db/sms.db
fi

%files
%defattr(-,root,root)
${MODULE_DIR}/*

%changelog
* $(date +"%a %b %d %Y") Issabel Package Maintainer <maintainer@issabel.org> ${VERSION}-${RELEASE}
- Automated build of SMS module
- Telnyx API integration
- Dashboard improvements
EOF

# Crear el tarball
cd ..
tar -czf ${BUILDROOTDIR}/SOURCES/${NAME}-${VERSION}.tar.gz smsasterisk/

# Construir el RPM
rpmbuild -ba ${BUILDROOTDIR}/SPECS/${NAME}.spec

# Copiar el RPM a un directorio accesible
mkdir -p ./dist
cp ${BUILDROOTDIR}/RPMS/${ARCH}/${NAME}-${VERSION}-${RELEASE}.${ARCH}.rpm ./dist/

echo "RPM build complete. Package available at: ./dist/${NAME}-${VERSION}-${RELEASE}.${ARCH}.rpm"

# Mostrar información del paquete
rpm -qpi ./dist/${NAME}-${VERSION}-${RELEASE}.${ARCH}.rpm
