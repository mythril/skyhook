<?php

interface Observable {
	public function notifyObservers($eventName, array $description);
	public function attachObserver($eventName, callable $observer);
}
