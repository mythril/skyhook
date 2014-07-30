<?php

interface WalletProvider extends Configurable {
	public function sendTransaction(BitcoinAddress $where, Amount $howMuch);
	public function getBalance($confirmations = 1);
	public function verifyOwnership();
	public function getWalletAddress();
}


