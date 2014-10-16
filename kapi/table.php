<?php
	class KTable extends mysqli_result implements ArrayAccess
	{
		public function KTable($database)
		{
			@parent::__construct($database);
		}
	    public function offsetSet($offset, $value) {
	    	throw new exception("sqlresult setting!");
	    }
	    public function offsetExists($offset) {
	        return $this->num_rows-1 > $offset && $offset >= 0;
	    }
	    public function offsetUnset($offset) {
	    	throw new exception("sqlresult unsetting!");
	    }
	    public function offsetGet($offset) {
	    	$this->data_seek($offset);
	        return $this->fetch_array(MYSQLI_ASSOC);
	    }
		public function to_json($column=null,$row=-1)
		{
			if($this->num_rows == 0){
				return '[]';
			}
			$rows = $this->getRows($column);
			if($row >= 0){
				$rows = $rows[$row];				
			}
			return json_encode($rows, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK );
		}
		public function getRows($column=null){			
			$this->data_seek(0);
			$rows = array();
			while($row = $this->fetch_assoc()){
				$rows[] = ($column?$row[$column]:$row);
			}
			return $rows;
		}
	}
?>