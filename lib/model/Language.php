<?php
/**
 * Language class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

class Language implements \Skeleton\I18n\LanguageInterface {
	public static function get_by_name_short($name) {
		$language = new Language();
		$language->id = 1;
		$language->name = 'English';
		$language->name_local = 'English';
		$language->name_short = 'en';
		$language->name_ogone = 'en';

		return $language;
	}

	public static function get_all() {
		return [self::get_by_name_short('foo')];
	}

}

