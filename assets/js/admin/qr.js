var QRScanner = {};

(function (qr) {
	var videoTimout;
	function startCapture(video, canvas, callback) {
		var height = 360,
			width = 480,
			streaming = false;
		
		function decodeResult(data) {
			if (data === 'error decoding QR Code') {
				return;
			}
			callback(data);
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
		
		function loop() {
			capture();
			videoTimout = window.setTimeout(loop, 500);
		}
		
		loop();
	}
	
	function stopCapture() {
		window.clearTimeout(videoTimout);
	}
	
	function getData(title, callback) {
		var html = $('<div id="qr-scanner" data-role="page"><div data-role="header"><h1>' + title + '</h1></div><div data-role="content"><p>Scan the relevant QR code:</p><video id="video" width="480" height="360"></video><canvas id="qr-canvas"></canvas></div></div>');
		html.appendTo(document.body);
		var lvideo = $('#video');
		var lcanvas = $('#qr-canvas');
		lcanvas.css({
			'margin': '0 auto',
			'transform': 'scaleX(-1)',
			'z-index': 999
		});
		html.one('pagehide', function () {
			stopCapture();
			html.remove();
		});
		function start() {
			startCapture(lvideo.get(0), lcanvas.get(0), function (data) {
				html.dialog('close');
				callback(data);
			});
		}
		html.one('pageshow', start);
		$.mobile.changePage('#qr-scanner',
			{transition: 'pop', role: 'dialog'}
		);
	}
	
	qr.getFromCamera = getData;
	
}(QRScanner));
