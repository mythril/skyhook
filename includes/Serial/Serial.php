<?php

namespace Serial;

interface Serial {
	const PARITY_NONE = 0;
	const PARITY_ODD = 1;
	const PARITY_EVEN = 2;
	public function getDevice();
	public function setBaudRate($rate);
	public function getBaudRate();
	public function setParity($parity);
	public function getParity();
	public function setByteSize($size);
	public function getByteSize();
	public function setStopBits($length);
	public function getStopBits();
	public function open($mode = "r+b");
	public function close();
	public function readLine();
	public function readAvailable();
	public function readBytes($count = 0);
	public function write($str);
	public function writeBytes(array $bytes);
	public function hasDataAvailable();
	public function isOpen();
}
