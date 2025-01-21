# Módulo SMS para Issabel PBX con Telnyx

Este módulo permite enviar mensajes SMS desde Issabel PBX utilizando la API de Telnyx.

## Características
- Envío de SMS desde la interfaz web de Issabel
- Integración con Telnyx para envío de mensajes
- Registro detallado de mensajes enviados
- Seguimiento del estado de los mensajes
- Dashboard interactivo para gestión de mensajes
- Configuración de credenciales desde la interfaz web

## Requisitos
- Issabel PBX 4.0 o superior en Rocky Linux
- PHP 7.4 o superior
- Composer
- Cuenta activa en Telnyx con:
  - API Key
  - Número de teléfono configurado

## Instalación en Rocky Linux

### Preparación del Sistema
1. Instalar dependencias necesarias:
```bash
dnf install -y php-pdo php-json php-sqlite3 composer policycoreutils-python-utils
```

2. Habilitar los módulos necesarios de PHP:
```bash
dnf module enable php:7.4
```

### Método 1: Instalación desde RPM (Recomendado)
1. Descargar el paquete RPM
```bash
wget https://github.com/araizaeduardo/issabel-sms5/releases/latest/download/issabel-sms-latest.rpm
```

2. Instalar el paquete
```bash
rpm -ivh issabel-sms-latest.rpm
```

### Método 2: Instalación Manual
1. Clonar el repositorio en una carpeta temporal
```bash
cd /tmp
git clone https://github.com/araizaeduardo/issabel-sms5.git
```

2. Copiar los archivos al directorio de módulos
```bash
cp -r issabel-sms5/* /var/www/html/modules/sms/
```

3. Instalar dependencias con Composer
```bash
cd /var/www/html/modules/sms
composer install --no-dev
```

4. Ejecutar el instalador
```bash
php setup/installer.php
```

5. Configurar permisos y SELinux
```bash
# Permisos básicos
chown -R asterisk:asterisk /var/www/html/modules/sms
chmod -R 755 /var/www/html/modules/sms
touch /var/www/html/modules/sms/.env
chmod 600 /var/www/html/modules/sms/.env
chown asterisk:asterisk /var/www/html/modules/sms/.env

# Configuración SELinux
semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/html/modules/sms/.env"
semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/html/modules/sms/vendor(/.*)?"
semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/db/sms.db"
restorecon -Rv /var/www/html/modules/sms
restorecon -Rv /var/www/db

# Permitir a Apache escribir en la base de datos y conectarse a Internet
setsebool -P httpd_unified 1
setsebool -P httpd_can_network_connect 1

# Reiniciar Apache
systemctl restart httpd
```

### Método 3: Construcción del RPM
Si deseas construir tu propio paquete RPM:

1. Instalar herramientas de construcción
```bash
dnf install -y rpm-build rpmdevtools
```

2. Clonar el repositorio
```bash
git clone https://github.com/araizaeduardo/issabel-sms5.git
```

3. Ejecutar el script de construcción
```bash
cd issabel-sms5/setup
chmod +x build.sh
./build.sh
```

El RPM se generará en `~/rpmbuild/RPMS/noarch/`

## Configuración de Telnyx
1. Crear una cuenta en [Telnyx](https://telnyx.com)
2. Obtener tu API Key desde el portal de desarrolladores
3. Configurar o comprar un número de teléfono para enviar SMS
4. En la interfaz web de Issabel, ir a "SMS" y configurar las credenciales

## Uso del Módulo
1. Acceder a la interfaz web de Issabel
2. Ir al menú "SMS"
3. Si es la primera vez, configurar las credenciales de Telnyx
4. Usar el dashboard para:
   - Enviar nuevos mensajes
   - Ver mensajes enviados y recibidos
   - Verificar el estado de entrega
   - Gestionar la configuración

## Solución de Problemas

### Problemas de Permisos
1. Verificar los logs de SELinux:
```bash
tail -f /var/log/audit/audit.log | grep denied
```

2. Verificar los logs de Apache:
```bash
tail -f /var/log/httpd/error_log
```

3. Comprobar los permisos SELinux:
```bash
ls -Z /var/www/html/modules/sms
ls -Z /var/www/db/sms.db
```

### Otros Problemas
- Revisar la tabla `sms_log` en la base de datos SQLite
- Asegurarse que el archivo `.env` tiene los permisos correctos
- Verificar la conectividad con la API de Telnyx:
```bash
curl -v https://api.telnyx.com/v2/health
```

## Soporte
Para reportar problemas o sugerir mejoras, por favor crear un issue en el repositorio:
https://github.com/araizaeduardo/issabel-sms5/issues
