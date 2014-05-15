<?

class Math {
	private static function filter($fn, array $amts) {
		if (empty($amts)) {
			return [];
		}
		
		$prev = array_shift($amts);
		
		if (!$prev instanceof Amount) {
			throw new InvalidArgumentException();
		}
		
		foreach ($amts as $amt) {
			$prev = $fn($prev, $amt);
		}
		
		return [$prev];
	}
	
	public static function min(array $amts) {
		return self::filter(function (Amount $a, Amount $b) {
			if ($a->isLessThan($b)) {
				return $a;
			}
			return $b;
		}, $amts);
	}
	
	public static function max(array $amts) {
		return self::filter(function (Amount $a, Amount $b) {
			if ($a->isGreaterThan($b)) {
				return $a;
			}
			return $b;
		}, $amts);
	}
}
