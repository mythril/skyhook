var Loading = {};
(function (l) {
	var wrapper = $('<div id="loading-widget-outer"></div>')
		.hide()
		.appendTo(document.body);
	
	var widget = $('<div id="loading-widget"></div>')
		.appendTo(wrapper);
	
	var text = $('<div class="loading-text"></div>')
		.appendTo(widget);
	
	wrapper.on(
		'submit click mousedown mouseup touchstart touchend',
		function (e) {
			e.stopPropagation();
		}
	);
	
	l.text = function (t) {text.text(t);};
	l.show = function () {wrapper.show();};
	l.hide = function () {wrapper.hide();};
}(Loading));
