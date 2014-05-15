<?php

class HTML {
	public function escape($string) {
		return htmlspecialchars($string, ENT_COMPAT | ENT_HTML5, 'UTF-8', true);
	}
	
	public function unescape($string) {
		return html_entity_decode($string, ENT_COMPAT | ENT_HTML5, 'UTF-8');
	}
}
