<?php
namespace App\member {
	use \stdClass as stdClass;
	use \Core\Kernel as Kernel;
	use \Core\Trigger as Trigger;
	use \Core\CoreController as CoreController;
	use \Core\ResourceObject as ResourceObject;
	use \Core\QueryBuilder as QueryBuilder;
	
	class Controller extends CoreController
	{
		public function login() {
			// trigger for SSO
			Trigger::execute("member.login.sso");

			setView("login");
		}
		
		public function register() {
			throw new Exception("Member register not allowed!");
		}
		
		public function loginProc() {
			// set Response Type
			// Kernel::setContentType("application/json");
			
			$request = Kernel::getRequest();
			$response = Kernel::getResponse();
			$user_id = $request->getForm("user_id");
			$user_password = $request->getForm("user_password");
			
			if(!$user_id) {
				return new ResourceObject(-1, "아이디를 입력해주세요.");
			}
			if(!$user_password) {
				return new ResourceObject(-1, "비밀번호를 입력해주세요.");
			}
			
			$resp = new stdClass;
			
			$query = QueryBuilder::table('users')->where('user_id', '=', $user_id);
			$member = $query->first();

			if($query->count() == 0) {
				return new ResourceObject(-1, "아이디 혹은 비밀번호가 잘못되었습니다.");
			}

			// Hash 종류 확인
			switch($member->hash_type) {
				case 'MD5':
					$passwordString = md5($user_password);
					break;
				case 'SHA1':
					$passwordString = sha1($user_password);
					break;
				case 'SHA256':
					$passwordString = hash('sha256', $user_password);
					break;
				case 'SHA512':
					$passwordString = hash('sha512', $user_password);
					break;
				default:
					
					break;
			}

			if(strtolower($member->user_password) !== $passwordString) {
				return new ResourceObject(-1, "아이디 혹은 비밀번호가 잘못되었습니다.");
			}
			
			/* Login Success */
			$request->setSession("ss_user_srl", $member->user_srl);
			$output = new ResourceObject(0);
			$output->redirect_url = _VCPHOST_;
			
			return $output;
		}
		
		public function logout() {
			// trigger for SSO
			Trigger::execute("member.logout.sso");

			$request = Kernel::getRequest();
			$response = Kernel::getResponse();
			
			$request->setSession("ss_user_srl", NULL);
			$response->sendRedirect(_VCPHOST_);
		}
		
		public function before() {
			if(!$skin)
				$skin = 'default';

			$viewPath = _VCPROOT_ . "/views/member/";
			$viewSkinPath = $viewPath . $skin . '/';
			
			setViewPath($viewSkinPath);

			// remove layout
			setLayoutPath();
		}
		
		public function isAdmin() {
			$member = $this->getMemberInfo();
			if(!$member) return false;
			if($member->user_priv != 'ADMIN') return false;

			return true;
		}
		
		public function isLogged() {
			// trigger for SSO
			Trigger::execute("member.isLogged.sso");

			$request = Kernel::getRequest();
			if($request->getSession("ss_user_srl")) {
				return true;
			} else {
				return false;
			}
		}
		
		public function getMemberInfo($user_srl = NULL) {
			if(!$this->isLogged()) 
				return NULL;
			
			$request = Kernel::getRequest();
			if($user_srl == NULL) {
				$user_srl = $request->getSession("ss_user_srl");
			}

			// get Member Info
			$query = QueryBuilder::table('users')->where('user_srl', '=', $user_srl);
			return $query->first();
		}
	}
}
