// Depends on jquery
var Comet = (function () {
	var self = {},
		iframes = {},
		connections = {};
	
	window.addEventListener("message", function (e) {
		var url = e.data.url;
		
		if (connections[url]) {
			connections[url](e.data.data);
		}
	}, false);
	
	function open(url, handler) {
		if (connections[url]) {
			throw "Connection to \"" + url + "\"already opened.";
		}
		$(function () {
			var iframe = $('<iframe></iframe>')
				.attr('src', url)
				.hide()
				.appendTo(document.body);
			iframes[url] = iframe;
		});
		connections[url] = handler;
	}
	
	self.open = open;
	
	function close(url) {
          if (iframes[url]) {
		iframes[url].remove();
		delete iframes[url];
		delete connections[url];
          }
	}
	
	self.close = close;
	
	function closeAll() {
		$.each(connections, function (k) {
			close(k);
		});
	}
	
	self.closeAll = closeAll;
	
	function setHandler(url, handler) {
		connections[url] = handler;
	}
	
	self.setHandler = setHandler;
	
	return self;
}());

