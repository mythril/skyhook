$(document).on('pageshow', function () {
	var timer;
	
	function redirect() {
		window.location.replace('/start');
	}
	
	function resetTimer() {
		if (timer) {
			window.clearTimeout(timer);
		}
		timer = window.setTimeout(redirect, 60*1000);
	}
	
	$(document.body).on('click focus change blur', resetTimer);
	resetTimer();
});
