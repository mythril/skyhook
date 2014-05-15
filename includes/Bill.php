<?php

class Bill {
	private $id;
	private $denomination;
	private $enteredAt;
	private $purchaseId;
	
	private function __construct() { }
	
	public static function create(DB $db, Amount $denom, Purchase $p) {
		$prepared = $db->prepare('
			INSERT INTO `bills` (
				`entered_at`,
				`denomination`,
				`purchase_id`
			) VALUES (
				NOW(),
				:denomination,
				:purchase_id
			)
		');
		
		$result = $prepared->execute(array(
			':denomination' => $denom->get(),
			':purchase_id' => $p->getId()
		));
		
		if ($result === false) {
			throw new Exception("Unable to log bill.");
		}
		
		return self::load($db, $db->lastInsertId());
	}
	
	public static function load(DB $db, $id) {
		$stmt = $db->prepare('
			SELECT
				`id`,
				`entered_at`,
				`denomination`,
				`purchase_id`
			FROM `bills`
			WHERE `id`=:id
		');
		
		$stmt->execute(array(
			':id' => $id
		));
		
		$row = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
		$bill = new self();
		
		return $bill
			->setEnteredAt(new DateTime($row['entered_at']))
			->setDenomination(new Amount($row['denomination']))
			->setPurchaseId($row['purchase_id'])
			->setId($id);
	}
	
	private function setEnteredAt(DateTime $ea) {
		$this->enteredAt = $ea;
		return $this;
	}
	
	private function setDenomination(Amount $denom) {
		$this->denomination = $denom;
		return $this;
	}
	
	private function setPurchaseId($id) {
		$this->purchaseId = $id;
		return $this;
	}
	
	private function setId($id) {
		$this->id = $id;
		return $this;
	}
	
	public function getEnteredAt() {
		return $this->enteredAt;
	}
	
	public function getDenomination() {
		return $this->denomination;
	}
	
	public function getPurchaseId() {
		return $this->purchaseId;
	}
	
	public function getId() {
		return $this->id;
	}
	
}
