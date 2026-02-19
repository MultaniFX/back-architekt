<?php
declare(strict_types=1);

class Lang {

	private static array $strings = [];

	public static function load(string $locale = 'de'): void {
		$file = __DIR__ . '/lang/' . $locale . '.php';
		if (is_file($file)) {
			self::$strings = require $file;
		}
	}

	public static function get(string $key, mixed ...$args): string {
		$str = self::$strings[$key] ?? $key;
		return empty($args) ? $str : sprintf($str, ...$args);
	}

	public static function all(): array {
		return self::$strings;
	}

	/** Returns a subset of strings matching a prefix, useful for JS injection. */
	public static function subset(string $prefix): array {
		$out = [];
		$len = strlen($prefix);
		foreach (self::$strings as $key => $val) {
			if (str_starts_with($key, $prefix)) {
				$out[substr($key, $len)] = $val;
			}
		}
		return $out;
	}
}
