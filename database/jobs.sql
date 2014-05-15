CREATE TABLE IF NOT EXISTS `jobs` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,

-- This should never be set in our PHP, let the database handle it
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

-- Name of the handler class that will process the job
	`handler` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,

-- The current status of the job
	`status` ENUM('queued', 'processing', 'finished') NOT NULL,

-- JSON formatted message contents
	`message` TEXT NOT NULL,

	PRIMARY KEY `this_id` (`id`),
	KEY `created_at_ind` (`created_at`),
	KEY `status_ind` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;

