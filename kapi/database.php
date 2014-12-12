<?php
include_once 'table.php';
class KDatabase extends mysqli
{
    public static $default;
    public function __construct($dbHost, $username, $password, $dbname) {
        
        // turn of error reporting
        //mysqli_report(MYSQLI_REPORT_OFF);
        // connect to database
        @parent::__construct($dbHost, $username, $password, $dbname);
        
        // check if a connection established
        if (mysqli_connect_errno()) {
            throw new Exception(mysqli_connect_error(), mysqli_connect_errno());
        } else {
            $this->query('USE ' . $dbname);
        }
    }
    public function sql_args_string($args) {
        $sql = '';
        foreach ($args as $arg) {
            switch (gettype($arg)) {
                case 'unknown type':
                case 'NULL':
                    $sql.= "NULL";
                    break;

                default:
                    $sql.= "'" . $this->real_escape_string($arg) . "'";
            }
            $sql.= ",";
        }
        return substr($sql, 0, strlen($sql) - 1);
    }
    public function call($procedure, $args) {
        $args = $this->sql_args_string($args);
        $result = $this->query("CALL $procedure($args);");
        if ($result->hasError()) {
            throw new Exception($result->getError());
        }
        return $result;
    }
    public function query($query) {        
        //echo '<div  class="well">'.$query.'</div>';
        //free  prev results
        while ($this->more_results() && $this->next_result()) {
            $result = $this->store_result();
            if (is_object($result)) $result->free();
            unset($result);
        }
        $this->set_charset("utf8");
        $results = $this->real_query($query);
        if (!$results) {
            throw new exception($this->error, $this->errno);
        }
        return new KTable($this);
    }
    public static function checkArgs($args) {
        foreach ($args as $key => $value) {
            if (is_array($value) || $value instanceof Traversable) {
                KDatabase::checkArgs($value);
            } else {
                $args[$key] = $this->real_escape_string($value);
            }
        }
    }
}
?>