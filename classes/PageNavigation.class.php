<?php
namespace Core;

class PageNavigation {
	public $page;
	public $totalPageCount;
	public $pageStart;
	public $pageEnd;
	public $currentPageGroup;
	public $totalPageGroupCount;
	
	public function __construct($totalCount, $page, $recordsPerPage, $pagePerPageGroup) {
		$totalPageCount = ceil($totalCount / $recordsPerPage);
		$totalPageGroupCount = ceil($totalPageCount / $pagePerPageGroup);
		$recordStart = ($page-1) * $recordsPerPage;
				
		$currentPageGroup = ceil($page/$pagePerPageGroup);
		$pageGroupStart = (($currentPageGroup - 1) * $pagePerPageGroup) + 1;
		$pageGroupEnd = $pageGroupStart + $pagePerPageGroup - 1;
				
		$this->page = $page;
		$this->totalPageCount = $totalPageCount;
		$this->pageStart = $pageGroupStart;
		$this->pageEnd = $pageGroupEnd;
		$this->currentPageGroup = $currentPageGroup;
		$this->totalPageGroupCount = $totalPageGroupCount;
	}
	
	public function getNextPage() {
		return $this->pageEnd + 1;
	}
	
	public function getPrevPage() {
		return $this->pageStart - 1;
	}
	
	public function getCurrentPage() {
		return $this->page;
	}
	
	public function isLastPageGroup() {
		if($this->currentPageGroup == $this->totalPageGroupCount) {
			return true;
		} else {
			if($this->page > $this->totalPageCount) {
				return true;
			}
			return false;
		}
	}
	
	public function isFirstPageGroup() {
		if($this->currentPageGroup == 1) {
			return true;
		} else {
			return false;
		}
	}
	
	public function getPageList() {
		$pageList = array();
		if($this->pageEnd > $this->totalPageCount) {
			$this->pageEnd = $this->totalPageCount;
		}
		for($i=$this->pageStart; $i<=$this->pageEnd; $i++) {
			$pageList[] = $i;
		}
		
		return $pageList;
	}
}