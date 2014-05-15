<?php

interface JobHandler {
	public function work(array $row);
}
