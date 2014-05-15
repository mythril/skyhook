CREATE TABLE IF NOT EXISTS `bills` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,

-- This should never be set in our PHP, let the database handle it
	`entered_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

-- The numeric denomination that was entered
	`denomination` DECIMAL(16, 4) NOT NULL DEFAULT 0.0000,

-- The purchase this bill was entered for
	`purchase_id` INT(11) NOT NULL,
	
	PRIMARY KEY `this_id` (`id`), 
	KEY `purchase_ind` (`purchase_id`),
	FOREIGN KEY (`purchase_id`)
		REFERENCES `purchases`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;

