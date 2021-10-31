<?php

namespace ProcessWire;

class TwackApiAccess {
	public static function pageIDRequest($data) {
		$data = AppApiHelper::checkAndSanitizeRequiredParameters($data, ['id|int']);
		$page = wire('pages')->get('id=' . $data->id);
		return self::pageRequest($page);
	}

	public static function dashboardRequest() {
		$page = wire('pages')->get('/');
		return self::pageRequest($page);
	}

	public static function pagePathRequest($data) {
		$data = AppApiHelper::checkAndSanitizeRequiredParameters($data, ['path|pagePathName']);
		$page = wire('pages')->get('/' . $data->path);
		return self::pageRequest($page);
	}

	protected static function pageRequest(Page $page) {
		if (!wire('modules')->isInstalled('Twack')) {
			throw new InternalServererrorException('Twack module not found.');
		}
		wire('twack')->enableAjaxResponse();

		$lang = SELF::getLanguageCode(wire('input')->get->pageName('lang'));
		if (!empty($lang) && wire('languages')->get($lang)) {
			wire('user')->language = wire('languages')->get($lang);
		} else {
			wire('user')->language = wire('languages')->getDefault();
		}

		if (!$page->viewable()) {
			throw new ForbiddenException();
		}

		$ajaxOutput = $page->render();
		$results = json_decode($ajaxOutput, true);
		return $results;
	}

	private static function getLanguageCode($key) {
		$languageCodes = [
			'de' => 'german',
			'en' => 'english'
		];

		$code = '' . strtolower($key);
		if (!empty($languageCodes[$key])) {
			$code = $languageCodes[$key];
		}

		return $code;
	}
}
