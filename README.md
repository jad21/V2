---
title: V2
authors: Jose Angel Delgado <jose.delgado@gmail.com>

# Overview
  V2 es un microframework basado en el psr-4 de php. Dirigido a la creación de api y comandos de consola.

# Instalación

* `git clone https://github.com/jad/V2.git`
* `cd V2 && composer install`

# Getting Started

  V2 es modular, por lo que cada desarrollo se tiene que considerar como la creacion de un modulo.
 El patro de desarrollo es el seguiente:

> Para entorno web `
[ Models|Herpels ] => Services => Controllers.`
> Para procesos de consola `[ Models|Herpels ] => Services => Commands.`

## Estructura

```
├── 01_app/
│   ├── 02_Commands/
│   ├── 03_Modules/
│   │   ├── 04_NameModule/
│   │   │   ├── 05_Controllers/
│   │   │   ├── 06_Helpers/
│   │   │   ├── 07_Models/
│   │   │   ├── 08_Services/
│   │   │   ├── Commands/
│   │   ├── OtherModule/
│   ├── 09_etc/
│   │   ├── 10_config.xml
│   │   ├── 11_commands.xml
│   ├── 12_var/
│   │   ├── 13_logs/
│   ├── 13_views/
├── 14_public/
├── 15_vendor/
```
* `01_app`: app server
* `02_Commands`: Donde van los los procesos
* `03_Modules`: Carpeta donde van todos los modulos
* `04_NameModule`: Nombre de un modulo
* `06_Helpers`: Donde van las Api Rest Soap, Etc.
* `09_etc`: aca van alojados todos los archivos de configuracion, como datos de conexion, ext.
* `10_config.xml`: archivo de configuracion de conexiones de base de datos, etc
* `11_commands.xml`: archivo de configuracion donde van todas las clases de los procesos
* `12_var`: carpeta donde van alojados archivos de datos como los logs, libs...
* `15_vendor`: librerias externar instalas con composer

# Archivos de configuracion:

El archivo por defecto de configuracion es un xml que se llama `config.xml`:
```php
  <?php
    $config = env("config");
    echo $config->global->author;//Jose Angel Delgado
  ?>
```
```xml
  <config>
    ...
    <global>
        <version>1.1</version>
        <author><![CDATA[Jose Angel Delgado]]></author>
        <email><![CDATA[esojangel@gmail.com]]></email>
    </global>
    <db>
      <default>
        <![CDATA[mysql]]>
      </default>
      <connections>
        <mysql>
          <host><![CDATA[hosy]]></host>
          <port><![CDATA[3306]]></port>
          <dbname><![CDATA[database]]></dbname>    
                <username><![CDATA[user]]></username>
                <password><![CDATA[pass]]></password>
        </mysql>
      </connections>
    </db>
  ...
  </config>
```
tambien se pueden hacer archivos de configuracion en formato json
```json
  {
    "config":{
      "global":{
        "author":"Jose Angel Delgado"
      }
    }
  }
```

# Como acceder a una ruta del controlador:

  `module/controller/method`

### example:

```shell
curl -X GET http://127.0.0.1/V2/main/trans/index
```
### response
```json
{
  "response": "Hola Yaxa"
}

```
  > nota: 
   >donde `main` es el modulo, `trans` es el controlador llamado `TransController` y `index` es el metodo del controlador que queremos consultar




