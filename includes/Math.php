<?

class Math {
	private static function reduce($fn, array $amts) {
		if (empty($amts)) {
			return [];
		}
		
		$prev = array_shift($amts);
		
		foreach ($amts as $amt) {
			if (!$prev instanceof Amount) {
				throw new InvalidArgumentException();
			}
			
			$prev = $fn($prev, $amt);
		}
		
		return $prev;
	}
	
	private static function filter($fn, $amts) {
		$result = [];
		
		if (empty($amts)) {
			return [];
		}
		
		foreach ($amts as $key => $amt) {
			if (!$amt instanceof Amount) {
				throw new InvalidArgumentException();
			}
			if ($fn($amt, $key)) {
				$result[$key] = $amt;
			}
		}
		
		return $result;
	}
	
	private static function map($fn, $amts) {
		$result = [];
		
		if (empty($amts)) {
			return [];
		}
		
		foreach ($amts as $key => $amt) {
			if (!$amt instanceof Amount) {
				throw new InvalidArgumentException();
			}
			$result[$key] = $fn($amt, $key);
		}
		
		return $result;
	}
	
	public static function min(array $amts) {
		return self::reduce(function (Amount $a, Amount $b) {
			if ($a->isLessThan($b)) {
				return $a;
			}
			return $b;
		}, $amts);
	}
	
	public static function max(array $amts) {
		return self::reduce(function (Amount $a, Amount $b) {
			if ($a->isGreaterThan($b)) {
				return $a;
			}
			return $b;
		}, $amts);
	}
}
