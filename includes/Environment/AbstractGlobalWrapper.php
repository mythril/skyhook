<?
namespace Environment;

use ArrayAccess;

/**
 * Provides simple wrapping facilities for super globals
 */
abstract class AbstractGlobalWrapper implements ArrayAccess {
	/**
	 * Super global that is being wrapped
	 * @var array
	 */
	protected $backing;

	/**
	 * Stores a reference to the global
	 *
	 * @param array &$b super global to wrap
	 */
	public function __construct(array &$b) {
		$this->backing = &$b;
	}
	
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->backing);
	}
	
	public function offsetGet($offset) {
		return $this->backing[$offset];
	}
	
	public function offsetSet($offset, $value) {
		$this->backing[$offset] = $value;
	}
	
	public function offsetUnset($offset) {
		unset($this->backing[$offset]);
	}
}

