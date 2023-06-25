# Pre_Parcial2_Progra3
Pre_Parcial2_Progra3


Chequear localhost que funcione.

Ir a Apache en config httpd.conf y chequear que 

<Directory />
    AllowOverride all
    Require all denied
</Directory>

Este AllowOverride en all, sino cambiarlo a "all".

Ir a carpeta xampp en disco local:
apache/conf/extra ahi abrir con visual httpd-vhosts deberia estar algo asi:

/// MI LOCALHOST VIRTUAL////
<VirtualHost *:80>
    ServerAdmin administrator@mail.com
    DocumentRoot "C:/xampp/htdocs/Ej_Labo_Clase07/backend/public" -> ruta del proyecto
    ServerName api_slim4                                          -> nombre del server "ruta a la cual se le pega"
    ErrorLog "logs/api_slim4-error.log"
    CustomLog "logs/api_slim4-access.log" common
</VirtualHost>

//// LOCALHOST NORMAL  ////
<VirtualHost *:80>
    ServerAdmin administrator@mail.com
    DocumentRoot "C:/xampp/htdocs" 
    ServerName localhost
    ErrorLog "logs/localhost.log"
    CustomLog "logs/localhost.log" common
</VirtualHost>

Una vez agregada la ruta...

Ir a dico local Windows/System32/drivers/etc ->> abrir en visual el archivo: hosts

detro de host deveria verse:
# localhost name resolution is handled within DNS itself.
#	127.0.0.1       localhost
#	::1             localhost
    127.0.0.1       api_slim4  --> este seria nuestro server

Deveriamos poner en el mismo puerto que el localhost el nombre de nuestro server.

Deberia pedirnos servicios de ADM , en caso de complicaciones ejecutarlo con servicios de ADM en Visual o Bloc (CAMBIARLE LOS PERMISOS EN PROPIEDADES)

POR ULTIMO agregar en public el archivo .htaccess

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]

Reiniciamos los servers y le pegamos a la ruta de nuestro server "NO OLVIDAR COLOCAR LA RUTA get->(/) con algun mensaje para ver si funciona"

JSONS --> Instalar los json por composer y luego lanzar : composer require php-di/php-di

asi deve estar el json: hacer composer install
    "require": {
        "slim/slim": "4.*",
        "slim/psr7": "^1.3",
        "firebase/php-jwt": "^4.0"
    }

extras comandos:
composer require firebase/php-jwt
composer require php-di/php-di
composer require slim/slim:"4.*"
composer require slim/psr7

























