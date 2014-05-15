<?
use Exceptions\ContainerException;

/**
 * A general purpose object instantiator that provides inversion of control.
 */
class Container {
	/**
	 * Map from types to builders of those types
	 * @var array
	 */
	protected static $builderMap = array();

	/**
	 * Map indicating whether or not a type should be used as a singleton
	 * @var array
	 */
	protected static $singletons = array();

	/**
	 * Available singletons
	 * @var array
	 */
	protected static $instances = array();

	/**
	 * Cleans the name provided
	 *
	 * @param string $name Name to be cleaned
	 * @return string name with leading namespace pather removed
	 */
	protected static function cleanName($name) {
		return ltrim($name, '\\');
	}

	/**
	 * Bind a concrete class to an abstraction name
	 *
	 * @param string $abstract class or interface name
	 * @param string $concrete class that implements/extends $abstract
	 * @param boolean $overwrite whether or not this binding supercedes previous ones
	 * @return void
	 * @throws ContainerException when the type is already registered
	 */
	public static function bind($abstract, $concrete, $overwrite = false) {
		$abstract = self::cleanName($abstract);
		$concrete = self::cleanName($concrete);
		if (!$overwrite && isset(self::$builderMap[$abstract])) {
			throw new ContainerException("Type '{$abstract}' already regisered.");
		}
		self::$builderMap[$abstract] = function () use ($concrete) {
			return Container::dispense($concrete);
		};
	}

	/**
	 * Registers a builder for a given type
	 *
	 * @param string $type name of class/interface to resolve
	 * @param callable $builder a factory function to assemble the associated type
	 * @param boolean $overwrite whether or not to bump an existing registered builder
	 * @return void
	 * @throws ContainerException when the type is already registered and the $overwrite flag is false
	 */
	public static function register($type, callable $builder, $overwrite = false) {
		$type = self::cleanName($type);
		if (!$overwrite && isset(self::$builderMap[$type])) {
			throw new ContainerException("Type '{$type}' already regisered.");
		}
		self::$builderMap[$type] = $builder;
	}

	/**
	 * Registers a builder for a given type, as a singleton
	 *
	 * @param string $type name of class/interface to resolve
	 * @param callable $builder a factory function to assemble the associated type
	 * @return void
	 * @throws ContainerException when the type is already registered
	 */
	public static function registerSingleton($type, callable $builder = null) {
		$type = self::cleanName($type);
		if (isset(self::$singletons[$type])) {
			throw new ContainerException("Singleton '{$type}' already registered.");
		}
		self::$singletons[$type] = true;
		if ($builder) {
			self::$builderMap[$type] = $builder;
		}
	}

	/**
	 * Recursively constructs all the dependencies of a given $type
	 *
	 * @param string $type class to instantiate
	 * @return Object instantiated class
	 * @throws ContainerException when constructor is not public or is abstract
	 */
	protected static function dispenseConstructor($type) {
		$type = self::cleanName($type);
		$class = new ReflectionClass($type);
		$ctor = $class->getConstructor();
		if (!$ctor) {
			return new $type;
		}
		if (!$ctor->isPublic()) {
			throw new ContainerException("Constructor of '{$type}' is not public.");
		}
		if ($ctor->isAbstract()) {
			throw new ContainerException("Constructor of '{$type}' is abstract.");
		}
		$args = [];
		foreach ($ctor->getParameters() as $arg) {
			if ($arg->isDefaultValueAvailable()) {
				$args[] = $arg->getDefaultValue();
				continue;
			}
			$className = $arg->getClass();
			if ($className) {
				$args[] = self::dispense($className->name);
			} else {
				throw new ContainerException(
					'Argument '
					. $arg->getPosition()
					. " of {$class->getName()} is not an object, "
					. "and therefore requires a registered builder "
					. " (see Container::register)."
				);
			}
		}
		return $class->newInstanceArgs($args);
	}

	/**
	 * Constructs a given $type and returns it, or if it has been registered as
	 * a singleton and already built, returns the previously built singleton
	 *
	 * @param string $type type to construct
	 * @return Object constructed object
	 * @throws ContainerException when the builder cannot be found
	 */
	public static function dispense($type) {
		$type = self::cleanName($type);
		if (isset(self::$instances[$type])) {
			return self::$instances[$type];
		}

		$reflectOnFailure = false;

		// attempt to load class $type
		if (class_exists($type, true)) {
			$reflectOnFailure = true;
		}

		$built = null;
		// locate builder
		if (isset(self::$builderMap[$type])) {
			// dispense, via builder
			$closure = self::$builderMap[$type];
			$built = $closure();
		} else if ($reflectOnFailure) {
			// create via constructor reflection
			$built = self::dispenseConstructor($type);
		}
		if (empty($built)) {
			throw new ContainerException("Builder for type: '{$type}' not found.");
		}
		if (isset(self::$singletons[$type])) {
			self::$instances[$type] = $built;
		}
		return $built;
	}
}

