<?php

/**
 * compilar
 * 
 * Esta clase contiene toda la funcionalidad para crear la estructura de traducciones en la compilación
 * Las traducciones en los fichero con extensión .jsx tienen que tener este formato:
 *      __('cadena a traducir',objetoJavascript)
 * Especial atención a las comillas simples y el nombre del objeto javascript que sera pasado al html  
 */
class Compilar{

    public $nombre_base;
    public $directorio_padre;
    public $text_domain;
    public $namespace;

    function __construct($text_domain = ''){
        $this->text_domain = $text_domain;
        $this->directorio_padre =  dirname(pathinfo(__FILE__)['dirname']);
        $longitud = strlen($this->directorio_padre);
        $nombre_directorio_local = substr(pathinfo(__FILE__)['dirname'],$longitud+1);
        $vector = explode('-',$nombre_directorio_local);
        $this->nombre_base = $vector[1];
        $this->namespace = '';
        $words = explode('-',$this->text_domain);
        foreach($words as $word)
            $this->namespace = $this->namespace . ucfirst($word);
        echo 'nombre_base :'.$this->nombre_base.PHP_EOL;
        echo 'directorio_padre :'.$this->directorio_padre.PHP_EOL;
        echo 'text_domain : '.$this->text_domain.PHP_EOL;
        echo 'namespace : '.$this->namespace;
    
    }
    
    function get_translation_args($folder){
        $arguments = array();
        // file name for translations file
        $file_name = $this->text_domain.'-'.$this->nombre_base.'-translations.php';


        $words = explode('-',$this->text_domain);
        $function_name = '';
        foreach($words as $word){
            if ($function_name == '')
                $function_name = $word;
            else $function_name = $function_name.'_'.$word;
        }
        $function_name = $function_name.'_'.$this->nombre_base.'_translations';

        echo 'get_translation_args $function_name :'.$function_name.PHP_EOL;


        if (file_exists($folder.'/'.$file_name)){
            $fichero = fopen($folder.'/'.$file_name,'r');
            $linea = fgets($fichero);
            while ($linea !== false){
                $primeras_comillas = strpos($linea,'"',0);
                $segundas_comillas = strpos($linea,'"',$primeras_comillas + 1);
                if (is_int($primeras_comillas) && is_int($segundas_comillas)){
                    $longitud = $segundas_comillas - $primeras_comillas + 1;
                    $nuevo_argumento = substr($linea,$primeras_comillas,$longitud);
                    $arguments[] = str_replace('"',"'",$nuevo_argumento);
                }
                $linea = fgets($fichero);
            }
        }

        return $arguments;
    
    }



    function create_translation_file($folder){
        
              
        $arguments = $this->get_new_arguments_folder($folder);
        
        // file name for translations file.
        $file_name = $this->text_domain.'-'.$this->nombre_base.'-translations.php';
        $words = explode('-',$this->text_domain);
        $function_name ='';
        foreach($words as $word){
            if ($function_name == '')
                $function_name = $word;
            else $function_name = $function_name.'_'.$word;
        }
        $function_name = $function_name.'_'.$this->nombre_base.'_translations';
 
        if (! file_exists($folder.'/includes/Translations'))
            mkdir($folder.'/includes/Translations');
        $fichero = fopen($folder.'/includes/Translations/Translations.php','w');
        fputs($fichero,'<?php'.PHP_EOL);
        fputs($fichero,PHP_EOL);
        fputs($fichero,"namespace ".$this->namespace.'\Translations;'.PHP_EOL);
        fputs($fichero,PHP_EOL);
        fputs($fichero,"if ( ! defined( 'ABSPATH' ) ) {exit;} ;".PHP_EOL);
        fputs($fichero,PHP_EOL);
        fputs($fichero,"class Translations {".PHP_EOL);
        fputs($fichero,PHP_EOL);
        fputs($fichero,'    public static function '.$this->nombre_base.'_translations(){'.PHP_EOL);
        fputs($fichero,'        $output = array('.PHP_EOL); 
        foreach($arguments as $key => $argument){
            $cadena = '             '.$key.' =>  __('.$argument.",'".$this->text_domain."'),".PHP_EOL;
            fputs($fichero,$cadena);
        }
        fputs($fichero,'            );'.PHP_EOL);
        fputs($fichero,PHP_EOL);
        fputs($fichero,'        return $output;'.PHP_EOL); 
        fputs($fichero,PHP_EOL);
        fputs($fichero,'    }'.PHP_EOL);
        fputs($fichero,'}'.PHP_EOL);
        fclose($fichero);            
    }

    function get_new_arguments_folder($folder,$arguments = []){
        $new_arguments = $arguments;
        if (is_dir($folder)){
            $manejador = opendir($folder);
            while (false !== ($file = readdir($manejador))){
                if ($file !== 'node_modules')
                    if ( str_ends_with($file,'.jsx')){
                        echo 'analising file :'.$file.PHP_EOL;
                        $new_arguments = $this->get_new_arguments_file($folder.'/'.$file,$new_arguments);

                    } else {
                        if (is_dir($folder.'/'.$file) && ($file != '.') && ($file != '..')){
                            //echo 'reading folder :'.$file.PHP_EOL;                    
                            $new_arguments = $this->get_new_arguments_folder($folder.'/'.$file,$new_arguments); 
                        } //else echo 'omiting :'.$file.PHP_EOL;
                    }
            }
        }
        return $new_arguments;
    }

    function get_new_arguments_file($file,$arguments){
        $new_arguments = $arguments;

        if (is_file($file)){
           $fichero = fopen($file,"r"); 
           if ($fichero !== false){
            $linea = fgets($fichero);
            while ($linea !== false){
                $line_arguments = $this->get_line_arguments($linea);
                $arguments_auxiliar = $new_arguments;
                foreach($line_arguments as $line_key => $line_argument){
                    $encontrado = false;
                    foreach($new_arguments as $key => $argument)
                        if ($line_key == $key)
                            $encontrado = true;
                    if (! $encontrado)
                    $new_arguments[$line_key] = $line_argument;
                }
                $linea = fgets($fichero);   
            }
           }
        }

        return $new_arguments;
    }

    function get_line_arguments($linea){
        $arguments = array();
        $offset = 0;
        $posicion = strpos($linea,"gt(",$offset);
        while($posicion !== false){
            $primera_coma = strpos($linea,"'",$posicion);
            $segunda_coma = strpos($linea,"'",$primera_coma + 1);
            $tercera_coma = strpos($linea,"'",$segunda_coma + 1);
            $cuarta_coma = strpos($linea,"'",$tercera_coma + 1);
            if (is_int($primera_coma) && is_int($segunda_coma) && is_int($tercera_coma) && is_int($cuarta_coma)){
                $longitud_clave = $segunda_coma - $primera_coma + 1;
                $longitud_texto = $cuarta_coma - $tercera_coma + 1;
                $nueva_clave = substr($linea,$primera_coma,$longitud_clave);
                $nuevo_texto = substr($linea,$tercera_coma,$longitud_texto);
                $encontrado = false;
                // Comprobamos que las claves no sean iguales
                foreach($arguments as $key => $argument)
                    if ($nueva_clave == $key){
                        // check if the string is the same, if not exit and alert, diferent string with same key
                        if ($nuevo_texto !== $argument)
                            exit('Inconsistencia en cadenas con misma clave, clave: '.$nueva_clave);
                        $encontrado = true;
                    }

                if (! $encontrado)
                    $arguments[$nueva_clave] = $nuevo_texto;
            }
            $posicion = strpos($linea,"gt(",$posicion + 1);
        }
        return $arguments;
    }

	/**
	 * increment_version
	 *
	 * Este metodo abre el fichero index.php del plugin y busca si se ha definido la constante para la version
     * en caso de encontrarla la incrementa.
     * La constante para la versión tiene que depender del text-domain, 
     * ejemplo: text-domain 
     * Constante: TEXT_DOMAIN_VERSION
	 * 
	 * @param  mixed $params
	 * @return json 
	 */
    function increment_version(){
        // Si el text-domain no esta vacio
        if ($this->text_domain != ''){
            // genera un arry con las palabras del text domain
            $words = explode('-',$this->text_domain);
            $constante = '';
            foreach($words as $word)
                if ($constante == '')
                    $constante = strtoupper($word);
                else $constante = $constante.'_'.strtoupper($word);

            $constante = $constante.'_VERSION';
            $cadena_a_buscar = "define('".$constante."',";
//              echo 'Cadena a buscar : '.$cadena_a_buscar;
            $longitud_cadena_a_buscar = strlen($cadena_a_buscar);
//              nombre del fichero inicial del plugin
            $fichero_index = $this->directorio_padre.'/'.$this->text_domain.'.php';
            $contenido = file_get_contents($fichero_index);
            $posicion_inicial = strpos($contenido, $cadena_a_buscar);

            if ($posicion_inicial !== false){
                $posicion_siguiente = strpos($contenido,')',$posicion_inicial + $longitud_cadena_a_buscar);
                $longitud_substring = $posicion_siguiente - ($posicion_inicial + $longitud_cadena_a_buscar);
                $version = substr($contenido,$posicion_inicial + $longitud_cadena_a_buscar,$longitud_substring);
                if (is_int(intval($version,10))){
                    $nueva_version = intval($version,10) + 1;
                    $cadena_a_sustituir = $cadena_a_buscar.$version.');';
                    $cadena_substituta = $cadena_a_buscar.$nueva_version.');';
                    $contenido_nuevo = substr_replace($contenido,$cadena_substituta,$posicion_inicial,strlen($cadena_a_sustituir));
                    file_put_contents($fichero_index,$contenido_nuevo);
                }
            }
        }
    }
}