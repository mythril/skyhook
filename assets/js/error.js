$(function () {
	var gt = new Gettext({domain: 'secondary'});
	function _(msgid) { return gt.gettext(msgid); }
	var save = $('#save');
	var email = $('#email');
	var ticketId = $('#ticket').val();
	var inFlight = false;
	
	save.on('click', function (e) {
		e.preventDefault();
		if (!inFlight) {
			inFlight = true;
			$.getJSON(
				'/add-email-to-ticket/' + ticketId,
				{email: email.val()},
				function (result) {
					var title, message, url;
					if (result.invalid) {
						title = _('Invalid Email Address');
						message = _('Specified address appears invalid.');
						url = false;
					} else {
						title = _('Success');
						message = _('Email address stored.');
						url = '/';
						if (/\?insufficient\=1/.test(window.location.href)) {
							url = '/admin/minimum-balance';
							debugger;
						}
					}
					new Messi(
						message,
						{
							title: title,
							modal: true,
							center: true,
							closeButton: false,
							buttons: [{
								id: 'ok',
								label: _('Ok'),
								val: 'ok',
								class: 'btn-primary'
							}],
							callback: function () {
								if (url) {
									window.location.replace(url);
								}
							}
						}
					);
				}
			).always(function () {
				inFlight = false;
			});
		}
	});
});
