<?php
namespace Core{
	/********************************************************
	 * Sygrion Enhanced Version (for SFCore)
	 ********************************************************/
	class sygrion
	{
		private static $instance = NULL;
		public $xe_compatable = true;
		public $template_path = "";
		public $template_file = "";
		public $default_ext = "stpl";
		public $default_namespace = "";
		public $alias_list = array();
		public $css = NULL;
		public $js = NULL;
		
		public static function getInstance($template_path = NULL) 
		{
			/* 
			 * getInstance method removed
			if(self::$instance === NULL)
				self::$instance = new sygrion;
				
			return self::$instance; */
			return new sygrion($template_path);
		}
		
		/* For support Include */
		public function setTemplatePath($template_path) {
			$this->template_path = $template_path;
		}
	
		public function __construct($template_path)
		{
			$this->setTemplatePath($template_path);
		}
	
		public function display($template_file)
		{
			echo $this->compile($template_file);
		}
		
		public function errorHandler() {
			$error = error_get_last();
			print_r($error);
			exit;
		}
	
		public function compile($template_file)
		{
			if(!preg_match("/(.stpl|.html|.tpl)/i", $template_file)) {
				$template_file = sprintf("%s.%s", $template_file, $this->default_ext);
			}
			$this->template_file = $template_file;

			$fullPath = sprintf("%s%s", $this->template_path, $this->template_file);
			if(!is_file($fullPath))
				throw new \Exceptions(sprintf("<br/><b>Sygrion Template Error</b>: Unable to compile template file '%s' (sygrion: no such file or directory)", $fullPath));

			// use CacheEngine
			$oCacheEngine = \Core\CacheEngine::getInstance();
			$cacheKey = 'Sygrion_'.md5($fullPath);
			$mtime = filemtime($fullPath);
			$cacheData = $oCacheEngine->getTemplateCache($cacheKey, $mtime);
			if($cacheData) {
				$buff = $cacheData->stpl;
				foreach($cacheData->css as $v) 
					$this->addCSS($v);
				
				foreach($cacheData->js as $v) 
					$this->addJS($v);
			}
			
			// Template data not exists, parse & create cache
			if(!$buff) {
				$buff = $this->parse();
				$oCacheEngine->generateTemplateCache($cacheKey, $buff, $mtime, $this->css, $this->js);
			}

			ob_start();
			$success = $this->execute($buff);
			$output = ob_get_contents();
			ob_end_clean();
			return $output;
		}
		
		public function addClassAlias($cl, $al) {
			$this->alias_list[] = array('alias' => $al, 'class' => $cl);
		}
		
		public function execute($buff)
		{
			$this->addClassAlias("\Core\Kernel", "Kernel");
			// inject Kernel as Default
			if($this->default_namespace != '') {
				$pre = sprintf("namespace %s { ", $this->default_namespace);
			} else {
				$pre = "";
			}
			foreach($this->alias_list as $v) {
				$pre .= sprintf("use %s as %s;\n", $v['class'], $v['alias']);
			}
			$pre .= "?>";
			$buff = $pre . $buff;
			if($this->default_namespace != '')
				$buff .= '<?php }';
			// echo $buff;exit;

			return eval($buff);
		}
	
		public function parse() {
			$fullPath = sprintf("%s%s", $this->template_path, $this->template_file);
	
			if(is_file($fullPath)) {
				$compiled = file_get_contents($fullPath);
				return $this->__parse_template($compiled);
			} else {
				throw new \Exceptions(sprintf("<br/><b>Sygrion Template Error</b>: Unable to compile template file '%s' (sygrion: no such file or directory)", $fullPath));
			}
		}
	
		public function __parse_template($compiled)
		{
			$compiled = $this->parseIncludeImport($compiled);
			
			// compile inline php script
			$compiled = preg_replace_callback("/\{\{@\s?(.*)\s\}\}/imsU", array($this, "_replaceInlinePHP"), $compiled);
	
			// replace comments
			$compiled = preg_replace('@<!--//.*?-->@s', '', $compiled);
	
			// compile if phrase
			$if_rule = array("/@if\s?\((.*)\)/i");
			foreach($if_rule as $rule) {
				$compiled = preg_replace_callback($rule, function($match) {
					$output = "<?php if(" . $match[1].") { ?>";
					$output = Sygrion::_replaceVar($output);
					return $output;
				}, $compiled);
			}
	
			// compile if phrase
			$elseif_rule = array("/@elseif\s?\((.*)\)/i");
			foreach($elseif_rule as $rule) {
				$compiled = preg_replace_callback($rule, function($match) {
					$output = "<?php }elseif(" . $match[1].") { ?>";
					$output = Sygrion::_replaceVar($output);
					return $output;
				}, $compiled);
			}
	
			// compile while phrase
			$while_rule = array("/@while\s?\((.*)\)/i");
			foreach($while_rule as $rule) {
				$compiled = preg_replace_callback($rule, function($match) {
					$output = "<?php while(" . $match[1].") { ?>";
					$output = Sygrion::_replaceVar($output);
					return $output;
				}, $compiled);
			}
	
			// compile for phrase
			$for_rule = array("/@for\s\((.*)\)/i");
			foreach($for_rule as $rule) {
				$compiled = preg_replace_callback($rule, function($match) {
					$output = "<?php for(" . $match[1].") { ?>";
					$output = Sygrion::_replaceVar($output);
					return $output;
				}, $compiled);
			}
	
			// compile foreach phrase
			$foreach_rule = array("/@foreach\s?\((.*)\)/i");
			foreach($foreach_rule as $rule) {
				$compiled = preg_replace_callback($rule, function($match) {
					$output = "<?php foreach(" . $match[1].") { ?>";
					$output = Sygrion::_replaceVar($output);
					return $output;
				}, $compiled);
			}
	
			// compile if, foreach, for
			$compiled = str_ireplace(array("@endif", "@endforeach", "@endfor", "@endswitch", "@endwhile"), "<?php } ?>", $compiled);
			
			// compile else
			$compiled = str_ireplace("@else", "<?php }else{ ?>", $compiled);
	
			// compile break, continue
			$compiled = str_ireplace(array("@break", "@continue"), array("<?php break; ?>", "<?php continue; ?>"), $compiled);
	
			// compile inline tag
			$compiled = $this->_parseInline($compiled);
			
			// compile variables
			$compiled = preg_replace_callback("/\{\{\s?(.*)\s?\}\}/imU", array($this, "_replaceInlineVariable"), $compiled);
			
			$compiled = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\r\n", $compiled);
			
			return $compiled;
		}
		
		/**
		 * replace loop and cond template syntax
		 * @param string $buff
		 * @return string changed result
		 */
		private function _parseInline($buff)
		{
			if(preg_match_all('/<([a-zA-Z]+\d?)(?>(?!<[a-z]+\d?[\s>]).)*?(?:[ \|]cond| loop)="/s', $buff, $match) === false)
			{
				return $buff;
			}
			$split_regex = "@(<(?>/?{$tags})(?>[^<>\{\}\"']+|<!--.*?-->|{[^}]+}|\".*?\"|'.*?'|.)*?>)@s";
			$nodes = preg_split($split_regex, $buff, -1, PREG_SPLIT_DELIM_CAPTURE);
			// list of self closing tags
			$self_closing = array('area' => 1, 'base' => 1, 'basefont' => 1, 'br' => 1, 'hr' => 1, 'input' => 1, 'img' => 1, 'link' => 1, 'meta' => 1, 'param' => 1, 'frame' => 1, 'col' => 1);
			for($idx = 1, $node_len = count($nodes); $idx < $node_len; $idx+=2)
			{
				if(!($node = $nodes[$idx]))
				{
					continue;
				}
				if(preg_match_all('@\s(loop|cond)="([^"]+)"@', $node, $matches))
				{
					// this tag
					$tag = substr($node, 1, strpos($node, ' ') - 1);
					// if the vale of $closing is 0, it means 'skipping'
					$closing = 0;
					// process opening tag
					foreach($matches[1] as $n => $stmt)
					{
						$expr = $matches[2][$n];
						$expr = self::_replaceVar($expr);
						$closing++;
						switch($stmt)
						{
							case 'cond':
								$nodes[$idx - 1] .= "<?php if({$expr}){ ?>";
								break;
							case 'loop':
								if(!preg_match('@^(?:(.+?)=>(.+?)(?:,(.+?))?|(.*?;.*?;.*?)|(.+?)\s*=\s*(.+?))$@', $expr, $expr_m))
								{
									break;
								}
								if($expr_m[1])
								{
									$expr_m[1] = trim($expr_m[1]);
									$expr_m[2] = trim($expr_m[2]);
									if($expr_m[3])
									{
										$expr_m[2] .= '=>' . trim($expr_m[3]);
									}
									$nodes[$idx - 1] .= "<?php if({$expr_m[1]}&&count({$expr_m[1]}))foreach({$expr_m[1]} as {$expr_m[2]}){ ?>";
								}
								elseif($expr_m[4])
								{
									$nodes[$idx - 1] .= "<?php for({$expr_m[4]}){ ?>";
								}
								elseif($expr_m[5])
								{
									$nodes[$idx - 1] .= "<?php while({$expr_m[5]}={$expr_m[6]}){ ?>";
								}
								break;
						}
					}
					$node = preg_replace('@\s(loop|cond)="([^"]+)"@', '', $node);
					// find closing tag
					$close_php = '<?php ' . str_repeat('}', $closing) . ' ?>';
					//  self closing tag
					if($node{1} == '!' || substr($node, -2, 1) == '/' || isset($self_closing[$tag]))
					{
						$nodes[$idx + 1] = $close_php . $nodes[$idx + 1];
					}
					else
					{
						$depth = 1;
						for($i = $idx + 2; $i < $node_len; $i+=2)
						{
							$nd = $nodes[$i];
							if(strpos($nd, $tag) === 1)
							{
								$depth++;
							}
							elseif(strpos($nd, '/' . $tag) === 1)
							{
								$depth--;
								if(!$depth)
								{
									$nodes[$i - 1] .= $nodes[$i] . $close_php;
									$nodes[$i] = '';
									break;
								}
							}
						}
					}
				}
				if(strpos($node, '|cond="') !== false)
				{
					$node = preg_replace('@(\s[-\w:]+(?:="[^"]+?")?)\|cond="(.+?)"@s', '<?php if($2){ ?>$1<?php } ?>', $node);
					$node = self::_replaceVar($node);
				}
				if($nodes[$idx] != $node)
				{
					$nodes[$idx] = $node;
				}
			}
			$buff = implode('', $nodes);
			return $buff;
		}

		public function __parse_load_target($match) {
			if(isset($match[2]))
				$st_filename = $match[2];
			if(isset($match[3])) 
				$st_filename = $match[3];
				
			$response = Kernel::getResponse();
			if(!preg_match("/(http|https):\/\/(.*)/i", $st_filename)) {
				// local side
				$st_filepath = $this->template_path;
				$fullPath = sprintf("%s%s", $st_filepath, $st_filename);
				$arr = explode(".", $fullPath);
				$ext = strtolower(end($arr));
				if(is_file($fullPath)) {
					switch($ext) {
						case 'css':
							$response->addCSS(realpath($fullPath));
							break;
						case 'js':
							$response->addJS(realpath($fullPath));
							break;
						default:
							break;
					}
				}
			} else {
				// remote side
				$arr = explode(".", $st_filename);
				$ext = strtolower(end($arr));
				if(is_file($st_filename)) {
					switch($ext) {
						case 'css':
							$response->addCSS($st_filename);
							break;
						case 'js':
							$response->addJS($st_filename);
							break;
						default:
							break;
					}
					
				}
			}
			return "";
		}
		
		public function __parse_asset($match) {
			if(isset($match[2]))
				$st_filename = $match[2];
			if(isset($match[3])) 
				$st_filename = $match[3];
				
			$response = Kernel::getResponse();
			if(!preg_match("/(http|https):\/\/(.*)/i", $st_filename)) {
				// local side
				$st_filepath = $this->template_path;
				$fullPath = sprintf("%s%s", $st_filepath, $st_filename);
				$arr = explode(".", $fullPath);
				$ext = strtolower(end($arr));
				if(is_file($fullPath)) {
					switch($ext) {
						case 'css':
							$this->addCSS(realpath($fullPath));
							break;
						case 'js':
							$this->addJS(realpath($fullPath));
							break;
						default:
							break;
					}
				}
			} else {
				// remote side
				$arr = explode(".", $st_filename);
				$ext = strtolower(end($arr));
				switch($ext) {
					case 'css':
						$this->addCSS($st_filename);
						break;
					case 'js':
						$this->addJS($st_filename);
						break;
					default:
						break;
				}
					
			}
			return "";
		}

		public function addCSS($st_filename) {
			$response = getResponse();
			$response->addCSS($st_filename);
			if($this->css == NULL) 
				$this->css = array();

			$this->css[] = $st_filename;
		}

		public function addJS($st_filename) {
			$response = getResponse();
			$response->addJS($st_filename);
			if($this->js == NULL) 
				$this->js = array();

			$this->js[] = $st_filename;
		}
		
		public function __parse_css($match) {
			if(isset($match[2]))
				$st_filename = $match[2];
			if(isset($match[3])) 
				$st_filename = $match[3];
				
			$response = Kernel::getResponse();
			if(!preg_match("/(http|https):\/\/(.*)/i", $st_filename)) {
				// local side
				$st_filepath = $this->template_path;
				$fullPath = sprintf("%s%s", $st_filepath, $st_filename);
				if(is_file($fullPath)) {
					$this->addCSS(realpath($fullPath));
				}
			} else {
				// remote side
				$this->addCSS($st_filename);
			}
			return "";
		}
		
		public function __parse_js($match) {
			if(isset($match[2]))
				$st_filename = $match[2];
			if(isset($match[3])) 
				$st_filename = $match[3];
			
			$response = Kernel::getResponse();
			if(!preg_match("/(http|https):\/\/(.*)/i", $st_filename)) {
				// local side
				$st_filepath = $this->template_path;
				$fullPath = sprintf("%s%s", $st_filepath, $st_filename);
				if(is_file($fullPath)) {
					$this->addJS(realpath($fullPath));
				}
			} else {
				// remote side
				$this->addJS($st_filename);
			}
			
			
			return "";
		}
		
		public function __parse_include($match){
			if(isset($match[2]))
				$incl_filename = $match[2];
			if(isset($match[3])) 
				$incl_filename = $match[3];
			
			if($incl_filename) {
				$arr = explode("/", $incl_filename);
				$incl_filename = preg_replace(sprintf("/%s$/", end($arr)), "", $incl_filename);
				$incl_filename = end($arr);
				unset($arr[count($arr)-1]);
				$addon_filepath = implode($arr, "/");
			}
				
			$incl_filepath = $this->template_path;
			if($addon_filepath) {
				$incl_filepath .= $addon_filepath;
				if($incl_realpath = realpath($incl_filepath))
					$incl_filepath = $incl_realpath;
				
				$incl_filepath .= "/";
			}
			
			$compiled = sygrion::__proc_include($incl_filepath, $incl_filename);
			
			return $compiled;
		}
		
		public static function __proc_include($template_path, $template_file)
		{
			$oSygrion = sygrion::getInstance($template_path);
			$fullPath = sprintf("%s%s", $template_path, $template_file);
			
			if(is_file($fullPath)) {
				$compiled = file_get_contents($fullPath);
			} else {
				// $compiled = sprintf("<br/><b>Sygrion Template Error</b>: Unable to compile template file '%s' (sygrion: no such file or directory)", $fullPath);
				throw new \Exception(sprintf("Sygrion - Error while include  '%s' (no such file or directory)", $fullPath));
			}
			
			// Parse Include Import CSS&JS
			$compiled = $oSygrion->parseIncludeImport($compiled);
			
			ob_start();
			$success = $oSygrion->execute($compiled);
			$compiled = ob_get_contents();
			ob_end_clean();
			
			return $compiled;
		}
	
		public function parseIncludeImport($compiled)
		{

			// Calibrate src="" arguments
			$compiled = preg_replace_callback("/(src=\"(.*)\"|src=\'(.*)\')/imU", array($this, "_replaceSrcPath"), $compiled);
			
			// compile @css import
			$compiled = preg_replace_callback("/(@css \"(.*)\"[\r+]?$|@css \'(.*)\'[\r+]?$)/imsU", array($this, "__parse_css"), $compiled);
			
			// compile @js import
			$compiled = preg_replace_callback("/(@js \"(.*)\"[\r+]?$|@js \'(.*)\')[\r+]?$/imsU", array($this, "__parse_js"), $compiled);
			
				// compile @css import
			$compiled = preg_replace_callback("/(@asset \"(.*)\"[\r+]?$|@asset \'(.*)\'[\r+]?$)/imsU", array($this, "__parse_asset"), $compiled);
			
			// compile included template
			$compiled = preg_replace_callback("/(@include \"(.*)\"$|@include \'(.*)\'$)/imsU", array($this, "__parse_include"), $compiled);
			
			// compile @use script
			$compiled = preg_replace_callback("/(@use (.*)\\sas\\s(.*)$|@css \'(.*)\'$)/imsU", function($match) {
				/* $compiled = sprintf("<?php use %s as %s ?>", $match[2], $match[3]); */
				$this->addClassAlias($match[2], $match[3]);
				return "";
				// return $compiled;
			}, $compiled);
			
			// compile @namespace script
			$compiled = preg_replace_callback("/(@namespace (.*)$|@css \'(.*)\'$)/imsU", function($match) {
				/* $compiled = sprintf("<?php use %s as %s ?>", $match[2], $match[3]); */
				$this->default_namespace = $match[2];
				return "";
				// return $compiled;
			}, $compiled);
			
			$compiled = preg_replace_callback("/\@title (.*)$/imU", function($match){
				$compiled = sprintf("<?php setTitle(%s); ?>", $match[1]);
				return $compiled;
			}, $compiled);
			
			return $compiled;
		}
		
		public function _replaceSrcPath($match) {
			if(isset($match[2]) && $match[2] !== "") {
				$path = $match[2];
			}
			if(isset($match[3]) && $match[3] !== "") {
				$path = $match[3];
			}
			
			$replacePath = realpath(sprintf("%s%s", $this->template_path, $path));
			$replacePath = str_replace($_SERVER['DOCUMENT_ROOT'], "", $replacePath);
			if(!$replacePath) 
				$replacePath = $path;
				
			return sprintf("src=\"%s\"", $replacePath);
		}
		/**
		 * replace PHP variables of $ character
		 * @param string $php
		 * @return string $__Context->varname
		 */
		static function _replaceVar($php)
		{
			if(!strlen($php))
			{
				return '';
			}
			
			// Prevent Two times replacement
			if(preg_match("/\\\$GLOBALS\[\'__VARIABLES\'\]/", $php)) {
				// throw new Exception();
				return $php;
			}
			
			return preg_replace('@(?<!::|\\\\|(?<!eval\()\')\$([a-z]|_[a-z0-9])@i', '\$GLOBALS[\'__VARIABLES\']->$1', $php);
		}
		
		function _replaceInlinePHP($match) {
			$output = "<?php " . $match[1] . "; ?>";
			$output = $this->_replaceVar($output);
			return $output;
		}
		
		function _replaceInlineVariable($match) {
			if(preg_match("/^\\\$/", $match[1])) {
				$var_name = substr($match[1], 1);
				$output = '<?php echo $GLOBALS[\'__VARIABLES\']->' . $var_name . "; ?>";
			} else {
				$var_name = $match[1];
				$output = '<?php echo ' . $var_name . "; ?>";
				$output = $this->_replaceVar($output);
			}
			
			// Prevent Two times replacement
			if(preg_match("/\\\$GLOBALS\[\'__VARIABLES\'\]/", $match[1])) {
				return '<?php echo ' . $match[1] . "; ?>";
			}
			
			return $output;
		}
	}
}