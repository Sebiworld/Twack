<?php

namespace ProcessWire;

class TwackApiAccess {
	public static function pageIDRequest($data) {
		$data = AppApiHelper::checkAndSanitizeRequiredParameters($data, ['id|int']);
		$page = wire('pages')->get('id=' . $data->id);
		return self::pageRequest($page, '');
	}

	public static function dashboardRequest() {
		$page = wire('pages')->get('/');
		return self::pageRequest($page, '');
	}

	public static function pagePathRequest($data) {
		$data = AppApiHelper::checkAndSanitizeRequiredParameters($data, ['path|pagePathName']);
		$path = '/' . trim($data->path, '/') . '/';
		$page = wire('pages')->get('path="' . $path . '"');

		if (!$page->id && wire('modules')->isInstalled('LanguageSupport')) {
			// Check if its a root path
			$rootPage = wire('pages')->get('/');
			foreach ($rootPage->urls as $key => $value) {
				if ($value !== $path) {
					continue;
				}
				return self::pageRequest($rootPage, $key);
			}
		}

		$info = wire('pages')->pathFinder()->get($path);
		if (!empty($info['language']['name'])) {
			return self::pageRequest($page, $info['language']['name']);
		}

		return self::pageRequest($page, '');
	}

	protected static function pageRequest(Page $page, $languageFromPath) {
		if (!wire('modules')->isInstalled('Twack')) {
			throw new InternalServererrorException('Twack module not found.');
		}
		wire('twack')->enableAjaxResponse();

		if (wire('modules')->isInstalled('LanguageSupport')) {
			if (!empty($languageFromPath) && wire('languages')->get($languageFromPath) instanceof Page && wire('languages')->get($languageFromPath)->id) {
				wire('user')->language = wire('languages')->get($languageFromPath);
			} else {
				$lang = '' . strtolower(wire('input')->get->pageName('lang'));
				$langAlt = SELF::getLanguageCode($lang);

				if (!empty($lang) && wire('languages')->get($lang) instanceof Page && wire('languages')->get($lang)->id) {
					wire('user')->language = wire('languages')->get($lang);
				} elseif (!empty($langAlt) && wire('languages')->get($langAlt) instanceof Page && wire('languages')->get($langAlt)->id) {
					wire('user')->language = wire('languages')->get($langAlt);
				} else {
					wire('user')->language = wire('languages')->getDefault();
				}
			}

			if (!($page instanceof Page) || !$page->id) {
				throw new NotFoundException();
			} elseif (!$page->viewable(wire('user')->language)) {
				throw new ForbiddenException();
			}
		}

		if (!($page instanceof Page) || !$page->id) {
			throw new NotFoundException();
		} elseif (!$page->viewable()) {
			throw new ForbiddenException();
		}

		$ajaxOutput = $page->render();
		$results = json_decode($ajaxOutput, true);
		return $results;
	}

	/**
	 * Format requested language
	 *
	 * @param [type] $key
	 * @return void
	 */
	public static function getLanguageCode($key) {
		$languageCodes = [
			'aa' => 'afar',
			'ab' => 'abkhazian',
			'af' => 'afrikaans',
			'am' => 'amharic',
			'ar' => 'arabic',
			'ar-ae' => 'arabic-u-a-e',
			'ar-bh' => 'arabic-bahrain',
			'ar-dz' => 'arabic-algeria',
			'ar-eg' => 'arabic-egypt',
			'ar-iq' => 'arabic-iraq',
			'ar-jo' => 'arabic-jordan',
			'ar-kw' => 'arabic-kuwait',
			'ar-lb' => 'arabic-lebanon',
			'ar-ly' => 'arabic-libya',
			'ar-ma' => 'arabic-morocco',
			'ar-om' => 'arabic-oman',
			'ar-qa' => 'arabic-qatar',
			'ar-sa' => 'arabic-saudi-arabia',
			'ar-sy' => 'arabic-syria',
			'ar-tn' => 'arabic-tunisia',
			'ar-ye' => 'arabic-yemen',
			'as' => 'assamese',
			'ay' => 'aymara',
			'az' => 'azeri',
			'ba' => 'bashkir',
			'be' => 'belarusian',
			'bg' => 'bulgarian',
			'bh' => 'bihari',
			'bi' => 'bislama',
			'bn' => 'bengali',
			'bo' => 'tibetan',
			'br' => 'breton',
			'ca' => 'catalan',
			'co' => 'corsican',
			'cs' => 'czech',
			'cy' => 'welsh',
			'da' => 'danish',
			'de' => 'german',
			'de-at' => 'german-austria',
			'de-ch' => 'german-switzerland',
			'de-li' => 'german-liechtenstein',
			'de-lu' => 'german-luxembourg',
			'div' => 'divehi',
			'dz' => 'bhutani',
			'el' => 'greek',
			'en' => 'english',
			'en-au' => 'english-australia',
			'en-bz' => 'english-belize',
			'en-ca' => 'english-canada',
			'en-gb' => 'english-united-kingdom',
			'en-ie' => 'english-ireland',
			'en-jm' => 'english-jamaica',
			'en-nz' => 'english-new-zealand',
			'en-ph' => 'english-philippines',
			'en-tt' => 'english-trinidad',
			'en-us' => 'english-united States',
			'en-za' => 'english-south-africa',
			'en-zw' => 'english-zimbabwe',
			'eo' => 'esperanto',
			'es' => 'spanish',
			'es-ar' => 'spanish-argentina',
			'es-bo' => 'spanish-bolivia',
			'es-cl' => 'spanish-chile',
			'es-co' => 'spanish-colombia',
			'es-cr' => 'spanish-costa-rica',
			'es-do' => 'spanish-dominican-republic',
			'es-ec' => 'spanish-ecuador',
			'es-es' => 'spanish-espana',
			'es-gt' => 'spanish-guatemala',
			'es-hn' => 'spanish-honduras',
			'es-mx' => 'spanish-mexico',
			'es-ni' => 'spanish-nicaragua',
			'es-pa' => 'spanish-panama',
			'es-pe' => 'spanish-peru',
			'es-pr' => 'spanish-puerto-rico',
			'es-py' => 'spanish-paraguay',
			'es-sv' => 'spanish-el-salvador',
			'es-us' => 'spanish-united-states',
			'es-uy' => 'spanish-uruguay',
			'es-ve' => 'spanish-venezuela',
			'et' => 'estonian',
			'eu' => 'basque',
			'fa' => 'farsi',
			'fi' => 'finnish',
			'fj' => 'fiji',
			'fo' => 'faeroese',
			'fr' => 'french',
			'fr-be' => 'french-belgium',
			'fr-ca' => 'french-canada',
			'fr-ch' => 'french-switzerland',
			'fr-lu' => 'french-luxembourg',
			'fr-mc' => 'french-monaco',
			'fy' => 'frisian',
			'ga' => 'irish',
			'gd' => 'gaelic',
			'gl' => 'galician',
			'gn' => 'guarani',
			'gu' => 'gujarati',
			'ha' => 'hausa',
			'he' => 'hebrew',
			'hi' => 'hindi',
			'hr' => 'croatian',
			'hu' => 'hungarian',
			'hy' => 'armenian',
			'ia' => 'interlingua',
			'id' => 'indonesian',
			'ie' => 'interlingue',
			'ik' => 'inupiak',
			'in' => 'indonesian',
			'is' => 'icelandic',
			'it' => 'italian',
			'it-ch' => 'italian-switzerland',
			'iw' => 'hebrew',
			'ja' => 'japanese',
			'ji' => 'yiddish',
			'jw' => 'javanese',
			'ka' => 'georgian',
			'kk' => 'kazakh',
			'kl' => 'greenlandic',
			'km' => 'cambodian',
			'kn' => 'kannada',
			'ko' => 'korean',
			'kok' => 'konkani',
			'ks' => 'kashmiri',
			'ku' => 'kurdish',
			'ky' => 'kirghiz',
			'kz' => 'kyrgyz',
			'la' => 'latin',
			'ln' => 'lingala',
			'lo' => 'laothian',
			'ls' => 'slovenian',
			'lt' => 'lithuanian',
			'lv' => 'latvian',
			'mg' => 'malagasy',
			'mi' => 'maori',
			'mk' => 'fyro-macedonian',
			'ml' => 'malayalam',
			'mn' => 'mongolian',
			'mo' => 'moldavian',
			'mr' => 'marathi',
			'ms' => 'malay',
			'mt' => 'maltese',
			'my' => 'burmese',
			'na' => 'nauru',
			'nb-no' => 'norwegian-bokmal',
			'ne' => 'nepali-india',
			'nl' => 'dutch',
			'nl-be' => 'dutch-belgium',
			'nn-no' => 'norwegian',
			'no' => 'norwegian-nokmal',
			'oc' => 'occitan',
			'om' => 'afan-oromoor-oriya',
			'or' => 'oriya',
			'pa' => 'punjabi',
			'pl' => 'polish',
			'ps' => 'pashto',
			'pt' => 'portuguese',
			'pt-br' => 'portuguese-brazil',
			'qu' => 'quechua',
			'rm' => 'rhaeto-romanic',
			'rn' => 'kirundi',
			'ro' => 'romanian',
			'ro-md' => 'romanian-moldova',
			'ru' => 'russian',
			'ru-md' => 'russian-moldova',
			'rw' => 'kinyarwanda',
			'sa' => 'sanskrit',
			'sb' => 'sorbian',
			'sd' => 'sindhi',
			'sg' => 'sangro',
			'sh' => 'serbo-croatian',
			'si' => 'singhalese',
			'sk' => 'slovak',
			'sl' => 'slovenian',
			'sm' => 'samoan',
			'sn' => 'shona',
			'so' => 'somali',
			'sq' => 'albanian',
			'sr' => 'serbian',
			'ss' => 'siswati',
			'st' => 'sesotho',
			'su' => 'sundanese',
			'sv' => 'swedish',
			'sv-fi' => 'swedish-finland',
			'sw' => 'swahili',
			'sx' => 'sutu',
			'syr' => 'syriac',
			'ta' => 'tamil',
			'te' => 'telugu',
			'tg' => 'tajik',
			'th' => 'thai',
			'ti' => 'tigrinya',
			'tk' => 'turkmen',
			'tl' => 'tagalog',
			'tn' => 'tswana',
			'to' => 'tonga',
			'tr' => 'turkish',
			'ts' => 'tsonga',
			'tt' => 'tatar',
			'tw' => 'twi',
			'uk' => 'ukrainian',
			'ur' => 'urdu',
			'us' => 'english',
			'uz' => 'uzbek',
			'vi' => 'vietnamese',
			'vo' => 'volapuk',
			'wo' => 'wolof',
			'xh' => 'xhosa',
			'yi' => 'yiddish',
			'yo' => 'yoruba',
			'zh' => 'chinese',
			'zh-cn' => 'chinese-china',
			'zh-hk' => 'chinese-hong-kong',
			'zh-mo' => 'chinese-macau',
			'zh-sg' => 'chinese-singapore',
			'zh-tw' => 'chinese-taiwan',
			'zu' => 'zulu'
		];

		$code = '';
		if (!empty($languageCodes[$key])) {
			$code = $languageCodes[$key];
		}

		return $code;
	}
}
