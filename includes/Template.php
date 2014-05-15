<?
/**
 * Dynamic template with (hopefully) limited logic, looks to *.phtml files,
 * allows for lazy evaluation. Data may be escaped before being passed to
 * render(); but it is generally preferrable to escape data at output.
 */
class Template {
	private $page;
	public function __construct($page) {
		$this->page = $page;
	}
	
	private function getPage() {
		return realpath(__DIR__ . '/../pages/' . $this->page . '.phtml');
	}
	
	/**
	 * Imports symbol table into template file's execution context, rendering
	 * as the file dictates
	 *
	 * @param array $symbols to import
	 * @return void
	 */
	public function render(array $symbols = array()) {
		extract($symbols);
		unset($symbols);
		$html = new HTML();
		$i18n = Localization::getTranslator();
		include $this->getPage();
	}
}
