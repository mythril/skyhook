<?

class Hourly {
	private static $fn = '/home/pi/phplog/hourly';
	
	private static function timeElapsed() {
		if (!file_exists(self::$fn)) {
			return true;
		}
		$last = intval(trim(file_get_contents(self::$fn)));
		return time() > ($last + (60 * 60));
	}
	
	private static function done() {
		file_put_contents(self::$fn, time());
	}
	
	public static function doWork(array $fns) {
		if (self::timeElapsed()) {
			foreach ($fns as $fn) {
				$fn();
			}
			self::done();
		}
	}
}
