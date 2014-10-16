<?php
    include_once 'database.php';
    class KAPI{
        protected $db;
	public function __construct($dbHost,$username,$password,$dbname)
	{
            $this->db = new KDatabase($dbHost,$username,$password,$dbname);
	}
        public function checkArg($name,$value){
            return true;
        }
        //---------------------------------------------------------------
        //-- Static members section
        //---------------------------------------------------------------
        protected static function error($message){
            die(KAPI::output(array('error'=>$message)));
        }
        private static function output($value, $encoder='JSON'){
            $encoder = $encoder ?: 'JSON';
            switch($encoder){
                case 'JSON':
                    header("Content-Type: application/json");
                    echo json_encode($value, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK );
                    break;
                default:
                    print_r($value);                    
            }
        }
        public static function start(){
            try
            {
                error_reporting(E_ERROR);
                $args = $_GET ?: $_POST ?: array();
                if(isset($args['call'])){
                    list($class, $method) = explode('.', $args['call']);
                    $class = strtolower($class);
					$path = '..'.DIRECTORY_SEPARATOR  .$class.'.php';
                    if(file_exists($path)){
                        include_once $path;
                        $api = new $class();
                        if(!method_exists($api,$method)){
                            throw new Exception('wrong_call');
                        }
                        else{
                            $params = array();
                            foreach ((new ReflectionMethod($class, $method))->getParameters() as $param) {
                                if(!$param->isOptional()){
                                    $param_name = $param->getName();
                                    if(!isset($args[$param_name]) || $args[$param_name]==""){
                                        throw new Exception('arg_missed');
                                    }else if(!$api->checkArg($param_name,$args[$param_name])){
                                        throw new Exception('wrong_arg');
                                    }else{
                                        //$value = mysql_real_escape_string($args[$param_name]);
                                        $value = $args[$param_name];
                                        array_push($params,is_numeric($value)?(double)$value:$value);
                                    }
                                }
                            }
                            KAPI::output(call_user_func_array(array($api, $method),$params),isset($args['output'])?strtoupper($args['output']):null);
                        }	    
                    }else{
                        throw new Exception('wrong_class');
                    }        
                }else{
                    throw new Exception('wrong_call');
                }
            }
            catch(Exception $e){
                KAPI::error($e->getMessage());
            }            
        }        
    }
?>