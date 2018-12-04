<?php
namespace HtmlToPDF {

	class Shell {
		private $htpBinary = NULL;
		private $htiBinary = NULL;
		private $tmp_dir = NULL;
		public function __construct() {
			$binaryPath = realpath(__DIR__ . '/bin/');
			if($_SERVER['OS'] == 'Windows_NT') {
				$this->htpBinary = $binaryPath . '/wkhtmltopdf.exe';
				$this->htiBinary = $binaryPath . '/wkhtmltoimage.exe';
			} else {
				$this->htpBinary = $binaryPath . '/wkhtmltopdf';
				$this->htiBinary = $binaryPath . '/wkhtmltoimage';
			}
			$this->tmp_dir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
			$this->tmp_dir .= '/';
		}

		public function execute($path = NULL, $options = NULL) {
			$output_filename = $this->tmp_dir . uniqid('HTP' . time()) . '.pdf';
			$args = '';
			foreach($options as $k=>$v) {
				if(is_numeric($k)) {
					$args .= ' --' . $v;
				} else {
					$args .= ' --' . $k . ' ' . $v;
				}
			}

			$args .= ' ' . $path;
			$args .= ' ' . $output_filename;

			$cmdLine = $this->htpBinary . $args;

			$output = NULL;
			exec($cmdLine . ' 2>&1', $output, $return_var);
			$output = implode($output, "\n");

			if($return_var === 1) throw new HtmlToPDFException('Unable to execute command "' . htmlentities($cmdLine) . '"');

			$pdfContent = file_get_contents($output_filename);

			@unlink($output_filename);
			return new ConvertOutput($pdfContent);
		}
	}
}