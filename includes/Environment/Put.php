<?
namespace Environment;

use Environment\Server;

/**
 * Provides an array with HTTP PUT variables
 */
class Put extends AbstractGlobalWrapper {
	/**
	 * Reads the request body and return a representative array
	 *
	 * @param Server $s server wrapper for use in determining HTTP method
	 * @return array containing the request body decoded
	 */
	public static function buildHelper(Server $server, $source = 'php://input') {
		$put = [];
		if ($server['REQUEST_METHOD'] === 'PUT') {
			parse_str(file_get_contents($source), $put);
		}
		return $put;
	}
}

