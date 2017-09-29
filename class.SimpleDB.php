<?php

/**
 * SimpleDB: A very simple file based database written in PHP.
 *
 * Copyright (c) 2017 Sei Kan
 *
 * Distributed under the terms of the MIT License.
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright  2017 Sei Kan <seikan.dev@gmail.com>
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * @see       https://github.com/seikan/SimpleDB
 */
class SimpleDB
{
	const ASC = 1;
	const DESC = 2;
	const TYPE_INT = 'int';
	const TYPE_STR = 'str';
	const TYPE_DATE = 'date';

	protected $separator = ';';
	protected $types = [
		self::TYPE_INT, self::TYPE_STR, self::TYPE_DATE,
	];

	/**
	 * Database file.
	 *
	 * @var string
	 */
	private $database;

	/**
	 * Field name for indexing purpose.
	 *
	 * @var string
	 */
	private $indexKey = null;

	/**
	 * Headers of the database.
	 *
	 * @var array
	 */
	private $headers = [];

	/**
	 * Collection of records.
	 *
	 * @var array
	 */
	private $container = [];

	/**
	 * Total of column in database.
	 *
	 * @var int
	 */
	private $totalColumn = 0;

	/**
	 * Total rows of record.
	 *
	 * @var int
	 */
	private $totalRow = 0;

	/**
	 * Affected rows after a query.
	 *
	 * @var int
	 */
	private $affectedRows = 0;

	/**
	 * Initialize SimpleDB.
	 *
	 * @param string $database
	 *
	 * @throws \Exception
	 */
	public function __construct($database)
	{
		if (!file_exists($database)) {
			@touch($database);
		}

		if (!is_writable($database)) {
			throw new Exception('"'.$database.'" is not writable.');
		}

		$this->database = $database;
		$this->read();
	}

	public function __destruct()
	{
	}

	/**
	 * Check if database created.
	 *
	 * @return bool
	 */
	public function isCreated()
	{
		return $this->read();
	}

	/**
	 * Create a new database.
	 *
	 * @param array $fields
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	public function create($fields = [])
	{
		if ($this->read()) {
			throw new Exception('Database already created in "'.$this->database.'".');
		}

		if (!is_array($fields)) {
			throw new Exception('$fields is not an array.');
		}

		if (empty($fields)) {
			throw new Exception('$fields is empty.');
		}

		$this->headers = [];
		$this->container = [];

		foreach ($fields as $field => $type) {
			if (!in_array($type, $this->types)) {
				throw new Exception('Invalid data type "'.$type.'".');
			}
			$this->headers[$field] = $type;
		}

		$this->totalColumn = count($this->headers);

		return $this->commit();
	}

	/**
	 * Set a field as indexing key.
	 *
	 * @param string $field
	 *
	 * @throws \Exception
	 */
	public function setIndexKey($field)
	{
		if (!isset($this->headers[$field])) {
			throw new Exception('"'.$field.'" column not found.');
		}

		if (self::TYPE_INT != $this->headers[$field]) {
			throw new Exception('"'.$field.'" column is not a type of interger.');
		}

		$this->indexKey = $field;
	}

	/**
	 * Insert record into database.
	 *
	 * @param array $data
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	public function insert($fields)
	{
		if (!is_array($fields)) {
			throw new Exception('$fields is not an array.');
		}

		if (empty($fields)) {
			throw new Exception('$fields is empty.');
		}

		$newItem = [];

		foreach ($this->headers as $name => $type) {
			$newItem[$name] = (isset($fields[$name])) ? $this->parse($type, $fields[$name]) : '';
		}

		if (null != $this->indexKey) {
			if (!isset($fields[$this->indexKey])) {
				$newItem[$this->indexKey] = 1;

				foreach ($this->container as $row) {
					if ($row[$this->indexKey] >= $newItem[$this->indexKey]) {
						$newItem[$this->indexKey] = $row[$this->indexKey] + 1;
					}
				}
			} else {
				foreach ($this->container as $row) {
					if ($row[$this->indexKey] == $fields[$this->indexKey]) {
						return $this->commit();
					}
				}
			}
		}
		array_push($this->container, $newItem);

		return $this->commit();
	}

	/**
	 * Fetch records from database.
	 *
	 * @param string $column
	 * @param string $needle
	 * @param string $orderBy
	 * @param int    $sort
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public function select($column = '*', $needle = '*', $orderBy = '', $sort = self::ASC)
	{
		$this->affectedRows = $this->totalRow;

		if (in_array($orderBy, array_keys($this->headers))) {
			$this->container = $this->sort($this->container, $orderBy, $sort);
		}

		if ('*' == $needle) {
			return $this->container;
		}

		$result = [];

		if ('*' == $column) {
			foreach ($this->container as $row) {
				if (preg_match('/'.$this->getNeedle($needle).'/i', implode('', $row))) {
					array_push($result, $row);
				}
			}
		} else {
			foreach ($this->container as $row) {
				if (preg_match('/'.$this->getNeedle($needle).'/i', $row[$column])) {
					array_push($result, $row);
				}
			}
		}

		$this->affectedRows = count($result);

		return $result;
	}

	/**
	 * Update record.
	 *
	 * @param string $column
	 * @param string $needle
	 * @param array  $fields
	 *
	 * @return bool
	 */
	public function update($column, $needle, $fields)
	{
		$this->affectedRows = 0;

		if (!is_array($fields)) {
			throw new Exception('$fields is not an array.');
		}

		for ($i = 0; $i < $this->totalRow; ++$i) {
			if (isset($this->container[$i][$column]) && preg_match('/'.$this->getNeedle($needle).'/i', $this->container[$i][$column])) {
				++$this->affectedRows;

				foreach ($this->headers as $name => $type) {
					if (isset($fields[$name])) {
						$this->container[$i][$name] = $this->parse($type, $fields[$name]);
					}
				}
			}
		}

		return $this->commit();
	}

	/**
	 * Delete record.
	 *
	 * @param string $column
	 * @param string $needle
	 *
	 * @return bool
	 */
	public function delete($column, $needle)
	{
		$tmp = [];

		for ($i = 0; $i < $this->totalRow; ++$i) {
			if (!isset($this->container[$i][$column]) || !preg_match('/'.$this->getNeedle($needle).'/i', $this->container[$i][$column])) {
				array_push($tmp, $this->container[$i]);
			}
		}

		$this->affectedRows = count($this->container) - count($tmp);
		$this->container = $tmp;

		return $this->commit();
	}

	/**
	 * Get affected rows.
	 *
	 * @return int
	 */
	public function affectedRows()
	{
		return $this->affectedRows;
	}

	/**
	 * Get the last insert ID.
	 *
	 * @return int
	 */
	public function getLastId()
	{
		if (empty($this->container)) {
			return 0;
		}

		return end($this->container)[$this->indexKey];
	}

	/**
	 * Convert text into proper regular expression.
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	private function getNeedle($text)
	{
		if ('=' == substr($text, 0, 1)) {
			return '^'.preg_replace('/([.+^$?\[\]])/', '\\\$1', substr($text, 1)).'$';
		}

		return $text;
	}

	/**
	 * Parse field value based on type.
	 *
	 * @param string $type
	 * @param string $value
	 *
	 * @return string
	 */
	private function parse($type, $value)
	{
		switch ($type) {
			case self::TYPE_INT:
				if (!preg_match('/^[0-9]+$/', $value)) {
					return 0;
				}
				break;

			case self::TYPE_DATE:
				if ('NOW()' == strtoupper($value)) {
					return date('Y-m-d H:i:s');
				}

				if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
					return null;
				}
				break;
		}

		return $value;
	}

	/**
	 * Sort result by column.
	 *
	 * @param array  $array
	 * @param string $index
	 * @param int    $order
	 *
	 * @return bool
	 */
	private function sort($array, $index, $order = self::ASC)
	{
		if (is_array($array) && count($array) > 0) {
			foreach (array_keys($array) as $key) {
				$temp[$key] = $array[$key][$index];
			}

			(self::ASC == $order) ? asort($temp) : arsort($temp);

			foreach (array_keys($temp) as $key) {
				(is_numeric($key)) ? $sorted[] = $array[$key] : $sorted[$key] = $array[$key];
			}

			return $sorted;
		}

		return $array;
	}

	/**
	 * Read database file.
	 *
	 * @return bool
	 */
	private function read()
	{
		$this->container = [];
		$item = [];

		$fp = fopen($this->database, 'r');

		if ($fp) {
			$headers = fgetcsv($fp, 2048, $this->separator);

			if (false === $headers) {
				return false;
			}

			$this->totalColumn = count($headers);

			$this->headers = [];
			foreach ($headers as $column) {
				$this->headers[substr($column, 0, strpos($column, '['))] = substr($column, strpos($column, '[') + 1, -1);
			}

			$keys = array_keys($this->headers);

			while ($buffer = fgetcsv($fp, 2048, $this->separator)) {
				for ($i = 0; $i < $this->totalColumn; ++$i) {
					$item[$keys[$i]] = str_replace('\\"', '"', $buffer[$i]);
				}

				array_push($this->container, $item);
			}
			fclose($fp);
		}

		$this->totalRow = count($this->container);

		return true;
	}

	/**
	 * Save changes to database.
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	private function commit()
	{
		reset($this->container);

		$keys = array_keys($this->headers);

		$out = '';

		foreach ($this->headers as $name => $type) {
			$out .= $name.'['.$type.']'.$this->separator;
		}

		$out = rtrim($out, $this->separator)."\n";

		if (!empty($this->container)) {
			foreach ($this->container as $row) {
				$item = [];

				for ($i = 0; $i < $this->totalColumn; ++$i) {
					$item[$keys[$i]] = str_replace('"', '\\"', $row[$keys[$i]]);
				}

				$out .= '"'.implode('"'.$this->separator.'"', $item)."\"\n";
			}
		}

		if ($this->indexKey) {
			$this->container = $this->sort($this->container, $this->indexKey);
		}

		$this->totalRow = count($this->container);

		return $this->write($out);
	}

	/**
	 * Write changes into database file.
	 *
	 * @param string $text
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	private function write($text)
	{
		if (!is_writable($this->database)) {
			throw new Exception('"'.$this->database.'" is not writable.');
		}

		$fp = @fopen($this->database, 'w');

		if ($fp) {
			flock($fp, 2);
			fputs($fp, $text);
			flock($fp, 3);
			fclose($fp);

			return true;
		}

		return false;
	}
}
