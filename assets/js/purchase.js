/*global Comet, $, Loading, Messi*/
$(function () {
	var gt = new Gettext({domain: 'secondary'});
	function _(msgid) { return gt.gettext(msgid); }

	var ticketId = String(window.location.href).split('/').pop();
	var bills,
		bitcoin;
	
	function updateTotal(total) {
		bills = total.bills;
		bitcoin = total.btc;
		$('#bills').text(bills);
		$('#btc').text(bitcoin);
		if (typeof total.diff === "number") {
			$('#low-balance').addClass('show');
			$('#remainder').text(total.diff);
		} else {
			$('#low-balance').removeClass('show');
		}
	}
	
	var cancelable = true;
	
	function disableCanceler() {
		cancelable = false;
	}
	
	function disableBillAcceptor() {
		$.get('/bill-acceptor/disable');
	}
	
	function runCanceler() {
		if (cancelable) {
			window.location.replace('/start');
		} else {
			window.location.replace('/error/' + ticketId);
		}
	}
	
	function confirmCancel(e) {
		e.preventDefault();
		new Messi(
			_('Cancel the transaction?'),
			{
				title: _('Cancel:'),
				modal: true,
				center: true,
				closeButton: false,
				buttons: [
					{
						id: 0,
						label: _('Cancel'),
						val: 'Y',
						class: 'btn-danger'
					},
					{
						id: 1,
						label: _('Continue'),
						val: 'N',
						class: 'btn-success'
					}
				],
				callback: function (val) {
					if (val === 'Y') {
						Loading.text(_('Processing'));
						Loading.show();
						disableBillAcceptor();
						window.setTimeout(runCanceler, 1000);
					}
				}
			}
		);
	}
	
	$('#canceler').on('click touchstart', confirmCancel);
	
	var handlers = {};
	
	handlers.totalsUpdated = function (data) {
		if (parseFloat(data.bills) > 0) {
			$('#buy').removeAttr('disabled').prop('disabled', false);
			$('#canceler').attr('disabled', 'disabled').prop('disabled', true);
			disableCanceler();
		}
		
		if (bills !== data.bills) {
			updateTotal(data);
		}
	};
	
	var GENERIC_ERROR_TEXT = '<h1>Hardware Error:</h1>Please contact the operator.';
	
	var statusInfo = {
		JAMMED: {
			goodWhen: false,
			recoverable: true,
			text: '<h1>Acceptor Jammed:</h1>If you can not clear the acceptor, please contact the operator.'
		},
		FULL: {
			goodWhen: false,
			recoverable: false,
			text: GENERIC_ERROR_TEXT
		},
		FAILURE: {
			goodWhen: false,
			recoverable: false,
			text: GENERIC_ERROR_TEXT
		},
		CHEATED: {
			goodWhen: false,
			recoverable: true,
			text: GENERIC_ERROR_TEXT
		},
		CASSETTE_PRESENT: {
			goodWhen: true,
			recoverable: false,
			text: GENERIC_ERROR_TEXT
		},
		INVALID_COMMAND: {
			goodWhen: false,
			recoverable: true,
			text: GENERIC_ERROR_TEXT
		},
	};
	
	var canRecover = true;
	
	var statusErrors = $('<div></div>');
	
	function recover() {
		statusErrors.detach().empty();
		$('#errorPage')
			.addClass('hidden')
			.removeClass('notnet');
	}
	
	function displayError(messages) {
		statusErrors.empty();
		$.each(messages, function (msg) {
			statusErrors.append('<div>' + msg + '</div>');
		});
		$('#errorPage')
			.removeClass('hidden')
			.addClass('notnet')
			.prepend(statusErrors);
	}
	
	handlers.stateChanged = function (data) {
		var recoverAfter = false;
		var messages = {};
		var notifyError = false;
		$.each(statusInfo, function (k, badStatus) {
			var eventValue = data.states[k];
			if (eventValue === undefined) {
				return;
			}
			if (eventValue === badStatus.goodWhen && canRecover) {
				recoverAfter = true;
			} else {
				if (!badStatus.recoverable) {
					canRecover = false;
				}
				messages[badStatus.text] = true;
				notifyError = true;
			}
		});
		if (recoverAfter) {
			recover();
		}
		if (notifyError) {
			displayError(messages);
		}
	};
	
	Comet.open('/billscan-balance/' + ticketId, function (data) {
		if (handlers[data.event]) {
			handlers[data.event](data);
		} else {
			console.log(data);
		}
	});
	
	function purchase(e) {
		$('#buy').off('click', purchase);
		Loading.text(_('Processing'));
		Loading.show();
		$.getJSON('/finalize/' + ticketId)
			.done(function (response) {
				var extra = '';
				if (response.proceed) {
					window.location.replace('/receipt/' + ticketId);
					return;
				}
				if (response.redirect) {
					window.location.replace('/start');
					return;
				}
				if (response.error) {
					if (response.insufficient) {
						extra = '?insufficient=1';
					}
					window.location.replace('/error/' + ticketId + extra);
					return;
				}
				$('#buy').on('click', purchase);
				Loading.hide();
			});
		e.preventDefault();
	}
	$('#buy').on('click', purchase);
});
