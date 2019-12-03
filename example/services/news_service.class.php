<?php
namespace ProcessWire;

class NewsService extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);
	}

	public function getNewsPage() {
		return wire('pages')->get('template.name="newsroom"');
	}

	public function getArticles($args = array()) {
		$output = new \StdClass();
		$articles = $this->getNewsPage()->find('template=newsitem');

		if (isset($args['sort'])) {
			$articles->filter('sort=' . $args['sort']);
		} else {
			$articles->filter('sort=-date');
		}

		// Filter by Tags:
		if (isset($args['tags'])) {
			if (is_string($args['tags'])) {
				$args['tags'] = explode(',', $args['tags']);
			}

			if (is_array($args['tags'])) {
				$articles->filter('tags='.implode('|', $args['tags']));
			}
		}

		// Filter by search query:
		if (isset($args['query'])) {
			if (is_string($args['query'])) {
				$query = wire('sanitizer')->text($args['query']);
				$articles->filter("title|name|einleitung|inhalte.text%={$query}");
			}
		}

		// Save the original count of all items before applying limit and offset:
		$output->count = $articles->count;

		// Index of last element of the output:
		$output->lastElementIndex = 0;

		// Apply Limit and Offset:
		$limitSelector = array();

		if (isset($args['start'])) {
			$limitSelector[] = 'start=' . $args['start'];
			$output->lastElementIndex = intval($args['start']);
		} elseif (isset($args['offset'])) {
			$limitSelector[] = 'start=' . $args['offset'];
			$output->lastElementIndex = intval($args['offset']);
		} else {
			$limitSelector[] = 'start=0';
		}

		if (isset($args['limit']) && $args['limit'] >= 0) {
			$limitSelector[] = 'limit=' . $args['limit'];
			$output->lastElementIndex = $output->lastElementIndex + intval($args['limit']);
		} elseif (!isset($args['limit'])) {
			$limitSelector[] = 'limit=12';
			$output->lastElementIndex = $output->lastElementIndex + 12;
		}

		if (!empty($limitSelector)) {
			$articles->filter(implode(', ', $limitSelector));
		}

		// Are there any more posts that can be downloaded?
		$output->hasMore = $output->lastElementIndex + 1 < $output->count;

		$output->articles = $articles;

		return $output;
	}

	public function getAjax() {
		$ajaxOutput = array();

		$args = wire('input')->post('args');
		if (!is_array($args)) {
			$args = array();
		}

		// Is a tags filter set?
		if (wire('input')->get('tags')) {
			$args['tags'] = wire('input')->get('tags');
		}

		// Is a search query set?
		if (wire('input')->get('query')) {
			$args['query'] = wire('input')->get('query');
		}

		if (wire('input')->get('limit')) {
			$args['limit'] = wire('input')->get('limit');
		}

		if (wire('input')->get('start')) {
			$args['start'] = wire('input')->get('start');
		} elseif (wire('input')->get('offset')) {
			$args['start'] = wire('input')->get('offset');
		}

		$articleOutput = $this->getArticles($args);
		$ajaxOutput['count'] = $articleOutput->count;
		$ajaxOutput['hasMore'] = $articleOutput->hasMore;
		$ajaxOutput['lastElementIndex'] = $articleOutput->lastElementIndex;

		// Deliver HTML card for each newsitem:
		$ajaxOutput['articles'] = array();
		foreach ($articleOutput->articles as $newsitem) {
			$component = $this->addComponent('NewsitemCard', ['directory' => 'newsroom', 'page' => $newsitem]);
			if ($component instanceof TwackNullComponent) continue;

			$ajaxOutput['articles'][] = $component->getAjax();
		}

		return $ajaxOutput;
	}
}
