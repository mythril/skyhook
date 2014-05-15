<?
namespace Environment;

/**
 * Wraps the $_SERVER super global for testable access
 */
class Server extends AbstractGlobalWrapper {

	/**
	 * Page Url
	 *
	 * @return string requested page URL
	 */
	public function pageUrl() {
		$path = dirname($this->backing['SCRIPT_NAME']);
		$path = rtrim($path, "/");
		return substr(
			$this->backing['REQUEST_URI'],
			strlen($path)
		);
	}

	/**
	 * Referer
	 * @return string referring URL
	 */
	public function referer() {
		if (isset($this->backing['HTTP_REFERER'])) {
			return $this->backing['HTTP_REFERER'];
		}

		return false;
	}
}
