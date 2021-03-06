<?php
include_once 'database.php';
class KAPI
{
    protected $db;
    public function __construct() {
        if (isset(self::$config['db'])) {
            $this->db = new KDatabase(
                self::$config['db']['host'], 
                self::$config['db']['username'], 
                self::$config['db']['password'], 
                self::$config['db']['name']
                );
        }
        $this->startSession();
    }    
    protected function call($method, $args) {
        return call_user_func_array(array($this, $method), $args);
    }
    protected function apiLink($method, $args = array()) {
        return explode('?', self::url()) [0] . '?' . http_build_query(array_merge($args, array('call' => get_class($this) . '.' . $method)));
    }
    //---------------------------------------------------------------
    //-- Static members section
    //---------------------------------------------------------------
    protected static function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    protected static function url() {
        return 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    protected static function error($message) {
        die(self::output(array('error' => $message)));
    }
    protected static function redirect($url = null, $params = null) {
        if(!$url){
            $url = explode('api/', self::url()) [0];
        }
        $url.= ($params ? (strpos($url, '?') !== false ? '&' : '?') . urldecode(http_build_query($params)) : '');
        //print_r([$url, $params]);
        //die();
        header("Location: $url");
    }
    private static function output($value, $encoder = 'JSON') {
        if (array_key_exists('callback', self::$args) && self::$args['callback']!='') {
            if ($value && array_key_exists('error', $value)) {
                $value['message'] = $value['error'];
                unset($value['error']);
                $statue = 'fail';
            } else {
                $statue = 'success';
            }
            self::redirect(self::$args['callback'], array_merge(['status' => $statue], $value?$value:[]));
        } else {
            $encoder = $encoder ? : 'JSON';
            switch ($encoder) {
                case 'JSON':
                    if (!error_get_last()) {
                        header("Content-Type: application/json");
                    }
                    echo json_encode($value, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
                    break;

                default:
                    print_r($value);
            }
        }
    }
    public static $args;
    protected static $config;
    public static function start() {
        self::$config = include('config.php');
        self::$args = array_merge($_GET, $_POST, $_FILES, array());
        try {
            
            //error_reporting(E_ERROR);
            if (isset(self::$args['call'])) {
                list($class, $method) = explode('.', self::$args['call']);
                $class = strtolower($class);
                if (file_exists($class . '.php')) {
                    include_once $class . '.php';
                    $api = new $class();
                    if (!method_exists($api, $method)) {
                        throw new Exception('wrong_call');
                    } else {
                        $params = array();
                        $reflection = new ReflectionMethod($class, $method);
                        if ($reflection->isPublic()) {
                            foreach ($reflection->getParameters() as $param) {
                                $param_name = $param->getName();
                                if (!$param->isOptional() && !isset(self::$args[$param_name])) {
                                    throw new Exception($param_name . '_missed');
                                }
                                $value = !array_key_exists($param_name, self::$args) || self::$args[$param_name] == '' ? null : self::$args[$param_name];
                                array_push($params, is_numeric($value) ? (double)$value : $value);
                            }
                            self::output($api->call($method, $params), isset(self::$args['output']) ? strtoupper(self::$args['output']) : null);
                        } else {
                            throw new Exception('private_method_call');
                        }
                    }
                } else {
                    throw new Exception('wrong_class');
                }
            } else {
                throw new Exception('call_missed');
            }
        }
        catch(Exception $e) {
            self::error($e->getMessage());
        }
    }
    static function handleFiles($files, $options) {
        if ($files['error'] == 4) {
            throw new Exception('file_empty');
        }
        $options = array_merge(array('filenamer' => function ($index, $extention) {
            return uniqid().'.'.$extention;
        }, 'supportedFileTypes' => array(), 'maxFileSize' => 0, 'supportedFileTypes' => [], 'targetPath' => 'uploads', 'filenamePrefix' => ''), $options);
        $counter = 0;
        if(!is_array($files['type'])){
            foreach ($files as $key => $value) {
                $files[$key] = [$value];
            }
        }
        foreach ($files['type'] as $type) {
            if (sizeof($options['supportedFileTypes']) > 0 && array_search($type, $options['supportedFileTypes']) === false) {
                throw new Exception('file_support');
            }
            
            //checking file size error
            if ($options['maxFileSize'] > 0 && $files['size'][$counter] > $options['maxFileSize']) {
                throw new Exception('file_size');
            }
            
            //checking file load error
            if ($files['error'][$counter] != 0) {
                throw new Exception('file_io');
            }
            $counter++;
        }
        $counter = 0;
        $filenames = array();
        foreach ($files['tmp_name'] as $tmp_name) {
            $filename = $options['targetPath'] . DIRECTORY_SEPARATOR . $options['filenamePrefix'] . $options['filenamer']($counter,pathinfo($files['name'][$counter], PATHINFO_EXTENSION));
            array_push($filenames, $filename);
            move_uploaded_file($tmp_name, '../' . $filename);
            $counter++;
        }
        return $filenames;
    }
}
?>