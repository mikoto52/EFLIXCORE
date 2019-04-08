<?php
namespace HtmlToPDF {
	class ConvertOutput {
		public $content = NULL;
		public function __construct($content = NULL) {
			$this->content = $content;
		}
		public function toString() {
			return $this->content;
		}
		public function download($filename = 'output.pdf') {
			header('Content-type: application/pdf');
			header('Content-Disposition: inline; filename="' . $filename . '"');
			header('Content-Transfer-Encoding: binary');
			header('Accept-Ranges: bytes');

			echo $this->content;
			exit;
		}
	}
}