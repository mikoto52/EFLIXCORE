<?php
namespace Core;
require_once(_VCPROOT_ . '/gplcode/htmLawed.php');

class DisplayHandler {
	public static $writer = "CGI";
	public static $useAbsPath = false;
	public static $attributes = NULL;

	public static function display($outputObj) {
		Trigger::execute("DisplayHandler.display.Before");
		
		if(self::$writer == "CLI") {
			echo sprintf("[%s][%s] ", date("Y-m-d H:i:s"), "Error");
			echo $outputObj->getMessage();
			echo "\n";
			return;
		}
		$response = Kernel::getResponse();
		
		/* Start of Compile View Template */
		if(!$outputObj) {
			
		}
		
		// if outputObject exists
		if($outputObj->isFatel() || $outputObj->isError()) {
			// Fatel Error Handling
			self::displayError($outputObj);
			setLayout();
			setLayoutPath();
		} else if($outputObj->isHTTPError()) {
			self::displayHTTPError($outputObj);
			$code = $outputObj->code;
			$message = $outputObj->codeㄴtable[$code];
			setLayout();
			setLayoutPath();
		}
		
		// process template engine & layout when contentType is 'text/html'
		if($response->contentType === 'text/html') {
			// Load Core JS/CSS statics
			self::loadCoreAssets();

			$viewFile = getView();
			$viewPath = getViewPath();
			
			if($response->useView()) {
				$oSygrion = sygrion::getInstance($viewPath);
				try {
					$content = $oSygrion->compile($viewFile);
				} catch(Exception $e) {
					$error = new ExceptionErrorObject(-1, $e->getMessage(), $e);
					Kernel::getInstance()->panic($error);
				}
			}
			
			Kernel::set("content", $content);
			
			/* End Of Compile View Template */
			
			/* Start of Compile Layout */
			$layoutFile = getLayout();
			$layoutPath = getLayoutPath();
			
			if($response->useLayout()) {
				if($layoutFile && $layoutPath) {
					$oSygrion = sygrion::getInstance($layoutPath);
					try {
						$responseBody = $oSygrion->compile($layoutFile);
					} catch(Exception $e) {
						$error = new ExceptionErrorObject(-1, $e->getMessage(), $e);
						Kernel::getInstance()->panic($error);
					}
				} else {
					$responseBody = $content;
				}
			} else {
				$responseBody = $content;
			}
			
			/* End of Compile Layout */
			$responseBody = \htmLawed($responseBody, array('tidy' => 5, 'parent'=>'html'));
			
			// Calibration for htmLawed
			$responseBody = preg_replace_callback("/\<script\s?type=[\"|']text\/javascript[\"|']\>.*?(&amp;).*?\<\/script\>/is", function($matches) {
				return html_entity_decode($matches[0]);
			}, $responseBody);
		}
		
		/* Start of Send Header */
		if($response->status != 200) 
		{
			header(sprintf("HTTP/1.1 %s %s", $response->status, $message));
		}
		foreach($response->headerstrings as $key=>$val) {
			header($val);
		}
		foreach($response->headers as $key=>$val) {
			header(sprintf("%s: %s", $key, $val));
		}
		// Send Content-Type Header
		header(sprintf("Content-Type: %s", $response->contentType));
		/* End of Send Header */
		
		/* Get HTML Header */
		if($response->contentType === 'text/html') {
			$response->responseBody = sprintf("%s%s\n%s", self::getHtmlHeader(), $responseBody, self::getHtmlFooter());
		} else if ($response->contentType == "application/json") {
			if($response->status === 200) {
				if(!$response->responseBody) {
					$output = $outputObj;
					unset($output->is_fatel);
					unset($output->is_exception);
					unset($output->is_http_error);
					$response->responseBody = json_encode($outputObj);
				}
			}
		} else if ($response->contentType == "text/xml" || $response->contentType == "application/xml") {
			if($response->status === 200) {
				if(!$response->responseBody) {
					$output = $outputObj;
					unset($output->is_fatel);
					unset($output->is_exception);
					unset($output->is_http_error);
					
					$response->responseBody = self::toXML($output);
				}
			}
		} else {
			
		}
		
		/* Display ResponseBody */
		
		echo $response->responseBody;
		
		Trigger::execute("DisplayHandler.display.After");
		
		// Shutdown the Kernel
		Kernel::Flush();
	}

	public static function setAttr($tag = 'html', $attr) {
		if(self::$attributes == NULL)
			self::$attributes = array();

		self::$attributes[$tag] = $attr;
	}
	
	public static function toXML($obj, $isRoot = true, $depth = 1) {
		$output = "";
		$subExists = false;
		if($isRoot == true)
			$output .= '<?xml version="1.0" encoding="UTF-8"?>' . "\n<response>\n";
		
		foreach($obj as $key=>$val) {
			if(is_object($val)) {
				$val2 = self::toXML($val, false, $depth + 1);
				if($val != $val2) $subExists = true;
				$val = $val2;
			}
			if(is_array($val)) {
				foreach($val as $val2) {
					$output .= sprintf("<%s>", $key);
					if(is_object($val2)) {
						$output .= self::toXML($val2, false, $depth + 1);
					} else {
						$output .= $val2;
					}
					$output .= sprintf("</%s>", $key);
					$output .= "\n";
				}
				continue;
			}
			
			$output .= sprintf("<%s>", $key);
			$output .= $val;
			$output .= sprintf("</%s>", $key);
			$output .= "\n";
		}
		if($isRoot == true)
			$output .= "</response>";
		
		return $output;
	}

	public static function getPath($path) {
		if(preg_match('[http:\/\/|https:\/\/]', $path)) return $path;

		$path = str_replace(getDocumentRoot(), "", $path);

		if(self::$useAbsPath == true)
			$path = 'http://' . $_SERVER['HTTP_HOST'] . $path;

		return $path;
	}

	public static function useAbsolutePath() {
		self::$useAbsPath = true;
	}
	
	public static function getHtmlHeader() {
		// Execute Trigger (Exception Base)
		Trigger::execute("DisplayHandler.getHtmlHeader.Before", array());
		
		$response = Kernel::getResponse();
		$output = "<!doctype html5>\n";
		$output .= "<html lang=\"ko\"";
		if(self::$attributes != NULL && self::$attributes['html'] != '') 
			$output .= " " . self::$attributes['html'];
		$output .= ">\n";
		$output .= "<head>\n";
		$output .= sprintf("<meta charset=\"%s\">\n", $response->charset);
		$output .= sprintf("<title>%s</title>\n", $response->title);
		// insert CSS Files
		foreach($response->css_files as $val) {
			$path = self::getPath($val);

			$output .= sprintf("<link rel=\"stylesheet\" type=\"text/css\" href=\"%s\" />\n", $path);
		}
		// insert JS Files
		foreach($response->js_files as $val) {
			$path = self::getPath($val);
			$output .= sprintf("<script type=\"text/javascript\" src=\"%s\"></script>\n", $path);
		}
		// insert html Headers
		foreach($response->htmlheaders as $key=>$val) {
			$output .= $val;
			$output .= "\n";
		}
		
		if(count($response->core_errors) > 0) {
			$output .= "<script type='text/javascript'>";
			foreach($response->core_errors as $val) {
				$output .= sprintf("console.log('%s');", addslashes($val));
			}
			$output .= "</script>";
		}
		$output .= "</head>\n";
		$output .= "<body";
		if(self::$attributes != NULL && self::$attributes['body'] != '') 
			$output .= " " . self::$attributes['body'];
		$output .= ">\n";
		
		// Execute Trigger (Exception Base)
		Trigger::execute("DisplayHandler.getHtmlHeader.After", array());
		
		return $output;
	}
	
	public static function getHtmlFooter() {
		$response = Kernel::getResponse();
		$output = "</body>\n";
		$output .= "</html>";
		
		return $output;
	}
	
	public static function displayError($obj) {
		if(!Kernel::isDebugMode()) {
			return self::displayHTTPError(new HTTPErrorObject(500));
		}
		Kernel::set("message", $obj->getMessage());
		Kernel::set("file", $obj->file);
		Kernel::set("line", $obj->line);
		$response = Kernel::getResponse();
		
		if($response->contentType === "text/html") {
			setViewPath(_VCPROOT_."/views/error/");
			setView("fatel_error");
			if($obj->isException()) {
				$trace = $obj->e->getTrace();
				if($trace[0]['class'])
					$method = sprintf("<b>%s::</b>", $trace[0]['class']);
					
				$method .= sprintf("<b>%s</b> on %s", $trace[0]['function'], $obj->e->getFile());
				Kernel::set("e", $obj->e);
				Kernel::set("file", $obj->e->getFile() );
				Kernel::set("line", $obj->e->getLine() );
				Kernel::set("method", $method );
				Kernel::set("trace", self::displayStackTrace($obj->e) );
			}
		}
	}
	
	public static function displayHTTPError($obj) {
		$code = $obj->code;
		$message = $obj->code_table[$code];
		$desc = $obj->desc_table[$code];
		$response = Kernel::getResponse();
		Kernel::setHTTPStatus($code);
		Kernel::setTitle($message);
		
		Kernel::set("code", $code);
		Kernel::set("message", $message);
		Kernel::set("desc", $desc);
		
		if($response->contentType === "text/html") {
			setViewPath(_VCPROOT_."/views/error/");
			setView("http_error");
		}
	}
	
	public static function displayStackTrace($e) 
	{
		$trace = $e->getTrace();
		$trace_str = sprintf("&nbsp;&nbsp;%s<br/>", $e->getMessage());
		foreach($trace as $k=>$v) {
			$trace_str .= sprintf("&nbsp;&nbsp;%s. <b>", $k+1);
			if($v['class'])
				$trace_str .= sprintf("%s::", $v['class']);
				
			$trace_str .= sprintf("%s</b>", $v['function']);
			
			if($v['file'])
				$trace_str .= sprintf(" on %s", $v['file']);
			else
				$trace_str .= sprintf(" on %s", $e->getFile());
				
			if($v['line'])
				$trace_str .= sprintf(":%s", $v['line']);
			else
				$trace_str .= sprintf(":%s", $e->getLine());
			
			$trace_str .= "<br/>";
		}
		return $trace_str;
	}
	
	public static function loadCoreAssets() {
		self::loadCoreCSS();
		self::loadCoreJS();
	}

	public static function loadCoreCSS() {
		$response = Kernel::getResponse();

		$response->addCSS(_VCPROOT_ . "assets/css/common.css", 0);
		$response->addCSS("https://fonts.googleapis.com/css?family=Open+Sans:400,700" , 0);
	}

	public static function loadCoreJS() {
		$response = Kernel::getResponse();

		$response->addJS(_VCPROOT_ . "assets/js/fontawesome-all.js", 0);
		$response->addJS(_VCPROOT_ . "assets/js/validator.js", 0);
		$response->addJS(_VCPROOT_ . "assets/js/base64.js", 0);
		$response->addJS(_VCPROOT_ . "assets/js/jquery-migrate-1.4.1.min.js", 0);
		$response->addJS(_VCPROOT_ . "assets/js/jquery-3.2.1.min.js", 0);
	}
}