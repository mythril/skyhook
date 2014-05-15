<?php

use Exceptions\TransactionCSVException;

class TransactionCSV {
	private $db;
	
	public function __construct(DB $db) {
		$this->db = $db;
	}
	
	private $lastPurchase;
	
	public function getLastID() {
		return $this->lastPurchase;
	}
	
	/**
	 * Generate's a CSV containing all the transactions
	 *
	 * @return string filename of the CSV
	 */
	public function save($from = 0) {
		$fn = "/home/pi/phplog/transactions.csv";
		
		$stmt = $this->db->prepare("
			SELECT *
			FROM `purchases`
			WHERE `id` > :from
			ORDER BY `id`
		");
		
		if (!$stmt->execute([':from' => $from])) {
			error_log(var_export($stmt->errorInfo(), true));
			return false;
		}
		
		if ($stmt->rowCount() < 1) {
			throw new TransactionCSVException('No new purchases to send.');
		}
		
		$row1 = $stmt->fetch(PDO::FETCH_ASSOC);
		
		//TODO check the return values of the f* functions
		$f = fopen($fn, "w");
		
		fputcsv($f, array_keys($row1));
		fputcsv($f, array_values($row1));
		
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			fputcsv($f, array_values($row));
			$this->lastPurchase = $row['id'];
		}
		
		fclose($f);
		
		return realpath($fn);
	}
}


/*
Example usage:
*/
/*
$t = new TransactionCSV(new DB(include "cfg.php"));
echo "CSV Saved as: ", $t->save(), "\n";
//*/


