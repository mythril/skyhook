<?php

class Localization {
	private static $locale;
	
	private static $available = [];
	private static $present = [];
	
	public static function localePresent($locale) {
		return isset(self::$present[$locale]);
	}
	
	public static function getAvailableLocales() {
		if (!empty(self::$available)) {
			return self::$available;
		}
		foreach (glob(getcwd() . '/locales/*/meta.json') as $l) {
			$tmp = JSON::decode(file_get_contents($l));
			$name = basename(dirname($l));
			$tmp['locale_name'] = $name;
			self::$available[] = $tmp;
			self::$present[$name] = $tmp;
		}
		return self::$available;
	}
	
	public static function saveLocale($locale) {
		self::setLocale($locale);
		file_put_contents('locale', $locale);
	}
	
	public static function setLocale($locale) {
		if (self::localePresent($locale)) {
			self::$locale = $locale;
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
	
	public static function flagURL() {
		return '/assets/flags/' . self::$present[self::$locale]['icon'] . '.png';
	}
}
