<?php
use Environment\Post;

class ConfigNormalizer {
	public static function normalize(Post $post) {
		$config = Container::dispense('Config');
		$normalized = [
			'email' => [
				'username' => @$post['email']['username'],
				'password' => @$post['email']['password'],
				'machine' => @$post['email']['machine'],
			],
			'walletProvider' => ['provider' => 'WalletProviders\Blockchain'],
			'pricingProvider' => [],
			'contact' => ['information' => @$post['contact']['information']],
			'transaction-cron' => false,
			'transactions' => [
				'maximum' => @$post['transactions']['maximum'],
			],
		];
		if (empty($normalized['email']['machine'])) {
			$normalized['email']['machine'] = 'Project Skyhook 00';
		}
		
		$normalized['transaction-cron'] = ($post['transaction-cron'] === "on");
		
		$blockchain = &$normalized['walletProvider'];
		$blockchain['id'] = @$post['wallet']['id'];
		$blockchain['mainPass'] = @$post['wallet']['mainPass'];
		$blockchain['secondPass'] = @$post['wallet']['secondPass'];
		$blockchain['fromAddress'] = @$post['wallet']['fromAddress'];
		$pricing = [];
		$filtered = function ($available, $chosen) use (&$post) {
			return array_fill_keys(
				array_filter(
					$chosen,
					function ($a) use($available) {
						return !empty($available[$a]);
					}
				),
				true
			);
		};
		$roughSources = $filtered(
			$config->getAvailablePricingProviders(),
			explode(',', @$post['sources'])
		);
		
		$sources = [];
		
		foreach ($roughSources as $rough => $enabled) {
			$provider = [
				'provider' => $rough
			];
			if ($rough === 'PricingProviders\StaticPrice') {
				$provider['value'] = @$post['staticPrice'];
			}
			$sources[] = $provider;
		}
		
		$selector = @$post['selector'];
		$availableSelectors = [
			'Pricing\Selectors\HighestPrice' => true,
			'Pricing\Selectors\LowestPrice' => true,
		];
		
		if (isset($availableSelectors[$selector])) {
			$pricing = [
				'provider' => $selector,
				'children' => $sources
			];
		} else {
			if (isset($sources[0])) {
				$pricing = $sources[0];
			}
		}
		
		$modifiersEnabled = isset($post['modifierEnabled'])
			? $post['modifierEnabled']
			: [];
		
		$realModifiers = $config->getAvailablePricingModifiers();
		
		$mns = "Pricing\\Modifiers\\";
		foreach ([
			$mns . 'PercentageFee',
			$mns . 'MinimumPrice',
		] as $mod) {
			if (empty($realModifiers[$mod]) || empty($modifiersEnabled[$mod])) {
				continue;
			}
			$pricing = [
				'provider' => $mod,
				'children' => [$pricing],
				'value' => @$post['modifier'][$mod]
			];
		}
		
		$normalized['pricingProvider'] = $pricing;
		return $normalized;
	}
	
	public static function denormalize(Config $cfg) {
		$denormalized = [
			'selector' => '',
			'sources' => [],
			'staticPrice' => '',
			'modifierEnabled' => [],
			'modifier' => [],
			'wallet' => [
				'id' => '',
				'mainPass' => '',
				'secondPass' => '',
				'fromAddress' => '',
			],
			'email' => [
				'username' => '',
				'password' => '',
			],
			'contact' => [
				'information' => '',
			],
			'transaction-cron' => false,
			'transactions' => [],
		];
		
		$cfgData = $cfg->asArray();
		
		if (isset($cfgData['transactions']['maximum'])) {
			$denormalized['transactions']['maximum'] = $cfgData['transactions']['maximum'];
		}
		
		if (!empty($cfgData['transaction-cron'])) {
			$denormalized['transaction-cron'] = $cfgData['transaction-cron'];
		}
		
		$denormalized['wallet'] = $cfgData['walletProvider'];
		unset($denormalized['wallet']['provider']);
		
		$denormalized['email'] = $cfgData['email'];
		if (empty($denormalized['email']['machine'])) {
			$denormalized['email']['machine'] = 'Project Skyhook 00';
		}
		
		$denormalized['contact']['information'] = @$cfgData['contact']['information'];
		
		$denormalized['selector'] = 'single';
		$pp = $cfg->getPricingProvider();
		if (!empty($pp)) {
			Config::walkPricingProviders(
				$cfg->getPricingProvider(),
				function ($p) use (&$denormalized) {
					$name = get_class($p);
					if ($p instanceof PricingProxy) {
						if ($p instanceof PriceModifier) {
							$denormalized['modifierEnabled'][$name] = true;
							$denormalized['modifier'][$name] = strval($p->getValue());
						} else {
							$denormalized['selector'] = $name;
						}
					} else {
						$denormalized['sources'][] = $name;
						if (preg_match('#.*StaticPrice$#', $name)) {
							$denormalized['staticPrice'] = strval($p->getPrice());
						}
					}
				}
			);
		}
		
		$denormalized['sources'] = implode(',', $denormalized['sources']);
		
		return $denormalized;
	}
	
	public static function test() {
		require "autoload.php";
		$cfg = Admin::volatileLoad()->getConfig();
		$d = self::denormalize($cfg);
		
		$n = self::normalize(new Post($d));
		
		var_dump($d);
		
		echo str_repeat("=", 80), "\n";
		
		$cfgData = $cfg->asArray();
		ksort($cfgData);
		ksort($n);
		
		var_dump($cfgData);
		echo str_repeat("=", 80), "\n";
		var_dump($n);
		
	}
}

//ConfigNormalizer::test();
