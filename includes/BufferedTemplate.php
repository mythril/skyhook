<?php

class BufferedTemplate extends Template {
	public function getBuffer(array $symbols) {
		ob_start();
		$this->render($symbols);
		return ob_get_clean();
	}
}
