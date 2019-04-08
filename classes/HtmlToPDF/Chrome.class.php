<?php
namespace HtmlToPDF {

	class Chrome {
		private $bunary = NULL;
		private $tmp_dir = NULL;
		public function __construct() {
			switch(PHP_OS) {
				case 'WINNT':
					$this->binary = "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe";
					
					break;
				case 'Darwin':
					$this->binary = "/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome";
					break;
				default:
					$this->binary = "Chrome";
					break;

			}
			$this->tmp_dir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
			$this->tmp_dir .= '/';

		}

		public function execute($path = NULL, $options = NULL) {
			$output_filename = $this->tmp_dir . uniqid('CHR' . time()) . '.pdf';
			
			/** $args = ' --headless --disable-gpu';
			$args .= '  --print-to-pdf=' . $output_filename;
			$args .= ' ' . $path;
			$cmdLine = escapeshellarg($this->binary) . $args; */
			
			$cmdLine = sprintf(
				'%s --headless --disable-gpu --print-to-pdf=%s %s',
				escapeshellarg($this->binary),
				escapeshellarg($output_filename),
				escapeshellarg($path)
			);

			$output = NULL;
			\Core\Logger::getInstance()->write(sprintf('execute command "%s"', $cmdLine), "HtmlToPDF");
			
			exec('start "" ' . $cmdLine, $output, $return_var);
			// $output = shell_exec($cmdLine);
			$output = implode($output, "\n");
			\Core\Logger::getInstance()->write($output, "HtmlToPDF");

			if($return_var === 1){
				$msg = 'Unable to execute command "' . htmlentities($cmdLine) . '"';
				\Core\Logger::getInstance()->write($msg, "HtmlToPDF");
				throw new HtmlToPDFException($msg);
			}

			if(!is_file($output_filename)) {
				$msg = 'Unable to convert PDF "Chrome exit unexpectly"';
				\Core\Logger::getInstance()->write($msg, "HtmlToPDF");
				throw new HtmlToPDFException($msg);
			}
			
			$pdfContent = file_get_contents($output_filename);

			// @unlink($output_filename);
			return new ConvertOutput($pdfContent);
		}
	}
}