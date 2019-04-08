<?php
namespace Core {
	use \PDO as PDO;
	
	class MySQLDatabase extends CoreDatabase {
		public $connection = NULL;
		
		public function __construct($host, $id, $password, $name) {
			$this->connection = new PDO(sprintf("mysql:host=%s;dbname=%s", $host, $name), $id, $password);
			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			$this->connection->query("SET NAMES UTF8;");
		}
		
		public function count($str, $args = NULL) {
			$str = preg_replace("/\\s?SELECT \* /msi", "SELECT COUNT(*) as cnt ", $str, 1);
			
			$stmt = $this->connection->prepare($str);
			if($args != NULL) {
				foreach($args as $k=>$v) $stmt->bindParam(":".$k, $v);
			}
			$stmt->execute();
			$output = $stmt->fetchObject();
			return (int)$output->cnt;
		}
		
		public function prepare($str) {
			$stmt = $this->connection->prepare($str);
			
			return $stmt;
		}
		
		public function fetch($stmt = NULL) {
			if($stmt === NULL) {
				return null;
			}
			
			return $stmt->fetchObject();
		}
		
		public function fetchAll($stmt = NULL) {
			if($stmt === NULL) {
				return null;
			}
			
			return $stmt->fetchAll(PDO::FETCH_CLASS);
		}
		
		public function update($table, $cond, $args) {
			$setQr = "";
			$whereQr = $cond;
			foreach($args as $key=>$val) {
				$setQr .= sprintf("`%s` = :%s, ", $key, $key);
			}
			$setQr = substr($setQr, 0, -2);
			
			$query = sprintf("UPDATE `%s` SET %s where %s", $table, $setQr, $whereQr);
			
			$stmt = $this->connection->prepare($query);
			foreach($args as $key=>$val) {
				$stmt->bindValue(sprintf(":%s", $key), $val);
			}
			$stmt->execute();
			
			return $stmt;
		}
		
		public function insert($table, $args) {
			$fieldQr = "";
			$valueQr = "";
			foreach($args as $key=>$val) {
				$fieldQr .= sprintf("`%s`, ", $key);
				$valueQr .= sprintf(":%s, ", $key);
			}
			$fieldQr = substr($fieldQr, 0, -2);
			$valueQr = substr($valueQr, 0, -2);
			
			$query = sprintf("INSERT INTO `%s` (%s) VALUES (%s);", $table, $fieldQr, $valueQr);
			
			$stmt = $this->connection->prepare($query);
			foreach($args as $key=>$val) {
				$stmt->bindValue(sprintf(":%s", $key), $val);
			}
			$stmt->execute();
			
			return $stmt;
		}
	}
}