$(function () {
	var gt = new Gettext({domain: 'secondary'});
	function _(msgid) { return gt.gettext(msgid); }

	var timer;
	
	function startOverWhenIdle() {
		var timestamp = (+new Date()) / 1000;
		var interval;
		if (!Network.isConnected()) {
			return;
		}
		new Messi(
			'<div id="timer">30</div>',
			{
				title: _('Do you need more time?'),
				modal: true,
				center: true,
				closeButton: false,
				buttons: [{
					id: 'ok',
					label: _('More Time'),
					val: 'ok',
					class: 'btn-primary'
				}],
				callback: function () {
					resetTimer();
					window.clearInterval(interval);
				}
			}
		);
		interval = window.setInterval(function () {
			var secondsLeft = 30 - Math.round(((+new Date()) / 1000) - timestamp);
			if (secondsLeft <= 0) {
				if (typeof window.Comet !== 'undefined') {
					window.Comet.closeAll();
				}
				window.clearInterval(interval);
				window.location.replace('/start');
				return;
			}
			$('#timer').text(secondsLeft);
		}, 500);
	}
	
	function resetTimer () {
		if (timer) {
			window.clearTimeout(timer);
		}
		timer = window.setTimeout(startOverWhenIdle, 90*1000);
	}
	resetTimer();
	$(document.body).on('click focus blur tap touchstart mousedown', resetTimer);
});
