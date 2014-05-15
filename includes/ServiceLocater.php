<?php

class ServiceLocater {
	public static function resolve(array $config, $interface) {
		if (!isset($config['provider'])) {
			return null;
		}
		$provider = $config['provider'];
		if (class_exists($provider) && is_subclass_of($provider, $interface)) {
			$r = new $provider();
			$r->configure($config);
			if ($r->isConfigured()) {
				return $r;
			}
		}
		throw new ConfigurationException($provider . ' not found.');
	}
}


