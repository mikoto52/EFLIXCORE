<?php
/****
 * For Realiser Admin
 ****/
namespace App\member {
	use App\realiser\admin as Admin;
	use App\realiser\menu as Menu;
	use Core\Kernel as Kernel;
	use \stdClass as stdClass;
	class realiser extends \App\realiser\RealiserObject {

		public function member_settings() {

			$this->setUI("member_settings");
		}

		public function getMemberList($columns, $order, $draw, $start = NULL, $limit = NULL) {
			if(!$start) $start = 0;
			if(!$limit) $limit = 25;

			$table = 'vs_users';
			$dbConn = getDBConn();
			$baseQr = sprintf(" SELECT * FROM %s WHERE 1 ", $table);
			$total = $dbConn->count($baseQr);
			$this->draw = $draw;
			$this->recordsTotal = $total;
			$this->recordsFiltered = $total;
			$cols = array();
			foreach($columns as $v) $v['name']? $cols[] = $v['name']:'';
			
			$selectQr = sprintf(" SELECT %s from %s WHERE 1 ORDER BY %s %s LIMIT %d,%d ", implode($cols, ','), $table, 'user_srl', $order[0]['dir'], $start, $limit);
			$stmt = $dbConn->prepare($selectQr);
			$stmt->execute();
			$this->data = $dbConn->fetchAll($stmt);
		}

		public function ajax($action = NULL) {
			$output = new \Core\ResourceObject();
			switch($action) {
				case 'member_list':
					$limit = POST('limit');
					$draw = POST('draw');
					$start = POST('start');
					$columns = POST('columns');
					$order = POST('order');
					return $this->getMemberList($columns, $order, $draw, $start, $limit);
					break;
			}
		}
		
		public function manage($srl = NULL) {
			$oDB = getDBConn();
			if(!$srl || $srl == '') {
				$stmt = $oDB->prepare(" SELECT * FROM `vs_users` ORDER BY `user_srl` DESC ");
				$stmt->execute();
				$data = $oDB->fetchAll($stmt);
				$idx = count($data);
				foreach($data as $k=>&$v) {
					unset($v->user_password);
					$v->idx = $idx;
					$idx--;
				}

				Kernel::set('data', $data);

				$this->setUI("member_list");
			} else {
				if($this->method == 'POST') {
					$args = new stdClass;
					$args->hash_type = POST('hash_type');
					if(POST('user_password') && POST('user_password2')){
						$args->user_password = POST('user_password');
						switch($args->hash_type) {
							case "SHA512":
								$args->user_password = hash("SHA512", $args->user_password);
								break;
							case "SHA256":
								$args->user_password = hash("SHA256", $args->user_password);
								break;
							case "MD5":
								$args->user_password = hash("MD5", $args->user_password);
								break;
							case "SHA1":
								$args->user_password = hash("SHA1", $args->user_password);
								break;
							default:

								break;
						}
					}
					$args->user_name = POST('user_name');
					$args->user_nickname = POST('user_nickname');
					$args->user_email = POST('user_email');
					$args->user_srl = $srl;

					$stmt = $oDB->update('vs_users', " user_srl = :user_srl ", $args);

					return sendRedirect(getUrl());
				} else if($this->method == 'GET') {
					$stmt = $oDB->prepare(" SELECT * FROM `vs_users` WHERE `user_srl` = :user_srl ");
					$stmt->bindParam(':user_srl', $srl);
					$stmt->execute();
					$member = $stmt->fetchObject();

					Kernel::set('member', $member);
					$this->setUI("member_form");
				} else {
					return new \Core\HTTPErrorObject(404);
				}
			}
		}
		
		/* Register Functions */
		public static function register() {
			$adminMenu = Menu::getDefaultMenu();

			$adminMenu->addMenu(
				Menu::createMenu("", "회원", "#")->addMenu(
					Menu::createMenu("member.member_settings", "회원 설정"),
					Menu::createMenu("member.manage", "회원 목록")
				)
			);
		}
	}
}
