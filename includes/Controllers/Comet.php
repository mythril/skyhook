<?php

namespace Controllers;
use Template;
use Container;
use JSON;
use Iterator;

trait Comet {
	private function interval() {
		$SECONDS = 1000000;
		$MS = $SECONDS / 1000;
		return 100 * $MS;
	}
	
	public function start() {
		//asks client to treat this document as uncachable
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

		//kills the default output buffer:
		while (ob_get_level()) {
			ob_end_flush(); 
		}

		//disables php's script terminator
		set_time_limit(0);

		//flush headers
		flush();
		
		$template = new Template('comet-begin');
		$template->render();
		
		//flush HTML
		flush();
	}
	
	public function beforeSend($data) {
		return;
	}
	
	private $count = 0;
	private function increment() {
		$this->count += 1;
		return $this->count;
	}
	
	private function intercept($data) {
		return $data;
	}
	
	public function send($data) {
		$this->beforeSend($data);
		$data = $this->intercept($data);
		flush();
		$c = $this->increment();
		echo '<script type="text/javascript" id="comet-packet-', $c, '" >';
		echo 'CometBroadcast(', JSON::encode($data), ', "*", ', $c, ');';
		echo '</script>';
		flush();
		$this->afterSend($data);
	}

	public function afterSend($data) {
		return;
	}
	
	public function end() {
		echo "</body></html>";
	}
	
	public function drain(Iterator $i) {
		$interval = $this->interval();
		
		$this->start();
		
		foreach($i as $datum) {
			$this->send($datum);
			usleep($interval);
			if (connection_aborted()) {
				break;
			}
		}
		
		$this->end();
	}
}

