<?php

namespace JobHandlers;
use JobHandler;

class PurchaseErrorEmailProvided implements JobHandler {
	use PurchaseErrorMailer;
	public function work(array $row) {
		$this->sendMail(
			'Update: an email address was provided for ticket #' . $row['purchase_id'],
			'emails/purchase-error-email-provided',
			$row['purchase_id']
		);
	}
}
