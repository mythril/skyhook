<?php

class Localization {
	private static $locale;
	
	private static $available = [];
	
	public static function localePresent($locale) {
		return isset(self::$available[$locale]);
	}
	
	public static function getAvailableLocales() {
		foreach (glob(getcwd() . '/locales/*', GLOB_ONLYDIR) as $l) {
			self::$available[basename($l)] = true;
		}
		return self::$available;
	}
	
	public static function setLocale($locale) {
		if (self::localePresent($locale)) {
			self::$locale = $locale;
			file_put_contents('locale', $locale);
		} else {
			throw new Exception('Invalid locale.');
		}
	}
	
	public static function init() {
		if (count(self::$available) === 0) {
			self::getAvailableLocales();
		}
		$candidate = 'en_US';
		if (file_exists('locale')) {
			$candidate = trim(file_get_contents('locale'));
		}
		if (self::localePresent($candidate)) {
			self::$locale = $candidate;
		} else {
			self::$locale = 'en_US';
		}
	}
	
	public static function getTranslator() {
		return new Zend_Translate(
			'Zend_Translate_Adapter_Gettext',
			self::path('LC_MESSAGES/skyhook.mo'),
			self::$locale
		);
	}
	
	public static function getLocale() {
		return Zend_Locale::findLocale(self::$locale);
	}
	
	public static function path($fn = '') {
		return rtrim(getcwd(), '/') . '/locales/' . self::$locale . '/' . ltrim($fn, '/');
	}
	
	public static function url($fn = '') {
		return '/locales/' . self::$locale . '/' . ltrim($fn);
	}
}
