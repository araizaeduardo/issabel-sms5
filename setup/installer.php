<?php
function createdirhierarchy($basePath, $dirs) {
    foreach ($dirs as $dir) {
        $path = $basePath . "/" . $dir;
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }
}

$dirs = array(
    "/var/www/html/modules/sms",
    "/var/www/html/modules/sms/themes",
    "/var/www/html/modules/sms/themes/default",
    "/var/www/html/modules/sms/themes/default/css",
    "/var/www/html/modules/sms/themes/default/js",
    "/var/www/html/modules/sms/libs",
    "/var/www/html/modules/sms/configs",
    "/var/www/html/modules/sms/lang",
    "/var/www/html/modules/sms/setup",
);

// Crear directorios
createdirhierarchy("", $dirs);

// Crear base de datos SQLite si no existe
$dbPath = "/var/www/db/sms.db";
if (!file_exists(dirname($dbPath))) {
    mkdir(dirname($dbPath), 0755, true);
}

// Crear archivo menu.xml
$menuXML = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<module>
    <menulist>
        <menuitem menuid="sms"    desc="SMS"    parent=""   module="no"  link="" order="7">
            <permissions>
                <group id="1" name="administrator" desc="total access"></group>
            </permissions>
        </menuitem>
        <menuitem menuid="smsconfig" desc="SMS" parent="sms" module="yes" link="yes" order="1">
            <permissions>
                <group id="1" name="administrator" desc="total access"></group>
            </permissions>
        </menuitem>
    </menulist>
</module>
EOT;

file_put_contents("/var/www/html/modules/sms/menu.xml", $menuXML);
?>
