var HelpPanel = {onOpen: function () {}, onClose: function () {}};

$(function () {
	var hPanel = $('#help-panel');
	var closeBtn = $('#help-panel > .closer');
	//<img src="/assets/images/settings.png" />
	var adminSecret = $('<a href="#"></a>')
		.attr('id', 'admin-secret')
		.addClass('button')
		.append($('<span class="fa fa-gear"></span>'))
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
	
	hPanel.on('click focus blur touchstart mousedown touchend change', resetTimeout);
	
	closeBtn.on(CLICK, close);
	
	helpNav.on(CLICK, 'a', function (e) {
		e.preventDefault();
		var target = $($(this).attr('href'));
		activateHelpSection(target);
	});
	
	$('#help-content').on(CLICK, 'a.back', function (e) {
		e.preventDefault();
		activateHelpSection(helpNav);
	});
	
	adminSecret
		.on(CLICK, function () {
			window.location.replace('/admin/login?redirect=1');
		});
	
	$('#help').on(CLICK, open);
});
