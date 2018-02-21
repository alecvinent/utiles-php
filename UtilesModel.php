<?php

Alec Vinent aleck.vinent@gmail.com

/**
 * UtilesModel
 *
 * @author
 * @version
 */
define("SECOND", 1);
define("MINUTE", 60 * SECOND);
define("HOUR", 60 * MINUTE);
define("DAY", 24 * HOUR);
define("MONTH", 30 * DAY);
define('TOTAL_DIAS', 30);
define('IMAGE_LOGO_DIR', 'images/logo');
define('LICENCIA_INVALIDA', 0);
define('LICENCIA_OK', 1);


class UtilesModel
{
    static function getExcelTableStyle()
    {
        // ////////
        $styleArray = array(
            'borders' => array(
                'outline' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN
                    // 'color' => array('argb' => 'FFFF0000'),
                )
            )
        );

        return $styleArray;
    }

    static function getFecha($fecha)
    {
        $tiempoModel = new TiempoModel ();

        $result = null;
        switch (gettype($fecha)) {
            case 'integer' :
                $result = $tiempoModel->fetchRow('fechaSK = ' . $fecha);
                break;

            case 'string' :
            default :
                $result = $tiempoModel->fetchRow("fecha = '" . $fecha . "'");
                break;
        }

        return $result;
    }

    static function date2timestamp($fecha)
    {
        $timestamp = 0;

        // list ( $year, $month, $day ) = split ( '[-]', $fecha );
        list ($year, $month, $day) = explode('-', $fecha);

        $timestamp = mktime(0, 0, 0, $month, $day, $year);
        // $timestamp = mktime ( date("H"), date("m"), date("s"), $month, $day, $year );

        return $timestamp;
    }

    static function getIp()
    {
        // obtain the ip
        // if getenv results in something, proxy detected
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } else { // otherwise no proxy detected
            $ip = getenv('REMOTE_ADDR');
        }

        return $ip;
    }

    /**
     * Listado de recursos para las controladoras registradas
     *
     * @return array
     */
    static function obtenerRecursos()
    {
        $acl = array();

        $frontController = Zend_Controller_Front::getInstance();
        $recursos = $frontController->getControllerDirectory();

        foreach ($recursos as $module => $path) {
            foreach (scandir($path) as $file) {
                if (strstr($file, "Controller.php") !== false) {
                    include_once $path . DIRECTORY_SEPARATOR . $file;

                    foreach (get_declared_classes() as $class) {
                        $check = substr($class, 0);

                        if ($check [0] != '_') {
                            if (is_subclass_of($class, 'Zend_Controller_Action')) {
                                $controller = strtolower(substr($class, 0, strpos($class, "Controller")));

                                // no mostrar las controladoras
                                $excluir = self::getRecursosPublicosControllers();

                                $flag = in_array($controller, $excluir);

                                if (!$flag) {
                                    $actions = array();

                                    foreach (get_class_methods($class) as $action) {
                                        if (strstr($action, "Action") !== false) {
                                            $value = str_replace('Action', '', $action);

                                            $excluir = self::getRecursosPublicosActions();
                                            $flag = in_array($value, $excluir);

                                            if (!$flag) {
                                                $actions [] = $value;
                                            }
                                        }
                                    }
                                    $acl [$module] [$controller] = $actions;
                                }
                            }
                        }
                    }

                    // $acl[$module][$controller] = $actions;
                }
            }
        }

        // Zend_Debug::dump($acl);die;
        return $acl;
    }

    static function getRecursosPublicosControllers()
    {
        $a = array(
            'auth',
            'index',
            'error',
            'licencia',
            'reporte'
        );
        return $a;
    }

    static function getRecursosPublicosActions()
    {
        $a = array(
            'index',
            'unidadmedida' => 'filtrarjson',
            'proyecto' => 'calcularhorasjson'
        );
        return $a;
    }

    // echo dateDiff("2006-04-05", "2006-04-01");

    /**
     *
     *
     *
     *
     *
     * dias de diferencia entre fechas ...
     *
     * @param unknown_type $start
     * @param unknown_type $end
     */
    static function dateDiff($start, $end)
    {
        if (!is_numeric($start) && !is_numeric($end)) {
            $start_ts = strtotime($start);
            $end_ts = strtotime($end);

            $diff = $end_ts - $start_ts;
        } else {
            $diff = $end - $start;
        }

        // return round($diff / 86400) . ' d&iacute;as';
        return round($diff / 86400);
    }

    static function tienePermiso()
    {
        $front = Zend_Controller_Front::getInstance()->getRequest();

        $module = $front->getModuleName();
        $controller = $front->getControllerName();
        $action = $front->getActionName();

        echo $module . '/' . $controller . '/' . $action;

        // recurso
        $resource_module = new Zend_Acl_Resource ($module);
        $resource_controller = new Zend_Acl_Resource ($controller);

        //
        $acl = Zend_Registry::get('acl');
        // $acl = new Zend_Acl();

        $recurso = null;
        if (!is_null($module) && !is_null($controller)) {
            $recurso = "'" . $module . ":" . $controller . "'";
            if ($module == 'default') {
                $recurso = $controller;
            }
        }

        $rol = Zend_Auth::getInstance()->hasIdentity() ? strtolower(Zend_Auth::getInstance()->getIdentity()->nombre_rol) : 'invitado';

        return $acl->isAllowed($rol, $recurso, $action);
    }

    static function ObtenerNavegador($user_agent)
    {
        $navegadores = array(
            'Opera' => 'Opera',
            'Mozilla Firefox' => '(Firebird)|(Firefox)',
            'Galeon' => 'Galeon',
            'Mozilla' => 'Gecko',
            'MyIE' => 'MyIE',
            'Lynx' => 'Lynx',
            'Netscape' => '(Mozilla/4\.75)|(Netscape6)|(Mozilla/4\.08)|(Mozilla/4\.5)|(Mozilla/4\.6)|(Mozilla/4\.79)',
            'Konqueror' => 'Konqueror',
            'Internet Explorer 7' => '(MSIE 7\.[0-9]+)',
            'Internet Explorer 6' => '(MSIE 6\.[0-9]+)',
            'Internet Explorer 5' => '(MSIE 5\.[0-9]+)',
            'Internet Explorer 4' => '(MSIE 4\.[0-9]+)'
        );

        foreach ($navegadores as $navegador => $pattern) {
            if (eregi($pattern, $user_agent))
                return $navegador;
        }
        return 'Desconocido';
    }

    static function sanitize($input)
    {
        // $cadena = trim(htmlentities(strip_tags($input)));
        $cadena = trim(strip_tags($input));
        $cadena = str_replace('&amp;', '', $cadena);
        $cadena = str_replace('amp;', '', $cadena);
        $cadena = str_replace('acute;', '', $cadena);
        $cadena = str_replace('nbsp;', '', $cadena);
        $cadena = str_replace('= ', '', $cadena);
        $cadena = str_replace('=', '', $cadena);

        return $cadena;
    }

    static function getMonthTotalDays($month, $year)
    {
        return cal_days_in_month(CAL_GREGORIAN, $month, $year);
    }

    static function getPermisos($path)
    {
        clearstatcache();

        $permisos = fileperms($path);

        if (($permisos & 0xC000) == 0xC000) {
            // Socket
            $info = 's';
        } elseif (($permisos & 0xA000) == 0xA000) {
            // Enlace Simb�lico
            $info = 'l';
        } elseif (($permisos & 0x8000) == 0x8000) {
            // Regular
            $info = '-';
        } elseif (($permisos & 0x6000) == 0x6000) {
            // Especial Bloque
            $info = 'b';
        } elseif (($permisos & 0x4000) == 0x4000) {
            // Directorio
            $info = 'd';
        } elseif (($permisos & 0x2000) == 0x2000) {
            // Especial Car�cter
            $info = 'c';
        } elseif (($permisos & 0x1000) == 0x1000) {
            // Tuber�a FIFO
            $info = 'p';
        } else {
            // Desconocido
            $info = 'u';
        }

        // Propietario
        $info .= (($permisos & 0x0100) ? 'r' : '-');
        $info .= (($permisos & 0x0080) ? 'w' : '-');
        $info .= (($permisos & 0x0040) ? (($permisos & 0x0800) ? 's' : 'x') : (($permisos & 0x0800) ? 'S' : '-'));

        // Grupo
        $info .= (($permisos & 0x0020) ? 'r' : '-');
        $info .= (($permisos & 0x0010) ? 'w' : '-');
        $info .= (($permisos & 0x0008) ? (($permisos & 0x0400) ? 's' : 'x') : (($permisos & 0x0400) ? 'S' : '-'));

        // Mundo
        $info .= (($permisos & 0x0004) ? 'r' : '-');
        $info .= (($permisos & 0x0002) ? 'w' : '-');
        $info .= (($permisos & 0x0001) ? (($permisos & 0x0200) ? 't' : 'x') : (($permisos & 0x0200) ? 'T' : '-'));

        return $info;
    }

    static function fichero2array($fichero)
    {
        $codes = array();
        foreach (file($fichero, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            @list ($key, $val) = explode(" ", $line, 2);
            $codes [$key] = UtilesModel::sanitize($val);
        }
        return $codes;
    }

    /**
     * Humanize by delta.
     *
     * @time the unix timestamp
     *
     * @return the human time text since time
     */
    static function timeago($time)
    {
        $delta = time() - $time;

        if ($delta < 1 * MINUTE) {
            return $delta == 1 ? "en este momento" : "hace " . $delta . " segundos ";
        }
        if ($delta < 2 * MINUTE) {
            return "hace un minuto";
        }
        if ($delta < 45 * MINUTE) {
            return "hace " . floor($delta / MINUTE) . " minutos";
        }
        if ($delta < 90 * MINUTE) {
            return "hace una hora";
        }
        if ($delta < 24 * HOUR) {
            return "hace " . floor($delta / HOUR) . " horas";
        }
        if ($delta < 48 * HOUR) {
            return "ayer";
        }
        if ($delta < 30 * DAY) {
            return "hace " . floor($delta / DAY) . " dias";
        }
        if ($delta < 12 * MONTH) {
            $months = floor($delta / DAY / 30);
            return $months <= 1 ? "el mes pasado" : "hace " . $months . " meses";
        } else {
            $years = floor($delta / DAY / 365);
            return $years <= 1 ? "el a&ntilde;o pasado" : "hace " . $years . " a&ntilde;os";
        }
    }

    static function getMeses()
    {
        $meses = array(
            "Enero",
            "Febrero",
            "Marzo",
            "Abril",
            "Mayo",
            "Junio",
            "Julio",
            "Agosto",
            "Septiembre",
            "Octubre",
            "Noviembre",
            "Diciembre"
        );
        return $meses;
    }

    static function getMes($pos)
    {
        $meses = array(
            "Enero",
            "Febrero",
            "Marzo",
            "Abril",
            "Mayo",
            "Junio",
            "Julio",
            "Agosto",
            "Septiembre",
            "Octubre",
            "Noviembre",
            "Diciembre"
        );
        if (array_key_exists($pos, $meses)) {
            return $meses [$pos];
        }
        return $meses [$pos - 1];
    }

    static function getBaseUrl()
    {
        $server = 'http://' . $_SERVER ['HTTP_HOST'];
        if ($_SERVER ['SERVER_PORT'] != 80) {
            $server = 'http://' . $_SERVER ['HTTP_HOST'] . ':' . $_SERVER ['SERVER_PORT'];
        }

        $server .= $_SERVER ['SCRIPT_NAME'];

        try {
            @list ($server, $a) = @explode('public/index.php', $server);
        } catch (Exception $e) {
        }

        return $server;
    }

    static function url()
    {
        $base_url = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
        $base_url .= '://' . $_SERVER['HTTP_HOST'];
        $base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
        return $base_url;
    }

    /**
     * get all files from url
     *
     * @param string $path
     * @param string $file_extension
     * @return multitype:
     */
    static function getImagesFromFolder($path = '', $file_extension = 'jpg')
    {
        // get all image files with a .jpg extension.
        $dh = opendir($path);
        while (false !== ($filename = readdir($dh))) {
            $files [] = $filename;
        }

        $str = '/\.' . $file_extension . '$/i';
        $images = preg_grep($str, $files);

        return $images;
    }

    static function getUnidadesMedida($tipo = null)
    {
        $medidas = array();
        $locale = new Zend_Locale ('es_ES');

        switch ($tipo) {
            case 'volumen' :
                $medidaModel = new Zend_Measure_Volume (1, null, $locale);
                $medidas [] ['volumen'] = $medidaModel->getConversionList();
                break;

            case 'peso' :
            default :
                $medidaModel = new Zend_Measure_Weight (1, null, $locale);
                $medidas [] ['peso'] = $medidaModel->getConversionList();
                break;

            default :
                // volumen
                $medidaModel = new Zend_Measure_Volume (1, null, $locale);
                $medidas [] ['volumen'] = $medidaModel->getConversionList();;

                // peso
                $medidaModel = new Zend_Measure_Weight (1, null, $locale);
                $medidas [] ['peso'] = $medidaModel->getConversionList();
                break;
        }

        // Zend_Debug::dump($medidas);die;
        return $medidas;
    }

    static function getUnidadMedida($buscar)
    {
        $locale = new Zend_Locale ('es_ES');
        $medidas = array();

        // ///////////////////'volumen':
        $medidaModel = new Zend_Measure_Volume (1, null, $locale);
        $medidas [] ['Zend_Measure_Volume'] = $medidaModel->getConversionList();

        // ///////////////////////'peso'
        $medidaModel = new Zend_Measure_Weight (1, null, $locale);
        $medidas [] ['Zend_Measure_Weight'] = $medidaModel->getConversionList();

        // ////////////////////////
        $medida = null;
        foreach ($medidas as $key => $value) {

            foreach ($value as $key => $values) {
                $clase = $key;

                foreach ($values as $key => $value) {
                    if ($key == $buscar) {
                        $medida = array(
                            $clase,
                            $key,
                            $value
                        );
                        break;
                    }
                }
            }
        }

        return $medida;
    }

    static function getCategoriasUnidades()
    {
        // return array('Zend_Measure_Volume', 'Zend_Measure_Weight');
        return array(
            'Zend_Measure_Weight'
        );

        // //////////////
        $lista = array();

        // ///////////////////
        $rutas = array();
        $rutas [] = '../library/Zend/Measure';
        $rutas [] = '../library/Zend/Measure/Cooking';
        $rutas [] = '../library/Zend/Measure/Flow';
        $rutas [] = '../library/Zend/Measure/Viscosity';

        foreach ($rutas as $path) {
            foreach (scandir($path) as $file) {
                if (strstr($file, ".php") !== false) {
                    include_once $path . DIRECTORY_SEPARATOR . $file;

                    foreach (get_declared_classes() as $class) {
                        $check = substr($class, 0);

                        if ($check [0] != '_') {
                            if (is_subclass_of($class, 'Zend_Measure_Abstract')) {
                                $lista [] = $class;
                            }
                        }
                    }
                }
            }
        }
        // ///////////////////

        return $lista;
    }

    static function getUnidades($categoria = 'Zend_Measure_Weight')
    {
        $lista = array();

        //$locale = new Zend_Locale ( 'es_ES' );
        $locale = new Zend_Locale ();

        $medidaModel = new $categoria (1, null, $locale);
        $lista = $medidaModel->getConversionList();

        return $lista;
    }

    static function getUnidadDatos($categoria, $buscar)
    {
        $lista = array();
        $locale = new Zend_Locale ('es_ES');

        $medidaModel = new $categoria (1, null, $locale);
        $lista = $medidaModel->getConversionList();

        $data = $lista [$buscar];
        return $data;
    }

    static function fichero_bloqueado($path)
    {
        $flag = false;

        $file = fopen($path, "w+");

        if (flock($file, LOCK_EX)) { // realizar un bloqueo exclusivo
            fwrite($file, "Escribir algo aqui\n");
            flock($file, LOCK_UN); // liberar el aviso
            $flag = true;
        }

        fclose($file);

        return $flag;
    }

    private function generate_password($number)
    {
        $arr = array(
            // 'a','b','c','d','e','f',
            // 'g','h','i','j','k','l',
            // 'm','n','o','p','r','s',
            // 't','u','v','x','y','z',
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'R',
            'S',
            'T',
            'U',
            'V',
            'X',
            'Y',
            'Z',
            '1',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9',
            '0'
            // '-'
            // '.',',',
            // '(',')','[',']','!','?',
            // '&','^','%','@','*','$'
        );
        // Generate a password
        $pass = "";
        for ($i = 0; $i < $number; $i++) {
            // Compute a random array index
            $index = rand(0, count($arr) - 1);
            $pass .= $arr [$index];
        }
        return $pass;
    }

    static function array2json($arreglo)
    {
        /*
         * $result = array(); foreach ($arreglo as $label => $value) { $result[] = array($label,$value); // make a "small" array for each pair } //Zend_Debug::dump(json_encode(array($result)));die; return json_encode($result); // wrap the result into another array; multiple plot data go like "array($result, $result2, ...)"
         */
        $result = '';
        $size = count($arreglo);
        $i = 0;
        foreach ($arreglo as $label => $value) {
            $i++;

            if (is_array($value)) {
                $keys = array_keys($value);
                $result .= "['" . trim($value [$keys [0]]) . "'," . $value [$keys [1]] . "]";
            } else {
                // $result .= "['" . $label . "'," . $value . "]";
                $result .= "'" . $label . "'," . $value;
            }

            if ($i < $size) {
                $result .= ",";
            }
        }

        return "[" . $result . "]";
    }

    static function fecha_to_mes_anno($fecha)
    {
        if (!is_numeric($fecha)) {
            $fecha = strtotime($fecha); // convierte la fecha de formato mm/dd/yyyy a marca de tiempo
        }

        $meses = array(
            "Enero",
            "Febrero",
            "Marzo",
            "Abril",
            "Mayo",
            "Junio",
            "Julio",
            "Agosto",
            "Septiembre",
            "Octubre",
            "Noviembre",
            "Diciembre"
        );
        $mes = $meses [date("n", $fecha) - 1]; // Representaci�n num�rica de un mes, sin ceros iniciales

        $ano = date("Y", $fecha); // optenemos el a�o en formato 4 digitos

        $fecha = $mes . "/" . $ano; // unimos el resultado en una unica cadena
        return $fecha; // enviamos la fecha al programa
    }

    static function tiempo_transcurrido($fecha)
    {
        if (empty ($fecha)) {
            return "No hay fecha";
        }

        $intervalos = array(
            "segundo",
            "minuto",
            "hora",
            "d&iacute;a",
            "semana",
            "mes",
            "a&ntilde;o"
        );
        $duraciones = array(
            "60",
            "60",
            "24",
            "7",
            "4.35",
            "12"
        );

        $ahora = time();
        // $Fecha_Unix = strtotime($fecha);
        // ////////////////////////////////////////
        $timestamp = '';

        if (!is_numeric($fecha)) {
            list ($year, $month, $day) = @split('[-]', $fecha);

            $Fecha_Unix = mktime(0, 0, 0, $month, $day, $year);
        } else {
            $Fecha_Unix = $fecha;
        }

        // /////////////////////////////////////

        if (empty ($Fecha_Unix)) {
            return "Fecha incorrecta";
        }
        if ($ahora > $Fecha_Unix) {
            $diferencia = $ahora - $Fecha_Unix;
            $tiempo = "Hace";
        } else {
            $diferencia = $Fecha_Unix - $ahora;
            $tiempo = "Dentro de";

            if ($diferencia == 0) {
                $tiempo = "Hace";
            }
        }
        for ($j = 0; $diferencia >= $duraciones [$j] && $j < count($duraciones) - 1; $j++) {
            $diferencia /= $duraciones [$j];
        }

        $diferencia = round($diferencia);

        if ($diferencia != 1) {
            $intervalos [5] .= "e"; // MESES
            $intervalos [$j] .= "s";
        }

        return "$tiempo $diferencia $intervalos[$j]";
    }

    static function strreplace($cadena, $isHTML = TRUE)
    {
        $simbolos = array();

        if ($isHTML) {
            $simbolos = array(
                '�' => '&Aacute;',
                '�' => '&Eacute;',
                '�' => '&Iacute;',
                '�' => '&Oacute;',
                '�' => '&Uacute;'
            );
        } else {
            $simbolos = array(
                '�' => '�',
                '�' => '�',
                '�' => '�',
                '�' => '�',
                '�' => '�'
            );
        }

        foreach ($simbolos as $key => $value) {
            $cadena = str_replace($key, $value, $cadena);
        }

        return $cadena;
    }

    static function limpiarArray($array)
    {
        $retorno = null;
        if ($array != null) {
            $retorno [0] = $array [0];
        }
        for ($i = 1; $i < count($array); $i++) {
            $repetido = false;
            $elemento = $array [$i];
            for ($j = 0; $j < count($retorno) && !$repetido; $j++) {
                if ($elemento == $retorno [$j]) {
                    $repetido = true;
                }
            }
            if (!$repetido) {
                $retorno [] = $elemento;
            }
        }
        return $retorno;
    }

    static function meses()
    {
        $meses = array(
            "Enero",
            "Febrero",
            "Marzo",
            "Abril",
            "Mayo",
            "Junio",
            "Julio",
            "Agosto",
            "Septiembre",
            "Octubre",
            "Noviembre",
            "Diciembre"
        );
        return $meses;
    }

    static function years($rango_inicio = 1, $rango_fin = NULL)
    {
        $annos = array();
        for ($i = $rango_inicio; $i <= $rango_fin; $i++) {
            $annos [] = $i;
        }
        return $annos;
    }

    /**
     * Permite copiar en un fichero un flujo de datos.
     * Si crea una copia del fichero anterior en la mismo destino, sino poner $copia = FALSE
     *
     * @param string $data
     * @param string $archivo
     * @param boolean $copia
     */
    static function salvar2fichero($data, $archivo, $copia = TRUE)
    {
        if ($copia) {
            if (file_exists($archivo)) {
                $nuevo_archivo = 'handelplus/data/last_' . time() . '.txt';
                if (!rename($archivo, $nuevo_archivo)) {
                    echo 'Error al copiar.';
                }
            }
        }

        $fichero = @fopen($archivo, 'w');
        if (!$fichero) {
            echo 'No se puede abrir el fichero.';
        }
        fwrite($fichero, $data, strlen($data));
    }

    /**
     * Ensuring valid utf-8 in PHP
     *
     * @param type $string
     * @return string
     */
    static function make_safe_for_utf8_use($string)
    {
        $encoding = mb_detect_encoding($string, "UTF-8,ISO-8859-1,WINDOWS-1252");

        $cadena = '';

        if ($encoding != 'UTF-8') {
            $cadena = iconv($encoding, 'UTF-8//TRANSLIT', $string);
        } else {
            $cadena = $string;
        }

        return html_entity_decode($cadena);
    }

    static function crearCarpeta($carpeta)
    {
        $dir = 'clientes/' . $carpeta;
        if (!is_dir($dir)) {
            mkdir($dir, 0700);
        }
    }

    /**
     * Generar clave
     *
     * @return string
     */
    static function generarAPIKey()
    {
        $a = new UtilesModel ();
        $key = $a->generate_password(strlen('h0lamund0'));

        $contratoModel = new ContratoModel ();
        $sql = $contratoModel->select();
        $sql->where('documento = ?', $key);
        $existe = $contratoModel->fetchRow($sql);

        while (!is_null($existe)) {
            UtilesModel::generarAPIKey();
        }

        return $key;
    }

    static function getUploadLimit()
    {
        $upload_max_filesize = ini_get('upload_max_filesize') . 'B';
        // $upload_max_filesize = ((int)$upload_max_filesize) * 1024 * 100;

        return $upload_max_filesize;
    }

    static function enviar_notificacion($mensaje, $tema = null, $sent_to = null)
    {
        try {
            $user_data = Zend_Auth::getInstance()->getIdentity();

            // Zend_Session::start();
            $auth_user = new Zend_Session_Namespace ('auth');

            $config = array(
                'auth' => 'login',
                'username' => $user_data->nombre_usuario,
                'password' => $auth_user->key
            );

            // get mail config
            // $mail_config = Zend_Registry::get('configuracion')->mail->toArray();
            $configuracion = Zend_Registry::get('configuracion');

            $mensaje .= "<br><br>";
            $mensaje .= $configuracion->footer_msg;

            $transport = new Zend_Mail_Transport_Smtp ($configuracion->mail_server_host, $config);
            $mail = new Zend_Mail ();
            $mail->setBodyHtml(UtilesModel::make_safe_for_utf8_use($mensaje));
            $mail->setFrom($configuracion->mail_server_webmaster, 'HANDEL');

            if (is_null($sent_to)) {
                $mail->addTo($configuracion->mail_server_webmaster, $configuracion->mail_server_webmaster);
            } else {
                $mail->addTo($sent_to, $sent_to);
            }

            if (!is_null($tema)) {
                $mail->setSubject($tema);
            } else {
                $mail->setSubject('TestSubject');
            }

            $mail->send($transport);
        } catch (Exception $c) {
            // echo $c->getMessage();
        }
    }

    static function getUrl_inicio()
    {
        return Zend_Controller_Front::getInstance()->getBaseUrl();
    }

    static function traducir($cadena)
    {
        $translate = Zend_Registry::get('Zend_Translate');
        return $translate->translate($cadena);
    }

    static function crear_fichero($fichero, $datos = NULL)
    {
        // crear fichero
        $fichero = @fopen($fichero, 'w');

        if (!is_null($datos)) {
            if (@fwrite($fichero, $datos) === false) {
                define('write_error', true);
                @fclose($fichero);
            }
        }
    }

    static function getLogo()
    {
        $ruta = 'images/empresa';
        $images = self::getImagesFromFolder($ruta, 'jpg');
        $imagen = '';

        foreach ($images as $value) {
            $imagen = $value;
            break;
        }

        return $ruta . '/' . $imagen;
    }

    /**
     * Obtener extensiones
     *
     * @return multitype:
     */
    static function getPHP_extensiones()
    {
        return get_loaded_extensions();
    }

    /**
     * Obtener monedas
     */
    static function getMonedas()
    {
        $monedaModel = new Zend_Currency ('en_US');
        return $monedaModel->getCurrencyList('en_US');
    }

    /**
     * Obtener monedas
     */
    static function getMonedaCodigo()
    {
        $monedaModel = new Zend_Currency ('en_US');
        return $monedaModel->getCurrencyList('en_US');
    }

    /**
     * formato telefono Internacional
     * Ejemplo: (123) 456 7899, (123).456.7899, (123)-456-7899, 123-456-7899, 123 456 7899, 1234567899
     * @return array
     */
    static function get_formato_telefono_Internacional()
    {
        //http://www.regexlib.com/RETester.aspx?regexp_id=296
        //CUBA +99(99)9999-9999
        //+99(99)626226

        $patron0 = '/\(?([0-9]{2})\)?([ .-]?)([0-9]{2})\2([0-9]{4})/';
        $label = self::traducir("Por ejemplo: (123) 456 7899, (123).456.7899, (123)-456-7899, 123-456-7899, 123 456 7899, 1234567899");
        return array($patron0, $label);
    }

    static function leer_fichero_completo($nombre_fichero)
    {
        //abrimos el archivo de texto y obtenemos el identificador
        $fichero_texto = fopen($nombre_fichero, "r");
        //obtenemos de una sola vez todo el contenido del fichero
        //OJO! Debido a filesize(), sólo funcionará con archivos de texto
        $contenido_fichero = fread($fichero_texto, filesize($nombre_fichero));
        return $contenido_fichero;
    }

    /**
     * Return local images as base64 encrypted code
     * @param string $filename
     * @param string $filetype
     * @return string
     */
    static function base64_encode_image($filename = string, $filetype = string)
    {
        if ($filename) {
            $imgbinary = fread(fopen($filename, "r"), filesize($filename));
            return 'data:image/' . $filetype . ';base64,' . base64_encode($imgbinary);
            //return 'data:;base64,' . base64_encode($imgbinary);
        }
    }

    static function getExcelInfo($objPHPExcel, $document_title = NULL)
    {
        settype($objPHPExcel, 'PHPExcel');
        if (!is_null($document_title)) {
            $objPHPExcel->getProperties()->setTitle($document_title);
        }

        $objPHPExcel->getProperties()->setCreator("MsC. Alexander Vinent Peña");


        $index = 0;
        foreach ($objPHPExcel->getAllSheets() as $worksheet) {
            $objPHPExcel->setActiveSheetIndex($index);

            $msg = 'Generado con ' . ConfiguracionModel::getConfiguracionClave('sitename')->value;
            $msg .= ' / ' . date('d.m.Y h:i:s a');
            $worksheet->getHeaderFooter()->setOddFooter($msg);
            $worksheet->getHeaderFooter()->setEvenFooter($msg);

            $msg2 = '&L&B' . $objPHPExcel->getProperties()->getTitle() . '&RPágina &P de &N;';
            $worksheet->getHeaderFooter()->setOddHeader($msg2);


            $index++;
        }
        $objPHPExcel->setActiveSheetIndex(0);
    }

    static function auto_refrescar_pagina($segundos = 1)
    {
        $refresh = $segundos;
        $pathdirectory = $_SERVER['PHP_SELF'];
        echo "<meta http-equiv=\"refresh\" content=\"$refresh;url=$pathdirectory\" />";
    }

    static function get_file_extension($file_name)
    {
        //$ext = substr(strrchr($file_name,'.'),1);
        $info = pathinfo($file_name, PATHINFO_EXTENSION);

        $ext = null;
        if (is_array($info)){
            if (array_key_exists('extension', $info)){
                $ext = $info['extension'];
            }
        }

        return $ext;
    }

    static function get_file_name($file_name)
    {
        return basename($file_name);
    }

    static function get_file_icon($extension)
    {
        $icono = '';

        switch ($extension) {
            case 'mp3':
                $icono = 'mp3.png';;
                break;

            case 'avi': //case 'ogg':
                $icono = 'video_mockup_online_internet_layout_blog-128.png';
                break;

            default:
                $icono = 'Movie_File.png';
                break;
        }

        return $icono;
    }

    static function get_mime_type($file)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimetype = finfo_file($finfo, $file);
            finfo_close($finfo);
        } else {
            $mimetype = mime_content_type($file);
        }
        if (empty($mimetype)) $mimetype = 'application/octet-stream';
        return $mimetype;
    }

    /**
     * Parses http query string into an array
     *
     * @author Alxcube <alxcube@gmail.com>
     *
     * @param string $queryString String to parse
     * @param string $argSeparator Query arguments separator
     * @param integer $decType Decoding type
     * @return array
     */
    static function http_parse_query($queryString, $argSeparator = '&', $decType = PHP_QUERY_RFC1738)
    {
        $result = array();
        $parts = explode($argSeparator, $queryString);

        foreach ($parts as $part) {
            list($paramName, $paramValue) = explode('=', $part, 2);

            switch ($decType) {
                case PHP_QUERY_RFC3986:
                    $paramName = rawurldecode($paramName);
                    $paramValue = rawurldecode($paramValue);
                    break;

                case PHP_QUERY_RFC1738:
                default:
                    $paramName = urldecode($paramName);
                    $paramValue = urldecode($paramValue);
                    break;
            }


            if (preg_match_all('/\[([^\]]*)\]/m', $paramName, $matches)) {
                $paramName = substr($paramName, 0, strpos($paramName, '['));
                $keys = array_merge(array($paramName), $matches[1]);
            } else {
                $keys = array($paramName);
            }

            $target = &$result;

            foreach ($keys as $index) {
                if ($index === '') {
                    if (isset($target)) {
                        if (is_array($target)) {
                            $intKeys = array_filter(array_keys($target), 'is_int');
                            $index = count($intKeys) ? max($intKeys) + 1 : 0;
                        } else {
                            $target = array($target);
                            $index = 1;
                        }
                    } else {
                        $target = array();
                        $index = 0;
                    }
                } elseif (isset($target[$index]) && !is_array($target[$index])) {
                    $target[$index] = array($target[$index]);
                }

                $target = &$target[$index];
            }

            if (is_array($target)) {
                $target[] = $paramValue;
            } else {
                $target = $paramValue;
            }
        }

        return $result;
    }

    static function is_class_method($type = "public", $method, $class)
    {
        // $type = mb_strtolower($type);
        $refl = new ReflectionMethod($class, $method);
        switch ($type) {
            case "static":
                return $refl->isStatic();
                break;
            case "public":
                return $refl->isPublic();
                break;
            case "private":
                return $refl->isPrivate();
                break;
        }
    }

    static function isFile($file)
    {
        $f = pathinfo($file, PATHINFO_EXTENSION);
        return (strlen($f) > 0) ? true : false;
    }

    static function getVideoExtensions()
    {
        //$url = 'https://raw.githubusercontent.com/sindresorhus/video-extensions/master/video-extensions.json';
        $url = 'data/video-extensions.json';
        $json = file_get_contents($url);
        $data = Zend_Json::decode($json, Zend_Json::TYPE_OBJECT);
        return $data;
    }

    /**
     * Pluralizes a word if quantity is not one.
     *
     * @param int $quantity Number of items
     * @param string $singular Singular form of word
     * @param string $plural Plural form of word; function will attempt to deduce plural form from singular if not provided
     * @return string Pluralized word if quantity is not one, otherwise singular
     */
    static function pluralize($quantity, $singular, $plural=null) {
        if($quantity==1 || !strlen($singular)) return $singular;
        if($plural!==null) return $plural;

        $last_letter = strtolower($singular[strlen($singular)-1]);
        switch($last_letter) {
            case 'y':
                return substr($singular,0,-1).'ies';
            case 's':
                return $singular.'es';
            default:
                return $singular.'s';
        }
    }
}
