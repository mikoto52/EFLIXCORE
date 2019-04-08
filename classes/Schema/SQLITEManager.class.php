<?php
namespace Schema {
	use \Core\Kernel as Kernel;
	use \Core\QueryBuilder as QueryBuilder;

	class SQLITEManager extends Manager {
		// Prefix
		public $qt = '`';
		public $sqt = "'";

		public function __construct() {
			parent::__construct();
		}

		public static function getInstance() {
			if(!self::$__INSTANCE)
				self::$__INSTANCE = new self();

			return self::$__INSTANCE;
		}

		public function createTable($table_name, $schema) {
			// process create Tables
			$qr = $this->__buildCreateQuery($table_name, $schema);
			if(is_array($qr)) {
				foreach($qr as $q)
					QueryBuilder::query($q);
			}
		}

		public function alterSchema($table_name, $schemas) {
			foreach($schemas->columns as $column) {
				if(!$this->columnExists($table_name, $column->name)) {
					$this->addSchema($table_name, $column);
				}
			}
		}

		public function addSchema($table_name, $column) {
			$query = 'ALTER TABLE [QT][table_name][QT] ADD COLUMN ';
			$query .= $this->__buildSchema($column);
			$query .= ';';
			$query = $this->buildQuery($query, array('table_name' => $this->getTableName($table_name)));

			QueryBuilder::query($query);
		}

		public function columnExists($table_name, $column_name) {
			/* $qr = 'SHOW COLUMNS FROM [QT][table_name][QT] LIKE [SQT][column_name][SQT];';
			$qr = $this->buildQuery($qr, array('table_name'=>$this->getTableName($table_name), 'column_name'=>$column_name));
			$output = QueryBuilder::query($qr)->get(); */
			$qr = "SELECT sql from sqlite_master where type='table' and name='[table_name]'";
			$qr = $this->buildQuery($qr, array('table_name' => $this->getTableName($table_name)));
			$result = QueryBuilder::query($qr)->get();
			if(empty($result)) 
				return false;
			$parser = new SQLiteParser\CreateTableParser();
			$def = $parser->parse($result[0]->sql);

			$ret = false;
			foreach($def->columns as $v) {
				if($v->name == $column_name) {
					$ret = true;
					break;
				}
			}

			return $ret;
		}

		public function checkSchema($table_name, $schemas) {
			foreach($schemas->columns as $column) {
				if(!$this->columnExists($table_name, $column->name))
					return false;
			}

			return true;
		}

		public function tableExists($table_name) {
			$qr = "SELECT * FROM sqlite_master where type='table' and name='[table_name]'";
			$qr = $this->buildQuery($qr, array('table_name' => $this->getTableName($table_name)));
			$result = QueryBuilder::query($qr)->get();
			if(!$result) {
				return false;
			}

			return true;
		}

		public function __buildSchema($column) {
			$v = $column;
			switch($v->data) {
				case 'number':
					$v->type = 'INTEGER';
					if(isset($v->sequence) && $v->sequence == true) 
						$v->type .= ' PRIMARY KEY AUTOINCREMENT';
					break;
				case 'blob':
					$v->type = 'BLOD';
					break;
				default:
					$v->type = 'TEXT';
					break;
			}

			$v->extra = '';
			if(isset($v->notnull) && $v->notnull == true) 
				$v->extra .= ' NOT NULL';
			else if(isset($v->notnull) && $v->notnull == false) 
				$v->extra .= ' NULL';
			if(isset($v->default) && $v->default == 'NULL') 
				$v->extra .= " DEFAULT NULL";
			else if(isset($v->default))
				$v->extra .= " DEFAULT '".$v->default."'";

			$v->qr = '[QT][name][QT] [type][extra]';
			$v->qr = $this->buildQuery($v->qr, array('name' => $v->name, 'type' => $v->type, 'extra' => $v->extra));
			
			return $v->qr;
		}

		public function __buildCreateQuery($table_name, $schema) {
			$columns = $schema->columns;
			$index = $schema->index;
			$query = 'CREATE TABLE IF NOT EXISTS [QT][table_name][QT] ';
			$query .= '(';
			foreach($columns as $v) {
				$v->query = $this->__buildSchema($v) . ', ';
				$query .= $v->query;
			}
			$query = substr($query, 0, -2);
			$query .= ');';

			$queries[] = $this->buildQuery($query, array('table_name' => self::getTableName($table_name)));

			foreach($index as $idx) {
				$query = 'ALTER TABLE [QT][table_name][QT]';
				if($idx->type == 'PRIMARY')
					$query .= 'ADD PRIMARY KEY ('.$idx->column.')';
				IF($idx->type == 'UNIQUE')
					$query .= 'ADD UNIQUE ('.$idx->column.')';
				$query .= ';';
				$queries[] = $this->buildQuery($query, array('table_name' => self::getTableName($table_name)));
			}
			return $queries;
		}
	}
}