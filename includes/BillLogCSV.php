<?php

class BillLogCSV {
	private $db;
	
	public function __construct(DB $db) {
		$this->db = $db;
	}
	
	/**
	 * Generate's a CSV containing all the transactions
	 *
	 * @return string filename of the CSV
	 */
	public function save($purchaseId) {
		$fn = "/home/pi/phplog/billLog." . $purchaseId . ".csv";
		
		$stmt = $this->db->prepare("
			SELECT *
			FROM `bills`
			WHERE `purchase_id` = :purchaseId
		");
		
		if (!$stmt->execute([':purchaseId' => $purchaseId])) {
			error_log(var_export($stmt->errorInfo(), true));
			return false;
		}
		
		if ($stmt->rowCount() < 1) {
			return false;
		}
		
		$row1 = $stmt->fetch(PDO::FETCH_ASSOC);
		
		//TODO check the return values of the f* functions
		$f = fopen($fn, "w");
		
		fputcsv($f, array_keys($row1));
		fputcsv($f, array_values($row1));
		
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			fputcsv($f, array_values($row));
		}
		
		fclose($f);
		
		return realpath($fn);
	}
	
	public static function test() {
		require_once __DIR__ . '/autoload.php';
		$b = new BillLogCSV(Container::dispense('DB'));
		echo "CSV Saved as: ", $b->save(19), "\n";
	}
}


