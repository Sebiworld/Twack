<?php
namespace ProcessWire;

class NewsitemCard extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);
	}

	public function getAjax(){
		$output = $this->getAjaxOf($this->page);
		$output['date'] = $this->page->getUnformatted('date');
		$output['introduction'] = $this->page->introduction;

		// Only output HTML, if its explicitly requested:
		if(wire('input')->get('htmlOutput')){
			$output['html'] = $this->renderView();
		}

		if($this->page->image){
			$output['image'] = $this->getAjaxOf($this->page->image->height(300));
		}

		return $output;
	}
}
