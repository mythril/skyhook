<?
use Controllers\Controller;

/**
 * Aid in the routing of requests to their respective handlers
 */
class Router {
	/**
	 * Creates a routing handler which instantiates and uses a class
	 * as the next step in routing
	 *
	 * @param string $classname class to instantiate
	 * @throws UnexpectedValueException when Controller is not implemented by the
	 * classname in question
	 * @return mixed result of routing, false if not matched
	 */
	public static function lazyLoad($classname) {
		return function ($matches, $url, $rest) use ($classname) {
			$routable = Container::dispense($classname);
			if ($routable instanceof Controller) {
				return $routable->execute($matches, $url, $rest);
			}
			throw new UnexpectedValueException($classname . " does not implement Controller");
		};
	}

	/**
	 * Replaces a named url section with it's regular expression equivalent
	 *
	 * @param string $route potentially containing named url sections
	 * @return string regular expression with named matching groups
	 */
	protected function replaceNamed($route) {
		return preg_replace_callback(
			'#\:([^/$]+)#',
			function ($matches) {
				return '(?P<' . $matches[1] . '>[^/]+)';
			},
			$route
		);
	}

	/**
	 * Checks to see if a given url matches a given route
	 *
	 * @param string $route raw route
	 * @param string $url to match route against
	 * @return mixed set of matches in the same format as `preg_replace_callback`, `false` if no matches detected
	 * @throws RouteException when an invalid route is encountered
	 */
	protected function routeMatches($route, $url) {
		//replace named
		$route = '#^' . $this->replaceNamed($route) . '#';

		$matches = array();

		//match for named groups
		//check matches for validity
		if (preg_match_all($route, $url, $matches, PREG_SET_ORDER) < 1) {
			return false;
		}

		//return matches
		return $matches[0];
	}

	/**
	 * Finds a url's matching route and dispatches the respective route handler.
	 * The array of routes should come in the following format:
	 * each element of the array must also be an array, where the 0th index
	 * is a route to match and the 1st index is a handler to invoke when a
	 * route matches
	 *
	 * @param string $url the current routing context url
	 * @param array $routes and their respective handlers
	 * @return mixed false if unresolved, array keyed with 'route' regular expression that matched, and 'index'
	 * the index of the passed routes that matched 'result' being whatever the handler returned
	 */
	public function resolve($url, array $routes) {
		$idx = -1;

		foreach ($routes as $route) {
			$idx += 1;

			if (!$route) {
				// this allows short-circuitiing of this route, for use in filtering
				continue;
			}

			$path = $route[0];
			$matches = $this->routeMatches($path, $url);

			if ($matches === false) {
				// route regex did not match
				continue;
			}

			$handler = $route[1];

			if ($handler instanceof Controller) {
				// not a simple handler, creating a callable handler
				$handler = array($handler, 'execute');
			}

			$rest = substr($url, strlen($matches[0]));
			$result = $handler($matches, $url, $rest);

			if ($result === false) {
				// handler does not wish to handle this route
				continue;
			}

			return array(
				'route' => $path,
				'index' => $idx,
				'result' => $result
			);
		}
		return false;
	}
}
