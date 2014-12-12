<?php
class KTable extends mysqli_result implements ArrayAccess
{
    protected $database;
    public function __construct($database) {
        @parent::__construct($database);
        $this->database = $database;
    }
    public function isEmpty() {
        return $this->database->field_count == 0;
    }
    public function offsetSet($offset, $value) {
        throw new exception("sqlresult setting!");
    }
    public function offsetExists($offset) {
        return !$this->isEmpty() && $this->num_rows - 1 > $offset && $offset >= 0;
    }
    public function offsetUnset($offset) {
        throw new exception("sqlresult unsetting!");
    }
    public function offsetGet($offset) {
        $this->data_seek($offset);
        return $this->fetch_array(MYSQLI_ASSOC);
    }
    public function to_json($column = null, $row = - 1) {
        if ($this->isEmpty()) {
            return '[]';
        }
        $rows = $this->getRows($column);
        if ($row >= 0) {
            $rows = $rows[$row];
        }
        return json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    }
    public function getRows($column = null) {
        if ($this->isEmpty()) {
            return [];
        } else {
            $this->data_seek(0);
            $rows = array();
            while ($row = $this->fetch_assoc()) {
                $rows[] = ($column ? $row[$column] : $row);
            }
            return $rows;
        }
    }
    public function prepareData($justFirstRow = false) {
        return $this->isEmpty() || $this->num_rows == 0 ? null : ($justFirstRow ? $this->getRows() [0] : $this->getRows());
    }
    public function hasError() {
        $result = $this->prepareData(true);
        return !is_null($result) && isset($result['error']);
    }
    public function getError() {
        if ($this->hasError()) {
            return $this->prepareData(true) ['error'];
        }
    }
}
?>