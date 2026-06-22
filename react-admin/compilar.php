<?php
// Iniciando la creación de la estructura de traducciones
require_once('class-compilar.php');

// Es necesario establecer manualmente el Text Domain , para que la
// estructura de traducciones que crea este fichero funcione.

$text_domain = 'gesimatic-backup';
$javascript_file_name = 'gesimatic-backup-admin.js';
$css_file_name ='gesimatic-backup-admin.min.css';

$herramientas = new Compilar($text_domain);

echo "Ejecutando 'npm run build' \n";
$comando = "npm run build";
exec($comando,$salida,$codigoSalida);
echo "Se ha ejecutado 'npm run build' con un codigo de salida : ".$codigoSalida."\n";
if ($codigoSalida == '0'){

// obtengo el directorio donde se almacenan los fichero de react compilados
$origen_dir = pathinfo(__FILE__)['dirname'].'/dist/assets';
echo "Directorio origen : ".$origen_dir."\n";
// obtengo el directorio donde se almacenaran los ficheros destino
$destino_dir = dirname(pathinfo(__FILE__)['dirname'])."/assets/js";
$destino_dir_css = dirname(pathinfo(__FILE__)['dirname'])."/assets/css";

// check if exists the assets folder and if not create it
if (! file_exists(dirname(pathinfo(__FILE__)['dirname']).'/assets'))
    mkdir(dirname(pathinfo(__FILE__)['dirname']).'/assets');

echo "Directorio destino : ".$destino_dir."\n";
// obtengo el manejador del directorio origen
$manejador = opendir($origen_dir);
// leo cada fichero del directorio origen
while (false !== ($file = readdir($manejador))){
    // si el fichero es un javascript
    if (str_ends_with($file,'.js')){
        // compruebo que la carpeta "assets" existe y si no la creo
        if (! file_exists(dirname(pathinfo(__FILE__)['dirname']).'/assets'))
            mkdir(dirname(pathinfo(__FILE__)['dirname']).'\\assets');
        // compruebo que la carpeta "assets\js" existe y si no la creo
        if (! file_exists(dirname(pathinfo(__FILE__)['dirname']).'/assets/js'))
            mkdir(dirname(pathinfo(__FILE__)['dirname']).'/assets/js');
        $origen = $origen_dir."/".$file;
        echo "Origen : ".$origen."\n";
        $destino = $destino_dir."/".$javascript_file_name;
        echo "Destino : ".$destino."\n";
        // copio el fichero javascript a su destino
        if (copy($origen,$destino))
            echo "Fichero ".$file." copiado \n";
    }
    if (str_ends_with($file,'.css')){
        // compruebo que la carpeta "assets" existe y si no la creo
        if (! file_exists(dirname(pathinfo(__FILE__)['dirname']).'/assets'))
            mkdir(dirname(pathinfo(__FILE__)['dirname']).'/assets');
        // compruebo que la carpeta "assets\css" existe y si no la creo
        if (! file_exists(dirname(pathinfo(__FILE__)['dirname']).'/assets/css'))
            mkdir(dirname(pathinfo(__FILE__)['dirname']).'/assets/css');
        $origen = $origen_dir."/".$file;
        echo "Origen : ".$origen."\n";
        $destino = $destino_dir_css."/".$css_file_name;
        echo "Destino : ".$destino."\n";
        // copio el fichero javascript a su destino
        if (copy($origen,$destino))
            echo "Fichero ".$file." copiado \n";
    }


}

$herramientas->increment_version();


// Soporte para traducciones
// Listando los ficheros a escanear dentro del directorio de react
$dir_react = (pathinfo(__FILE__)['dirname']);
$dir_plugin = dirname($dir_react);
$list_of_react_files = scandir($dir_react.'/src');
$list_of_plugin_files = scandir($dir_plugin.'/includes');
//echo '$dir_react :'.$dir_react.PHP_EOL;
//echo '$dir_plugin :'.$dir_plugin.PHP_EOL;
//echo '$list_of_react_files :'.PHP_EOL;
//var_dump($list_of_react_files);
//echo '$list_of_plugin_files :'.PHP_EOL;
//var_dump($list_of_plugin_files);
//$arguments = $herramientas->get_translation_args($dir_plugin.'\\inc');
//$arguments = array();
//arguments = $herramientas->get_new_arguments_folder($dir_react.'\\src',$arguments);

// echo '$arguments'.PHP_EOL;
// var_dump($arguments);

$herramientas->create_translation_file($dir_plugin);  

}