<?php

trait Observed {
	private $observers = [];
	
	public function attachObserver($eventName, callable $observer) {
		if (empty($this->observers[$eventName])) {
			$this->observers[$eventName] = [];
		}
		$this->observers[$eventName][] = $observer;
		return $this;
	}
	
	public function notifyObservers($eventName, array $description) {
		if (empty($this->observers[$eventName])) {
			return $this;
		}
		
		foreach ($this->observers[$eventName] as $observer) {
			$observer($description);
		}
		return $this;
	}
}
