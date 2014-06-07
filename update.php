<?php

require_once __DIR__ . '/includes/autoload.php';

$db = new DB(
	new DateTimeZone(trim(file_get_contents('/etc/timezone')))
);

$stmt = $db->query("SHOW COLUMNS FROM `purchases`;");

$addEmailToNotify = true;
$addNTXID = true;
$fixPrecision = true;

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
	if ($row['Field'] === 'email_to_notify') {
		$addEmailToNotify = false;
	}
	if ($row['Field'] === 'bitcoin_amount') {
		if (strpos($row['Type'], '16') !== false) {
			$fixPrecision = false;
		}
	}
	if ($row['Field'] === 'ntxid') {
		$addNTXID = false;
	}
}

if ($addEmailToNotify) {
	$db->query('
		ALTER TABLE `purchases`
			ADD COLUMN
				email_to_notify TEXT NULL DEFAULT NULL AFTER notice;
	');
	echo "Column 'email_to_notify' added.\n";
}

if ($addNTXID) {
	$db->query('
		ALTER TABLE `purchases`
			ADD COLUMN
				`ntxid` VARCHAR(64) NULL DEFAULT NULL AFTER txid;
	');
	echo "Column 'ntxid' added.\n";
}

if ($fixPrecision) {
	$db->query('
		ALTER TABLE `purchases`
			MODIFY COLUMN
				bitcoin_amount DECIMAL(16, 8) NOT NULL DEFAULT 0.0000
	');
	echo "Column 'bitcoin_amount' fixed.\n";
}

$stmt = $db->query(file_get_contents(__DIR__ . '/database/jobs.sql'));

echo "update finished\n";
