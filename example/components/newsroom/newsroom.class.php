<?php
namespace ProcessWire;

class Newsroom extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$filterArguments = array(
			'characterLimit' => 150
		);

		// Is a tags filter set?
		if (wire('input')->get('tags')) {
			$filterArguments['tags'] = wire('input')->get('tags');
		}

		// Is a search query set?
		if (wire('input')->get('tags')) {
			$filterArguments['tags'] = wire('input')->get('tags');
		}

		$this->newsService = $this->getService('NewsService');
		$newsOutput = $this->newsService->getArticles($filterArguments);
		$this->hasMore = $newsOutput->hasMore;
		$this->lastElementIndex = $newsOutput->lastElementIndex;
		$this->count = $newsOutput->count;
		$newsitems = $newsOutput->articles;

		foreach ($newsitems as $page) {
			$this->addComponent('NewsitemCard', ['page' => $page]);
		}

		$this->newsPage = $this->newsService->getNewsPage();
	}

	public function getAjax(){
		return $this->newsService->getAjax();
	}
}
