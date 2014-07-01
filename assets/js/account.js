Comet.open('/price', function (price) {
	$('.fiat-amount').text(CurrencyData.symbol + String(price));
});

$(function () {
	var gt = new Gettext({domain: 'secondary'});
	function _(msgid) { return gt.gettext(msgid); }
	MBP.hideUrlBarOnLoad();
	
	(function init() {
		var video = $('#video')[0],
			canvas = $('#qr-canvas')[0],
			height = 360,
			width = 480,
			suspended = false,
			streaming = false;
		
		function confirmAddr(addr) {
			new Messi(
				'<pre><strong class="address">' + addr + '</strong></pre> ' + _('Is this your address?'),
				{
					title: _('Address Detected:'),
					modal: true,
					center: true,
					closeButton: false,
					buttons: [
						{
							id: 0,
							label: _('Yes'),
							val: 'Y',
							class: 'btn-success'
						},
						{
							id: 1,
							label: _('No'),
							val: 'N',
							class: 'btn-danger'
						}
					],
					callback: function (val) {
						if (val === 'Y') {
							window.location.replace('/purchase/' + addr);
						} else {
							suspended = false;
						}
					}
				}
			);
		}
		
		//this does not validate the address
		function getBitcoinAddress(url) {
			//remove scheme and double slashes
			url = url.replace(/bitcoin:(\/\/)?/, '');
			//remove query
			return url.split('?')[0];
		}
		
		function decodeResult(data) {
			if (data === 'error decoding QR Code') {
				return;
			}
			var l = data.length;
			var addr = '';
			if (/bitcoin:/.test(data) || (l >= 27 && l <= 34)) {
				suspended = true;
				addr = getBitcoinAddress(data);
				$.getJSON('/validate/' + addr)
					.done(function (result) {
						if (result.valid) {
							confirmAddr(addr);
						}
					})
					.always(function () {
						suspend = false;
					});
			}
		}
		
		qrcode.callback = decodeResult;
		
		function getMedia(constraints, success, error) {
			(
				navigator.getUserMedia ||
				navigator.webkitGetUserMedia ||
				navigator.mozGetUserMedia ||
				navigator.msGetUserMedia
			).call(navigator, constraints, success, error);
		}
		
		getMedia(
			{video:true, audio:false},
			function success(stream) {
				if (navigator.mozGetUserMedia) {
					video.mozSrcObject = stream;
				} else {
					video.src = (window.URL || window.webkitURL).createObjectURL(stream);
				}
				video.play();
			},
			function error(err) {
			}
		);
		
		$(video).on('loadeddata', function () {
			if (!streaming) {
				streaming = true;
			}
		});
		
		$(video).hide();
		$(video).on('timeupdate', function () {
			if (video.videoWidth || video.videoHeight) {
				width = video.videoWidth * 0.75;
				height = video.videoHeight * 0.75;
				$(canvas)
					.attr('width', width)
					.attr('height', height);
				$(video).off('timeupdate');
			}
		});
		
		var ctx = canvas.getContext('2d');
		
		function capture() {
			try {
				ctx.drawImage(video, 0, 0, width, height);
				var data = canvas.toDataURL('image/png');
				qrcode.decode(data);
			} catch (e) {
				if (e.name == "NS_ERROR_NOT_AVAILABLE") {
					//setTimeout(capture, 1);
				} else {
					throw e;
				}
			}
		}
		
		var requestAnimationFrame = (function rafMemo() {
			var raf = /*window.requestAnimationFrame ||
				window.mozRequestAnimationFrame ||
				window.webkitRequestAnimationFrame ||*/
				function raf(cb) {
					window.setTimeout(cb, 500);
				};
			return raf;
		}());
		
		(function anim() {
			if (!suspended && Network.isConnected()) {
				capture();
			}
			requestAnimationFrame(anim);
		}());
	}());
});
