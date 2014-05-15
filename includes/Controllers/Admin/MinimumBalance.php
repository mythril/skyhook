<?

namespace Controllers\Admin;
use Controllers\Controller;
use Template;
use Container;
use Admin as AdminConfig;
use Environment\Get;

class MinimumBalance implements Controller {
	public function execute(array $matches, $url, $rest) {
		$config = AdminConfig::volatileLoad()->getConfig();
		$get = Container::dispense('Environment\Get');
		$error = 0;
		if (isset($get['error'])) {
			$error = (int)$get['error'];
		}
		$symbols = [
			'errorCode' => $error,
			'contactInfo' => $config->getContactInformation()
		];
		$tmpl = new Template('admin/minimum-balance');
		$tmpl->render($symbols);
		return true;
	}
}

