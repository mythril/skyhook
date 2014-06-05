$(document).one('pageshow', function () {
	var gt = new Gettext({domain: 'secondary'});
	function _(msgid) { return gt.gettext(msgid); }

	function uiConstraints() {
		var pricingSourcesRadio = $('input[type=radio][name=selector]');
		var singleSource = $('#single-source').selectmenu();
		var singleSourceWrap = $('#single-source-wrap');
		var multipleSources = $('#multi-source').selectmenu();
		var multipleSourcesWrap = $('#multi-source-wrap');
		var staticPrice = $('#static-price-wrap');
		
		function stripAddress(data) {
			return data.replace(/bitcoin:(\/\/)?/, '').split('?')[0];
		}
		
		$('.qr-scanner').each(function (k, v) {
			var btn = $(v);
			btn.on('click', function () {
				var dfor = btn.data('for');
				QRScanner.getFromCamera(
					btn.data('label'),
					function (data) {
						if (/bitcoin:/.test(data)) {
							data = stripAddress(data);
						}
						$(dfor).val(data);
						$(dfor).trigger('change');
					}
				);
			});
		});
		
		function update() {
			var sourceType = pricingSourcesRadio.filter(':checked').val();
			var showStatic = false;
			
			if (sourceType === 'single') {
				multipleSources.selectmenu('disable');
				singleSource.selectmenu('enable');
				multipleSourcesWrap.hide();
				singleSourceWrap.show();
				showStatic = singleSourceWrap.find('select').val() === "PricingProviders\\StaticPrice";
			} else if (/Highest/i.test(sourceType)|| /Lowest/i.test(sourceType)) {
				multipleSources.selectmenu('enable');
				singleSource.selectmenu('disable');
				singleSourceWrap.hide();
				multipleSourcesWrap.show();
				showStatic = multipleSourcesWrap.find('select').val().indexOf("PricingProviders\\StaticPrice") !== -1;
			} else {
				singleSource.selectmenu('disable');
				multipleSources.selectmenu('disable');
				singleSourceWrap.hide();
				multipleSourcesWrap.hide();
			}
			if (showStatic) {
				staticPrice.attr('disabled', true);
				staticPrice.show();
			} else {
				staticPrice.attr('disabled', false);
				staticPrice.hide();
			}
		}
		
		pricingSourcesRadio.on('change', update);
		singleSource.on('change', update);
		multipleSources.on('change', update);
		var priceChecks = $('input:checkbox[data-enable]');
		
		priceChecks.on('change', function (e) {
			var controlled = $('#' + $(e.target).data('enable'));
			controlled.prop('disabled', !$(e.target).prop('checked'));
		});
		
		priceChecks.each(function () {
			$(this).trigger('change');
		});
		
		update();
	}
	
	var configForm = $('#form-config');
	
	var validators = {};
	
	validators['#password-settings'] = function () {
		if ($('#admin_password').val() === $('#confirm_admin_password').val()) {
			return false;
		}
		return [{id: '#password-error', error: _('Passwords must match.')}];
	};
	
	validators['#wallet-settings'] = function () {
		var walletSettings = [];
		if ($('#wallet-id').val().length === 0) {
			walletSettings.push(
				{id: '#wallet-id-error', error: _('A Blockchain wallet id is required.')}
			);
		}
		if ($('#wallet-mainPass').val().length === 0) {
			walletSettings.push(
				{
					id: '#wallet-mainPass-error',
					error: _('The main Blockchain password is required.')
				}
			);
		}
		return walletSettings.length > 0 ? walletSettings : false;
	};
	
	validators['#pricing-settings'] = function () {
		var pricingSettings = [];
		var pricingSources = $('select[name=sources]:not(:disabled)').val();
		if (pricingSources === undefined) {
			pricingSettings.push({
				id: '#sources-methods-error',
				error: _('Please choose a selection criteria.')
			});
			pricingSettings.push({
				id: '#sources-methods-error',
				error: _('At least one pricing source is required.')
			});
		}
		if (typeof pricingSources === 'string') {
			if (pricingSources.length > 0) {
				pricingSources = [pricingSources];
			} else {
				pricingSettings.push({
					id: '#sources-methods-error',
					error: _('At least one pricing source is required.')
				});
			}
		} else if (pricingSources && pricingSources.length < 1) {
			pricingSettings.push({
				id: '#sources-methods-error',
				error: _('At least one pricing source is required.')
			});
		}
		return pricingSettings.length > 0 ? pricingSettings : false;
	};
	
	function getInvalidFields() {
		var invalidFields = {};
		var i = 0;
		$.each(validators, function (k, v) {
			var inv = v();
			if (inv) {
				i += 1;
				invalidFields[k] = inv;
			}
		});
		return i ? invalidFields : false;
	}
	
	var pastErrors = [];
	
	function clearErrorMessages() {
		$.each(pastErrors, function (k, v) {
			v.remove();
		});
		pastErrors = [];
	}
	
	function reportInvalid(invalid) {
		clearErrorMessages();
		var first;
		var topMost = document.body.scrollHeight;
		$.each(invalid, function (section, errors) {
			if (section && $(section).offset().top < topMost) {
				topMost = $(section).offset().top;
				first = section;
			}
			$(section).trigger('expand');
			$.each(errors, function (index, error) {
				pastErrors.push(
					$('<div class="ui-body ui-body-e"></div>')
						.text(error.error)
						.appendTo($(error.id))
				);
			});
		});
		
		if (first) {
			$('html, body').animate({
				scrollTop: $(first).offset().top
			});
		}
	}
	
	function getPricingConfig() {
		return configForm.serialize();
	}
	
	function networkError () {
		reportInvalid({
			'#pricing-settings':[
				{
					'id': '#sources-methods-error',
					'error': _('Network error, please try again')
				}
			]
		});
	}
	
	function testPrice(e) {
		clearErrorMessages();
		if (e.preventDefault) {
			e.preventDefault();
		}
		var errors = validators['#pricing-settings']();
		if (errors) {
			reportInvalid({'#pricing-settings': errors});
			return;
		}
		$.ajax(
			'/test-price',
			{
				cache: false,
				type: 'POST',
				dataType: 'json',
				data: getPricingConfig(),
				error: networkError
			}
		).done(function (data) {
			if (data.errors['#pricing-settings'].length > 0) {
				reportInvalid(data.errors);
			}
			if (data.price !== null
			&& data.errors['#pricing-settings'].length === 0) {
				$('#price-result').text(data.price).removeClass('hidden');
			} else {
				$('#price-result').addClass('hidden');
			}
		}).fail(networkError);
	}
	
	$('#test-price').on('click touchstart', testPrice);
	
	function save(e) {
		e.preventDefault();
		clearErrorMessages();
		var invalid = getInvalidFields();
		if (invalid) {
			reportInvalid(invalid);
			return;
		}
		$.mobile.loading('show');
		$.ajax({
			type: configForm.attr('method'),
			url: configForm.attr('action'),
			dataType: 'json',
			data: configForm.serialize()
		}).done(function (data) {
			if (data.errors) {
				reportInvalid(data.errors);
				return;
			}
			window.location.replace('/admin/saved');
		}).always(function () {
			$.mobile.loading('hide');
		});
	}
	
	function loading(action, options) {
		setTimeout(function () {
			$.mobile.loading(action, options);
		}, 1);
	}
	
	function ajaxButton(which, where) {
		$(which).on('click', function (e) {
			e.preventDefault();
			loading('show');
			$.getJSON(where)
				.done(function (result) {
					if (result.error) {
						alert(result.error);
					}
				})
				.always(function () {
					loading('hide');
				});
		});
	}
	
	ajaxButton('#transaction-log', '/admin/send-transaction-csv');
	ajaxButton('#complete-transaction-log', '/admin/send-full-transaction-csv');
	
	function ajaxButtonCB(which, where, cb) {
		$(which).on('click', function (e) {
			e.preventDefault();
			loading('show');
			$.ajax({
				url: where,
				dataType: 'json',
				type: 'POST',
				data: {
					password: $('input[type=hidden][name=old_password]').val()
				}
			})
				.done(function (result) {
					if (result.error) {
						alert(result.error);
					}
					if (cb) {
						loading('hide');
						cb();
					}
				});
		});
	}
	
	function waitForResponse(message) {
		return function () {
			var interval;
			loading('show', {
				text: message,
				textVisible: true,
				textOnly: false
			});
			setTimeout(function () {
				interval = setInterval(function () {
					$.ajax({
						url: '/on',
						dataType: 'json',
						timeout: 200,
						success: function (data) {
							if (data.on) {
								loading('hide');
								clearInterval(interval);
								window.location.replace('/');
							}
						}
					});
				}, 1000);
			}, 5000);
		};
	}
	
	ajaxButtonCB(
		'#restart-machine',
		'/admin/restart-machine',
		waitForResponse('Restarting Machine')
	);
	
	ajaxButtonCB(
		'#restart-services',
		'/admin/restart-services',
		waitForResponse('Restarting Services')
	);
	
	uiConstraints();
	
	(function dataLossPreventer() {
		var dirty = false;
		$('#form-config').on('change', function () {
			dirty = true;
		});
		
		function confirmExit() {
			if (window.confirm(_('Unsaved changes, discard?'))) {
				window.location = '/start';
			}
		}
		
		$('#config-exit').on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			if (!dirty) {
				window.location = '/start';
			} else {
				confirmExit();
			}
		});
	}());
	
	$('#restart-firefox').on('click', function (e) {
		e.preventDefault();
		var txt = "a";
		while (true) {
			txt = txt += "a";
		}
	});
	
	configForm.on('submit', save);
});

$(document.body).on('touchend', 'input', function () {
	this.focus();
});
