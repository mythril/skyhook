<?
// Grumble grumble database as ipc - sorry
class JobManager {
	const QUEUED = 'queued';
	const PROCESSING = 'processing';
	const FINISHED = 'finished';
	
	private static $initialized = false;
	private static $handlers = [];
	
	private static function addHandler($handler) {
		if (array_search('JobHandler', class_implements($handler)) !== false) {
			$k = explode("\\", $handler);
			$k = $k[1];
			self::$handlers[$k] = $handler;
		}
	}
	
	public static function _init() {
		if (self::$initialized) {
			return;
		}
		
		foreach (glob(__DIR__ . '/JobHandlers/*.php') as $file) {
			$class = "JobHandlers\\" . basename($file, ".php");
			self::addHandler($class);
		}
		
		self::$initialized = true;
	}
	
	public static function enqueue(DB $db, $handler, array $message) {
		if (!isset(self::$handlers[$handler])) {
			throw new UnexpectedValueException('Unsupported handler (' . $handler . ').');
		}
		
		$prepared = $db->prepare("
			INSERT INTO `jobs` (
				`handler`,
				`status`,
				`message`
			) VALUES (
				:handler,
				:status,
				:message
			)
		");
		
		$result = $prepared->execute([
			':handler' => $handler,
			':status' => self::QUEUED,
			':message' => JSON::encode($message)
		]);
	}
	
	private $db;
	
	public function __construct(DB $db) {
		$this->db = $db;
	}
	
	private function setStatus($which, $status) {
		$prepared = $this->db->prepare('
			UPDATE `jobs`
			SET `status` = :status
			WHERE `id` = :id
		');
		
		$result = $prepared->execute([
			':status' => $status,
			':id' => $which
		]);
	}
	
	private function processTen() {
		$prepared = $this->db->prepare('
			SELECT *
			FROM `jobs`
			WHERE `status` = :status
			ORDER BY `created_at`
			LIMIT 10
		');
		
		$prepared->execute([
			':status' => self::QUEUED
		]);
		
		foreach ($prepared->fetchAll(PDO::FETCH_ASSOC) as $row) {
			if (isset(self::$handlers[$row['handler']])) {
				$class = self::$handlers[$row['handler']];
				$worker = new $class();
				$this->db->beginTransaction();
				try {
					$this->setStatus($row['id'], self::PROCESSING);
					$worker->work(JSON::decode($row['message']));
					$this->setStatus($row['id'], self::FINISHED);
					$this->db->commit();
				} catch (Exception $e) {
					echo $e;
					$this->db->rollBack();
					error_log('job_log: ' . $e);
				}
			}
		}
	}
	
	public function doWork($until = PHP_INT_MAX) {
		$stop = time() > $until;
		
		while (!$stop) {
			$this->processTen();
			sleep(1);
			$stop = time() > $until;
		}
	}
}

JobManager::_init();
