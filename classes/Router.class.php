<?php
namespace Core {
	use \Exception as Exception;
	class Router {
		public static $routeList = array();
		public static function process() {
			
			$method = $_SERVER['REQUEST_METHOD'];
			$uri = Kernel::getRouterURI();
			$route_match = false;
			foreach(self::$routeList as $val) {
				if($val->method == $method) {
					$pattern = sprintf("/^%s\/?$/", str_replace("/", "\/", $val->pattern));
					if(preg_match_all($pattern, $uri, $matches)) {
						$callable = $val->callable;
						$args = array();
						for($i=1; isset($matches[$i]); $i++) {
							$args[] = $matches[$i][0];
						}
						$route_match = true;
						break;
					}
				}
			}
			if($route_match !== true)
				return new HTTPErrorObject(404);
		
			if(!isset($args)) $args = array();
			
			// when string, [appname].[methodname] format
			if(gettype($callable) === "string") {
				$arr = explode(".", $callable);
				$app = $arr[0];
				$method = $arr[1];
				$routeHandler = "controller";
			} else {
				$routeHandler = "closure";
			}
			
			$triggerOutput = Trigger::execute("Router.Before.Process", $output);
	        if($triggerOutput->isError()) {
	            $this->panic($triggerOutput);
	            return;
	        }
	        if($triggerOutput->terminated()) return $triggerOutput;
			
			// proc before controller
			if($routeHandler === "controller") {
				$beforeOutput = self::procController($app, "before");	
				if($beforeOutput->isError() || $beforeOutput->isHTTPError())
					return $beforeOutput;
			}
			// proc controller method
			if($routeHandler === "controller") {
				$output = self::procController($app, $method, $args);
				if($output->isError() || $output->isHTTPError())
					return $output;
			} else {
				$output = self::procClosure($callable, $args);
				if($output->isError() || $output->isHTTPError())
					return $output;
			}
			
			// proc after controller
			if($routeHandler === "controller") {
				$afterOutput = self::procController($app, "after");	
				if($afterOutput->isError() || $afterOutput->isHTTPError())
					return $afterOutput;
					
			}
			
			$triggerOutput = Trigger::execute("Router.After.Process", $output);
	        if($triggerOutput->isFatel()) {
	            $this->panic($triggerOutput);
	            return;
	        }
	        if($triggerOutput->terminated()) return $triggerOutput;
					
			return $output;
		}
		
		public static function procController($app, $method, $args = array()) {
			$controller = getController($app);
			if(!method_exists($controller, $method) && !$controller->__methodExists($method)) {
				throw new \Exception(sprintf("Method: '%s' not exists on '%sController' class", $method, $app));
			}
			$output = call_user_func_array(array($controller, $method), $args);
			if(!$output)
				$output = new ResourceObject();
				
			return $output;
		}

		public static function setRouteList($routeList) {
			self::$routeList = $routeList;
		}

		public static function getRouteList() {
			return self::$routeList;
		}
		
		public static function procClosure($callable, $args) {
			$output = call_user_func_array($callable, $args);
			if(!$output)
				$output = new ResourceObject();
	
			return $output;
		}
		
		public static function register($pattern, $method, $callable = NULL) {
			if($callable === NULL) {
				throw new \Exception(sprintf('Unable to register route: $callable must be a closure or string!'));
			}
			
			foreach(self::$routeList as $k=>$v) {
				if($v->equals($pattern, $method))
					unset(self::$routeList[$k]);
			}
			
			if(is_array($method)) {
				foreach($method as $key=>&$val) {
					self::register($pattern, $val, $callable);
				}
			} else {
				$oRouteObject = new RouteObject($pattern, $method, $callable);
				self::$routeList[] = $oRouteObject;
			}
		}
	}
}