<?php
/**
 * @files Base Model class that implements the ArrayAccess, Iterator,
 * and Countable interfaces
 * @author Will Steinmetz
 * --
 * Copyright (c)2010, Ralivue.com
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *		 * Redistributions of source code must retain the above copyright
 *			 notice, this list of conditions and the following disclaimer.
 *		 * Redistributions in binary form must reproduce the above copyright
 *			 notice, this list of conditions and the following disclaimer in the
 *			 documentation and/or other materials provided with the distribution.
 *		 * Neither the name of the Ralivue.com nor the
 *			 names of its contributors may be used to endorse or promote products
 *			 derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL Ralivue.com BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * --
 */
class Model implements ArrayAccess, Iterator, Countable {
	// public variables
	public $attributes = array();
	public $db_table;
	public $id_name;
	
	// private variables
	private $position;
	private $data = array();
	
	/**
	 * Constructor
	 * @param array $attributes
	 * @param string $db_table
	 */
	public function __construct($attributes, $db_table = null, $id_name = 'id') {
		$this->attributes = $attributes;
		$this->position = 0;
		if ($db_table != null) {
			$this->db_table = $db_table;
		}
		$this->id_name = $id_name;
	}
	
	/**
	 * Getter
	 * @param string/integer $key
	 * @return unknown_type
	 */
	public function __get($key) {
		return $this->data[0]->$key;
	}

	/**
	 * Setter
	 * @param string/integer $key
	 * @param unknown_type $value
	 * @return boolean
	 */
	public function __set($key, $value) {
		if (count($this->data) == 0) {
			$temp = new stdClass();
			$this->data[] = $temp;
		}
		if (in_array($key, $this->attributes)) {
			$this->data[0]->$key = $value;
			return true;
		} else if ($key == $this->id_name) {
			$this->data[0]->id = $value;
		} else if ($key == 'updated') {
			$this->data[0]->updated = $value;
		} else if ($key == 'created') {
			$this->data[0]->created = $value;
		}
		return false;
	}
	
	/**
	 * Implements ArrayAccess::offsetSet
	 * @param $offset
	 * @param $value
	 */
	public function offsetSet($offset, $value) {
		$this->data[$offset] = $value;
	}
	
	/**
	 * Implements ArrayAccess::offsetExists
	 * @param $offset
	 * @return unknown_type
	 */
	public function offsetExists($offset) {
		return isset($this->data[$offset]);
	}
	
	/**
	 * Implements ArrayAccess::offsetUnset
	 * @param $offset
	 * @return unknown_type
	 */
	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}
	
	/**
	 * Implements ArrayAccess::offsetGet
	 * @param $offset
	 * @return unknown_type
	 */
	public function offsetGet($offset) {
		return isset($this->data[$offset]) ? $this->data[$offset] : null;
	}
		
	/**
	 * Implements Iterator::rewind
	 */
	public function rewind() {
		$this->position = 0;
	}
		
	/**
	 * Implements Iterator::current
	 * $return unknown_type
	 */
	public function current() {
		return $this->data[$this->position];
	}
		
	/**
	 * Implements Iterator::key
	 * @return unknown_type
	 */
	public function key() {
		return $this->position;
	}
		
	/**
	 * Implements Iterator::next
	 */
	public function next() {
		++$this->position;
	}
		
	/**
	 * Implements Iterator::valid
	 * @return boolean
	 */
	public function valid() {
		return isset($this->data[$this->position]);
	}
		
	/**
	 * Implements Countable::count
	 * @return integer
	 */
	public function count() {
	 return count($this->data);
	}
		
	/**
	 * Finds a given value or collection in the database
	 * @param string/integer $query
	 * @param integer $limit
	 * @param array $where
	 * @param array $order
	 * @param integer $start
	 * @return boolean
	 */
	public function find($query = null, $limit = null, $where = null, $order = null, $start = null) {
		global $db;
		$inputs = array();
		$fields = array_merge(array($this->id_name), $this->attributes, array('created', 'updated'));

		if (is_numeric($query)) {
			$q = "SELECT " . implode(', ', $fields) . " FROM {$this->db_table} WHERE {$this->id_name} = ? LIMIT 1;";
			$inputs[] = $query;
		} else if ($query == 'first') {
			$q = "SELECT " . implode(', ', $fields) . " FROM {$this->db_table} ORDER BY {$this->id_name} LIMIT 1;";
		} else if ($query == 'last') {
			$q = "SELECT " . implode(', ', $fields) . " FROM {$this->db_table} ORDER BY {$this->id_name} DESC LIMIT 1;";
		} else {
			$q = "SELECT " . implode(', ', $fields) . " FROM {$this->db_table} ";
			if ($where != null) {
				$w = $this->build_where_prepared($where);
				$q .= $w[0];
				$inputs = array_merge($inputs, $w[1]);
			}
			$q .= "ORDER BY ";
			if ($order != null) {
				$x = 0;
				foreach($order as $field => $dir) {
					$q .= " {$field} {$dir}";
					if ($x < (count($order) - 1)) {
						$q .= ", ";
					}
					$x++;
				}
			} else {
				$q .= " {$this->id_name}";
			}
			if ($limit != null) {
				if (is_numeric($limit)) {
					if (($start != null) && (is_numeric($start))) {
						$q .= " LIMIT {$start}, {$limit}";
					} else {
						$q .= " LIMIT {$limit}";
					}
				}
			}
			$q .= ";";
		}
		$results = $db->prepare_select($q, $fields, $inputs, $this->attributes);
		
		if ($db->num_rows == 0) {
			return FALSE;
		} else {
			$this->data = $results;
			return TRUE;
		}
	}
	
	/**
	 * Destroys a given value in the database
	 * @param integer $index
	 * @return integer/boolean
	 */
	public function destroy($index = NULL) {
		global $db;
		if (is_numeric($index) && ($index < count($this->data))) {
			$sql = "DELETE FROM {$this->db_table} WHERE {$this->id_name} = ? LIMIT 1;";
			$db->prepare_cud($sql, array($this->data[$index]->id));
			return $db->affected_rows;
		} else if ($index == NULL) {
			$sql = "DELETE FROM {$this->db_table} WHERE {$this->id_name} = ? LIMIT 1;";
			$db->prepare_cud($sql, array($this->data[0]->id));
			return $db->affected_rows;
		} else {
			return false;
		}
	}
	
	/**
	 * Inserts object $this->data[0] into the database
	 * @return integer
	 */
	public function create() {
		global $db;
		
		$attributes = '';
		foreach ($this->attributes as $attribute) {
			$attributes .= "{$attribute}, ";
		}
		$attributes .= "created, updated";
		$this->data[0]->created = date('Y-m-d G:i:s');
		$this->data[0]->updated = date('Y-m-d G:i:s');
		$valuesStr = '';
		for ($i = 0; $i < (count($this->attributes) + 2); $i++) {
			$valuesStr .= '?';
			if ($i < (count($this->attributes) + 1)) {
				$valuesStr .= ', ';
			}
		}
		if (get_magic_quotes_gpc()) {
			foreach ($this->attributes as $attribute) {
				if ($this->data[0]->$attribute != null) {
					$this->data[0]->$attribute = stripslashes($this->data[0]->$attribute);
				}
			}
		}
		$data = array();
		foreach ($this->attributes as $attribute) {
			if ($this->data[0]->$attribute != null) {
				array_push($data, $this->data[0]->$attribute);
			} else {
				array_push($data, '');
			}
		}
		$values = array_merge($data, array($this->data[0]->created, $this->data[0]->updated));
		$sql = "INSERT INTO {$this->db_table} ({$attributes}) VALUES ({$valuesStr})";
		
		if ($db->prepare_cud($sql, $values)) {
			return $db->insert_id;
		}
		return FALSE;
	}
	
	/**
	 * Inserts object $this->data[$index] into the database
	 * @param integer $index
	 * @return integer
	 */
	public function createAt($index) {
		global $db;
		
		$attributes = '';
		foreach ($this->attributes as $attribute) {
			$attributes .= "{$attribute}, ";
		}
		$attributes .= "created, updated";
		$this->data[$index]->created = date('Y-m-d G:i:s');
		$this->data[$index]->updated = date('Y-m-d G:i:s');
		$valuesStr = '';
		for ($i = 0; $i < (count($this->attributes) + 2); $i++) {
			$valuesStr .= '?';
			if ($i < (count($this->attributes) + 1)) {
				$valuesStr .= ', ';
			}
		}
		if (get_magic_quotes_gpc()) {
			foreach ($this->attributes as $attribute) {
				if ($this->data[$index]->$attribute != null) {
					$this->data[$index]->$attribute = stripslashes($this->data[$index]->$attribute);
				}
			}
		}
		$data = array();
		foreach ($this->attributes as $attribute) {
			if ($this->data[$index]->$attribute != null) {
				array_push($data, $this->data[$index]->$attribute);
			} else {
				array_push($data, '');
			}
		}
		$values = array_merge($data, array($this->data[$index]->created, $this->data[$index]->updated));
		$sql = "INSERT INTO {$this->db_table} ({$attributes}) VALUES ({$valuesStr})";
		
		if ($db->prepare_cud($sql, $values)) {
			return $db->insert_id;
		}
		return FALSE;
	}
	
	/**
	 * Updates the value of $this->data[0] in the database
	 * @return integer
	 */
	public function update() {
		global $db;

		$attributes = '';
		foreach ($this->attributes as $attribute) {
			$attributes .= "{$attribute}, ";
		}
		$attributes .= "created, updated";
		$this->data[0]->updated = date('Y-m-d G:i:s');
		$valuesStr = '';
		for ($i = 0; $i < count($this->attributes); $i++) {
			$valuesStr .= $this->attributes[$i] . ' = ?, ';
		}
		$valuesStr .= 'created = ?, updated = ?';
		if (get_magic_quotes_gpc()) {
			foreach ($this->attributes as $attribute) {
				if ($this->data[0]->$attribute != null) {
					$this->data[0]->$attribute = stripslashes($this->data[0]->$attribute);
				}
			}
		}
		$data = array();
		foreach ($this->attributes as $attribute) {
			if ($this->data[0]->$attribute != null) {
				array_push($data, $this->data[0]->$attribute);
			} else {
				array_push($data, '');
			}
		}
		$values = array_merge($data, array($this->data[0]->created, $this->data[0]->updated, $this->data[0]->id));
		$sql = "UPDATE {$this->db_table} SET {$valuesStr} WHERE {$this->id_name} = ?";
		
		$db->prepare_cud($sql, $values);
		return $db->affected_rows;
	}
	
	/**
	 * Updates the value of $this->data[$index] in the database
	 * @param integer $index
	 * @return integer
	 */
	public function updateAt($index) {
		global $db;

		$attributes = '';
		foreach ($this->attributes as $attribute) {
			$attributes .= "{$attribute}, ";
		}
		$attributes .= "created, updated";
		$this->data[$index]->updated = date('Y-m-d G:i:s');
		$valuesStr = '';
		for ($i = 0; $i < count($this->attributes); $i++) {
			$valuesStr .= $this->attributes[$i] . ' = ?, ';
		}
		$valuesStr .= 'created = ?, updated = ?';
		if (get_magic_quotes_gpc()) {
			foreach ($this->attributes as $attribute) {
				if ($this->data[$index]->$attribute != null) {
					$this->data[$index]->$attribute = stripslashes($this->data[0]->$attribute);
				}
			}
		}
		$data = array();
		foreach ($this->attributes as $attribute) {
			if ($this->data[$index]->$attribute != null) {
				array_push($data, $this->data[$index]->$attribute);
			} else {
				array_push($data, '');
			}
		}
		$values = array_merge($data, array($this->data[$index]->created, $this->data[$index]->updated, $this->data[$index]->id));
		$sql = "UPDATE {$this->db_table} SET {$valuesStr} WHERE {$this->id_name} = ?";
		
		$db->prepare_cud($sql, $values);
		return $db->affected_rows;
	}
	
	/**
	 * Builds where clause for query
	 * @param array $where
	 * @return string
	 */
	private function build_where($where) {
		global $db;
		
		if (count($where) > 0) {
			$w = '';
			foreach ($where as $clause) {
				list($f, $o, $t, $n) = $clause;
				$w .= $db->where($f, $o, $t, $n) . ' ';
			}
			return 'WHERE ' . $w . " ";
		}
	}
	
	/**
	 * Builds where clause for query for prepared statemens
	 * @param unknown_type $where
	 * @return string
	 */
	private function build_where_prepared($where) {
		global $db;
		$inputs = array();
		
		if (count($where) > 0) {
			$w = '';
			foreach ($where as $clause) {
				list($f, $o, $t, $n) = $clause;
				$w .= $db->where_prepared($f, $o, $n) . ' ';
				$inputs[] = $t;
			}
			return array('WHERE ' . $w . " ", $inputs);
		}
	}
	
	/**
	 * Fills values from an array into $this->data
	 * @param array $arr
	 */
	public function fill($arr) {
		for ($i = 0; $i < count($arr); $i++) {
			$temp = new stdClass();
			foreach ($arr[$i] as $key => $val) {
				$temp->$key = $val;
			}
			$this->data[] = $temp;
		}
	}
	
	/**
	 * Remove element from collection at given index
	 * @param $index
	 * @return object/boolean
	 */
	public function remove($index) {
		if ($index == 0) {
			return array_shift($this->data);
		} else if ($index < $this->count()) {
			$r = $this->data[$index];
			array_splice($this->data, $index, 1);
			return $r;
		}
		return FALSE;
	}
	
	/**
	 * Adds an element to the collection
	 * @param object $obj
	 * @param integer $index
	 */
	public function add($obj, $index = null) {
		if ($index == null) {
			$this->data[] = $obj;
		} else {
			$this->data[$index] = $obj;
		}
	}
}