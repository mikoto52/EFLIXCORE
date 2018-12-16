<?php
namespace HtmlToPDF {

	class Chrome {
		private $bunary = NULL;
		private $tmp_dir = NULL;
		public function __construct() {
			$this->binary = "/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome";
			$this->tmp_dir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
			$this->tmp_dir .= '/';
		}

		public function execute($path = NULL, $options = NULL) {
			$output_filename = $this->tmp_dir . uniqid('CHR' . time()) . '.pdf';
			$args = ' --headless --disable-gpu';

			$args .= '  --print-to-pdf=' . $output_filename;
			$args .= ' ' . $path;

			$cmdLine = $this->binary . $args;

			$output = NULL;
			exec($cmdLine . ' 2>&1', $output, $return_var);
			$output = implode($output, "\n");
			\Core\Logger::getInstance()->write($output, "HtmlToPDF");
			
			if($return_var === 1) throw new HtmlToPDFException('Unable to execute command "' . htmlentities($cmdLine) . '"');

			$pdfContent = file_get_contents($output_filename);

			@unlink($output_filename);
			return new ConvertOutput($pdfContent);
		}
	}
}