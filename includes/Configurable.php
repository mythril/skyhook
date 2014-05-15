<?php

interface Configurable {
	public function configure(array $options);
	public function isConfigured();
}

