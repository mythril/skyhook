CREATE TABLE IF NOT EXISTS `purchases` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,

-- This should never be set in our PHP, let the database handle it
	`initiated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

-- Could be stored as a 256-bit int, but human readable is better for debugging
	`customer_address` VARCHAR(34) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,

-- A sum of the currency entered into the machine on this purchase ticket
	`currency_amount` DECIMAL(16, 4) NOT NULL DEFAULT 0.0000,

-- The Bitcoin price as calculated at the beginning of the ticket
	`bitcoin_price` DECIMAL(16, 4) NOT NULL,

-- The amount of Bitcoin that can be purchased in relation to the amount of currency entered
	`bitcoin_amount` DECIMAL(8, 8) NOT NULL DEFAULT 0.0000,

-- The Bitcoin network TXID that establishes the transaction has happened
	`txid` VARCHAR(64) NULL DEFAULT NULL,

-- The Blockchain.info NTXID that establishes the transaction has happened
-- Normalized to assist in fighting transaction malleability problems
	`ntxid` VARCHAR(64) NULL DEFAULT NULL,

-- Look at Purchase constants in the Purchase PHP Object
	`status` INT(11) NOT NULL DEFAULT 0,

-- Currency code for paper currency involved in this transaction
	`cur_code` VARCHAR(3) NOT NULL,

-- The time the purchase was finalized by the user
	`finalized_at` TIMESTAMP NULL DEFAULT NULL,

-- A message provided by the wallet service
	`message` TEXT NOT NULL,

-- Notice provided by wallet service
	`notice` TEXT NOT NULL,

-- Email address to notify if a transaction gets stuck
	`email_to_notify` TEXT NULL DEFAULT NULL,

	PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10001 DEFAULT CHARSET=latin1;

