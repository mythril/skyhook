var HelpPanel = {onOpen: function () {}, onClose: function () {}};

$(function () {
	var hPanel = $('#help-panel');
	var closeBtn = $('#help-panel > .closer');
	//<img src="/assets/images/settings.png" />
	var adminSecret = $('<a href="#">&#x2699;</a>')
		.attr('id', 'admin-secret')
		.addClass('button')
		.appendTo(hPanel);
	
	function activateHelpSection(target) {
		$('.help-section').removeClass('active');
		target.addClass('active');
	}
	
	var helpNav = $('#help-nav');
	var timeout;
	
	function close(e) {
		if (e) {
			e.preventDefault();
			e.stopPropagation();
		}
		hPanel.removeClass('visible');
		HelpPanel.onClose();
		activateHelpSection(helpNav);
	}
	
	function resetTimeout() {
		if (timeout) {
			window.clearTimeout(timeout);
		}
		timeout = window.setTimeout(close, 60*1000);
	}
	
	function open(e) {
		HelpPanel.onOpen();
		e.preventDefault();
		hPanel.addClass('visible');
		resetTimeout();
	}
	
	hPanel.on('click focus blur touchstart touchend change', resetTimeout);
	
	closeBtn.on('click', close);
	
	helpNav.on('click', 'a', function (e) {
		e.preventDefault();
		var target = $($(this).attr('href'));
		activateHelpSection(target);
	});
	
	$('#help-content').on('click', 'a.back', function (e) {
		e.preventDefault();
		activateHelpSection(helpNav);
	});
	
	adminSecret
		.on('mousedown touchstart', function () {
			window.location.replace('/admin/login?redirect=1');
		});
	
	new MBP.fastButton(document.getElementById('help'), open);
});
