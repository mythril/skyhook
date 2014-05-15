<?
namespace Controllers;

/**
 * When implemented, indicates that an object knows how to handle a route.
 */
interface Controller {
	/**
	 * The callback that is called when a route is resolved to this object
	 *
	 * @param array $matches an array of matches in the format of @see preg_match_all using PREG_SET_ORDER
	 * @param string $url the full that is currently being resolved
	 * @param string $rest the remainder of the url after all previous matches have been removed
	 * @return boolean whether or not this Route resolved the request
	 */
	public function execute(array $matches, $url, $rest);
}
