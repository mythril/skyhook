(function (global) {
	var isConnected = true;
	global.Network = {isConnected: function () {
		return isConnected;
	}};
	
	$(function () {
		var interval = 60;
		var contactInfo = $('#errorContact');
		var hideContact = $.trim(contactInfo.text()) === '';
		var errorPage = $('#errorPage');
		var shown = false;
		
		function showError() {
			isConnected = false;
			contactInfo.toggleClass('hidden', hideContact);
			errorPage.removeClass('hidden').show();
			shown = true;
		}
		
		function networkCheck() {
			$.ajax('/nettest', {
				dataType: 'json',
				cache: false,
				error: function (xhr, textStatus, errorThrown) {
					showError();
					window.setTimeout(networkCheck, interval*1000);
				},
				timeout: 5*1000
			}).fail(function () {
				showError();
				window.setTimeout(networkCheck, interval*1000);
			}).done(function (data) {
				if (data.error) {
					showError();
				} else if (shown) {
					window.location.replace('/start');
				}
				window.setTimeout(networkCheck, interval*1000);
			});
		}
		
		errorPage.on('click touchstart mousedown', function (e) {
			e.stopPropagation();
		});
		
		networkCheck();
	});
}(window));
