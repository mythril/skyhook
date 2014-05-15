<?
namespace Environment;

/**
 * Wraps the basic interactions with the session super global ($_SESSION)
 */
class Session extends AbstractGlobalWrapper {
	/**
	 * Session ID (usually provided by <code>session_id()</code>)
	 * @var string
	 */
	private $id;

	/**
	 * Session Name (defaults to the site's name)
	 * @var string
	 */
	private $name;

	/**
	 * A callable function which will regenerate (and possibly delete) a session
	 * @var array
	 */
	private $regenerator;

	/**
	 * Construcst a new session wrapper
	 * <code>$regenerator</code> signature:
	 * <code>string function(bool $delete)</code>
	 * The regenerator function ought to return the new Session ID
	 *
	 * @param array &$b $_SESSION array to wrap
	 * @param string $id session identifier
	 * @param string $name session name
	 * @param callable &$regenerator regenerates session, optionally deleting old one
	 */
	public function __construct(array &$b, $id, $name, callable &$regenerator) {
		$this->id = $id;
		$this->name = $name;
		$this->regenerator = &$regenerator;
		parent::__construct($b);
	}

	/**
	 * Retrieves the wrapped session id
	 *
	 * @return string session id
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Retrieves the wrapped session name
	 *
	 * @return string session name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Regenerates the session using regenerator provided during object
	 * construction
	 *
	 * @param boolean $delete whether or not to delete existing session
	 * @return void
	 */
	public function regenerate($delete = false) {
		$this->id = $this->regenerator->__invoke($delete);
	}
}
