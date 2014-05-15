$(function () {
	Comet.open('/check-balance', function (walletIsFunded) {
		if (walletIsFunded) {
			window.location.replace('/start');
		}
	});
});
