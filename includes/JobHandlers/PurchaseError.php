<?php

namespace JobHandlers;
use JobHandler;

class PurchaseError implements JobHandler {
	use PurchaseErrorMailer;
	public function work(array $row) {
		$this->sendMail(
			'There was an error processing ticket #' . $row['purchase_id'],
			'emails/purchase-error',
			$row['purchase_id']
		);
	}
}
