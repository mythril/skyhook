<?php

interface WalletProvider extends Configurable {
	public function sendTransaction(BitcoinAddress $where, Amount $howMuch);
}


