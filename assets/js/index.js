
var SkyhookUtils = (function(){
  return {
    disableBillAcceptor : function() {
      $.get('/bill-acceptor/disable');
    }
  }
})();


function timeoutAction() {
  console.log("Timeout reached...");
  PageManager.viewPage(PageIds.START);
}

// Handles inactivity timeouts and triggers a callback
var IdleTimeout = (function(func){
  var timeoutId = 0;
  var interval = 0;
  var action = func;

  return {
    start : function(seconds) {
      IdleTimeout.stop();
      timeoutId = window.setTimeout(action, seconds * 1000); 
      interval = seconds;
    },
    restart : function() { IdleTimeout.start(interval); },
    stop : function() { window.clearTimeout(timeoutId) }
  };
})(timeoutAction);


var NetworkMonitor = (function() {
  var INTERVAL = 60;
  var timeoutId = 0;

  function testNetwork() {
    $.ajax('/nettest', { dataType: 'json', cache: false, timeout: 5*1000 })
      .fail(function() {
        if (!PageManager.isCurrentPage(PageIds.NETWORKERROR)) {
          PageManager.viewPage(PageIds.NETWORKERROR);
        }
      })
      .done(function(data) {
        if (data.error) {
          if (!PageManager.isCurrentPage(PageIds.NETWORKERROR)) {
            PageManager.viewPage(PageIds.NETWORKERROR);
          }
        } else {
          if (PageManager.isCurrentPage(PageIds.NETWORKERROR)) {
            PageManager.viewPage(PageIds.START);
          }
        }
      })
      .always(function() {
        NetworkMonitor.start();
      });
  }

  return {
    start : function() {
      NetworkMonitor.stop();
      timeoutId = window.setTimeout(testNetwork, INTERVAL * 1000);
    },
    stop : function() { window.clearTimeout(timeoutId) }
  };
})();


var PageIds = {
  "START" : "START-PAGE",
  "QRSCAN" : "QRSCAN-PAGE",
  "DEPOSIT" : "DEPOSIT-PAGE",
  "RECEIPT" : "RECEIPT-PAGE",
  "ERROR" : "ERROR-PAGE",
  "NETWORKERROR" : "NETWORK-ERROR-PAGE"
};

// Simple clientside page manager. 
// Shows/hides divs as needed with appropriate init/enter/exit handlers.
// Each page receives a separate context object that will not be reset until the
// browser page is reloaded.
var PageManager = (function() {
  var pageMap = {};
  var currentPage = "";

  function getLoggedHandler(logText, func) {
    return function(context) {
      console.log(logText);
      func(context);
    }
  }

  return {
    // addPage() - Add a page to be managed by the PageManager.
    //   id - ID if the container DIV for the page
    //   onInit - func called only once when this page is first entered
    //   onEnter - func called each time upon entering the page
    //   onExit - func called each time before leaving the page
    addPage : function(id, onInit, onEnter, onExit) {
      pageMap[id] = {
        "context" : { "id" : id },
        "id" : id,
        "inited" : false,
        "onInit"  : getLoggedHandler("PageManager:onInit:"+id, onInit),
        "onEnter" : getLoggedHandler("PageManager:onEnter:"+id, onEnter),
        "onExit"  : getLoggedHandler("PageManager:onExit:"+id, onExit)
      }
    },
    
    // viewPage() - View a page managed by the PageManager.
    //   id - ID of the page to transition to
    //   extra - object of extra data to pass to the page about to be viewed
    viewPage : function(id, extra) {
      if (!id || !pageMap[id]) return;
      if (currentPage) {
        pageMap[currentPage].onExit(pageMap[currentPage].context, currentPage);
        $("#" + currentPage).hide();
      }
      currentPage = id;
      if (!pageMap[currentPage].inited) {
        pageMap[currentPage].onInit(pageMap[currentPage].context, id);
        pageMap[currentPage].inited = true;
      }
      pageMap[currentPage].context.extra = extra;
      pageMap[currentPage].onEnter(pageMap[currentPage].context, id);
      $("#" + currentPage).show();
    },
 
    // isCurrentPage() - Check if a specific page is the currently viewed page
    isCurrentPage : function(id) {
      return (id == currentPage);
    }
    
  };
})();

/* Start Page */
PageManager.addPage( PageIds.START,

  function INIT(context) {
    $("#" + PageIds.START).on("click", function() {
      PageManager.viewPage(PageIds.QRSCAN);
    });
  },

  function ENTER(context) {
    SkyhookUtils.disableBillAcceptor();
    Comet.open('/price', function (price) {
      $('#bitcoin-price').text("$" + price);
    });
    $("#" + PageIds.START)[0].appendChild($("#bitcoin-price-box")[0]);
    NetworkMonitor.start();

    // Force network connections to re-establish every 5 minutes
    // Sometimes the Comet price times out.. this takes care of that.
    IdleTimeout.start(300);
  },

  function EXIT(context) {
    Comet.closeAll();
    IdleTimeout.stop();
    NetworkMonitor.stop();
  }
);
 
 
/* Bitcoin Address QR Scan Page */
PageManager.addPage( PageIds.QRSCAN,

  function INIT(context) {
    $("#btn-scan-cancel").on("click", function() {
      PageManager.viewPage(PageIds.START);
    });

    context.canvas = $("#qr-canvas")[0];
    qrcode.callback = function(address) {
      console.log("address: " + address);
      context.stopScanning();
      IdleTimeout.restart();

      // Addresses are sometimes encoded with protocols and arguments. Need to strip them.
      // [bitcoin:[//]]<ADDRESS>[?param=value1][&param=value2]
      var matches = address.match(/(?:^bitcoin:\/*)?([^\?]*)\??/);
      if (matches.length != 2) {
        context.startScanning();
        return;
      }
      address = matches[1];

      if (!Bitcoin.Address.validate(address)) {
        context.startScanning();
        return;
      }
 
      // Querying backend for TicketID takes a bit. Show loading overlay. 
      Loading.text("Please wait...");
      Loading.show();

      // Need to close the price update here. Otherwise the scanner turns off
      // if we do it after requesting /purchase. Strange.
      Comet.close("/price");

      $.getJSON("/start-purchase/" + address, function(data) {
        if (data.error) {
          // TODO: Show an error msg here
          console.log("Error starting purchase:", data.error);
          return PageManager.viewPage(PageIds.START);
        }

        Loading.hide();

        var extra = {
          "ticketId" : data.ticketId,
          "address" : data.address,
          "price" : data.price
        };
        PageManager.viewPage(PageIds.DEPOSIT, extra);
      });
    }

    function capture() {
      if (!isScanning) return; 
      context.stopScanning();
      
      var video = $('#video')[0];
      var ctx = context.canvas.getContext('2d');
      ctx.drawImage(video, 0, 0, video.videoWidth, video.videoHeight, 
                    0, 0, context.canvasWidth, context.canvasHeight);
      try {
        qrcode.decode();
      } catch(e) {
        context.startScanning();
      }
    }

    var isScanning = false;
    context.startScanning = function() {
      if (isScanning) return;
      isScanning = true;
      setTimeout(capture, 0);
    }

    context.stopScanning = function() {
      isScanning = false;
    }
  },

  function ENTER(context) {
    Comet.open('/price', function (price) {
      $('#bitcoin-price').text("$" + price);
    });
    $("#" + PageIds.QRSCAN)[0].appendChild($("#bitcoin-price-box")[0]);

    IdleTimeout.start(60);
    NetworkMonitor.start();

    function getMedia(constraints, success, error) {
      (navigator.getUserMedia || 
       navigator.webkitGetUserMedia || 
       navigator.mozGetUserMedia || 
       navigator.msGetUserMedia).call(navigator, constraints, success, error);
    }

    var video = $("#video")[0];
    getMedia({
        video: true,
        audio: false
      },
      function success(stream) {
        if (!PageManager.isCurrentPage(context.id)) {
          console.log("Media Callback after page change.");
          stream.stop();
          return;
        }
        context.stream = stream;
        video.src = (window.URL || window.webkitURL).createObjectURL(stream);
        video.play();
      },
      function error(e) {
        console.log("Problem getting media stream: " + e);
      }
    );

    $(video).on("timeupdate", function () {
      if (video.videoWidth && video.videoHeight) {
        // qrdecode from a scaled canvas. only 1/4 of the pixels. Faster.
        $(context.canvas).attr("width", video.videoWidth * 0.5)
        $(context.canvas).attr("height", video.videoHeight * 0.5)
        context.canvasWidth = video.videoWidth * 0.5;
        context.canvasHeight = video.videoHeight * 0.5;
        $(video).off("timeupdate");
        console.log("Video dimension: " + video.videoWidth + "x" + video.videoHeight);
        context.startScanning();
      }
    });
  }, 
  
  function EXIT(context) {
    Comet.closeAll();
    IdleTimeout.stop();
    NetworkMonitor.stop();
    context.stopScanning();

    var video = $("#video")[0];
    if (video && video.pause) {
      console.log("Pausing video");
      video.pause();
      if (context.stream && context.stream.stop) {
        console.log("Stopping video");
        context.stream.stop();
        context.stream = null;
      }
      video.src = "";
    }
  }
);

/* Deposit Money Page */
PageManager.addPage( PageIds.DEPOSIT,

  function INIT(context) {
    function finalizePurchase() {
      Loading.text("Sending Bitcoin...");
      Loading.show();
      $.getJSON('/finalize/' + context.ticketId)
        .done(function (data) {
          if (data.proceed ) {
            context.extra["btc"] = context.btc;
            return PageManager.viewPage(PageIds.RECEIPT, context.extra); 
          } else if (data.redirect) {
            return PageManager.viewPage(PageIds.START); 
          } else if (data.error) {
            var extra = { ticketId: context.ticketId };
            if (data.insufficient) {
              extra["insufficient"] = true;
            }
            return PageManager.viewPage(PageIds.ERROR, extra);
          } else {
            // TODO ? Just re-enable the button and ignore? When does this occur?
          }
        });
    }

    $("#btn-buy-cancel").on("click", function() {
      PageManager.viewPage(PageIds.START);
    }); 
    $("#btn-send-bitcoin").on("click", finalizePurchase);
  },

  function ENTER(context) {
    context.bills = 0;
    context.bitcoin = 0;
    context.diff = undefined;

    function billscanListener(data) {
      console.log(data.bills + ":" + data.btc + ":" + data.diff);
      if (parseFloat(data.bills) > 0 && context.bills == 0) {
        // Money inserted. Remove CANCEL and clear the timeout.
        $("#btn-buy-cancel").hide();
        $("#btn-send-bitcoin").show();

        // Disable the idle timeout once bills are inserted
        IdleTimeout.stop();
      }
      
      if (context.bills != data.bills) {
        context.bills = data.bills;
        context.btc = data.btc;

        // TODO: Format the two amounts before displaying?
        $('#cash-deposited-amount').text("$" + parseInt(context.bills));
        $('#bitcoin-purchased-amount').text(context.btc);
      }

      if (context.diff != data.diff) {
        context.diff = data.diff;
 
        if (typeof context.diff != "undefined") {
          // TODO: properly localize for other currencies
          if (context.diff >= 100) {
            // No need to warn about inserting > $100. No bills > $100.
            $('.low-funds-warning').hide();
            $('.no-funds-error').hide();
          } else if (context.diff >= 5) {
            // Smallest CAD bill is $5. Treat smaller diffs as no-more-funds.
            $('.no-funds-error').hide();
            $('.low-funds-warning').show();
            $('#low-funds-amount').text("$" + context.diff);
          } else {
            // Refuse any more bills.
            SkyhookUtils.disableBillAcceptor();
            $('.low-funds-warning').hide();
            $('.no-funds-error').show();
          }
        } else {
          $('.low-funds-warning').hide();
          $('.no-funds-error').hide();
        }
      }
    }

    if (!context.extra) {
      console.log("No extra data found...");
      return PageManager.viewPage(PageIds.START);
    }   
    context.address = context.extra["address"];
    context.ticketId = context.extra["ticketId"];
    context.price = context.extra["price"];
   
    IdleTimeout.start(60);

    $("#" + PageIds.DEPOSIT)[0].appendChild($("#bitcoin-price-box")[0]);
    $('#bitcoin-price').text(context.price);
    $('#bitcoin-purchased-amount').text("0.00000000");
    $('#cash-deposited-amount').text("$0");
    $('.bitcoin-sent-to').text(context.address);
    $('.low-funds-warning').hide();
    $('.no-funds-error').hide();
    $('#btn-buy-cancel').show();
    $('#btn-send-bitcoin').hide();

    Comet.open('/billscan-balance/' + context.ticketId, billscanListener);
  },

  function EXIT(context) {
    Comet.closeAll();
    IdleTimeout.stop();
    Loading.hide();
    SkyhookUtils.disableBillAcceptor();
  }
);

/* Receipt Page */
PageManager.addPage( PageIds.RECEIPT,

  function INIT(context) {
    $("#" + PageIds.RECEIPT).on("click", function() {
      PageManager.viewPage(PageIds.START);
    }); 
  },

  function ENTER(context) { 
    if (!context.extra) {
      console.log("No extra data found...");
      return PageManager.viewPage(PageIds.START);
    }   
    context.address = context.extra["address"];
    context.ticketId = context.extra["ticketId"];
    context.price = context.extra["price"];
    context.btc = context.extra["btc"];

    $("#" + PageIds.RECEIPT)[0].appendChild($("#bitcoin-price-box")[0]);
    $('#bitcoin-price').text(context.price);
 
    $("#bitcoin-sent-amount").text(context.btc);
    $(".bitcoin-sent-to").text(context.address);

    IdleTimeout.start(10);
  },

  function EXIT(context) {
    IdleTimeout.stop();
  }
);

/* Error Page */
PageManager.addPage( PageIds.ERROR,

  function INIT(context) {
    $("#email-address").Watermark("Email Address (optional)");

    $("#btn-error-done").on("click", function() {
      $.Watermark.HideAll();
      IdleTimeout.restart();
      var emailField = $("#email-address");

      if (emailField.val() != "") {
        Loading.text("Please wait...");
        Loading.show();
        $.getJSON('/add-email-to-ticket/' + context.extra["ticketId"], { email: emailField.val() })
          .done(function (data) {
            if (data.invalid) {
              Loading.hide();
              var tid = window.setInterval(function(){emailField.toggleClass("invalid")},200);
              window.setTimeout(function(){window.clearInterval(tid)},1300);
              emailField.select();
            } else {
              if (context.extra["insufficient"]) {
                // TODO: Make the error page configurable based on context.extra
                // Better to send to a dead-end version of this error page rather than /admin...
                window.location.replace("/admin/minimum-balance");
              } else {
                PageManager.viewPage(PageIds.START); 
              }
            }
          });
      } else {
        PageManager.viewPage(PageIds.START); 
      }

      $.Watermark.ShowAll();
    });
  },

  function ENTER(context) {
    if (!context.extra) {
      console.log("No extra data found...");
      return PageManager.viewPage(PageIds.START);
    }   

    IdleTimeout.start(60);

    $.Watermark.HideAll();
    $("#email-address").val("");  // Clear existing info
    $.Watermark.ShowAll();
  },

  function EXIT(context) {
    Loading.hide();
    IdleTimeout.stop();
  }
);

/* Network Error Page */
PageManager.addPage( PageIds.NETWORKERROR,
  function INIT(context) { },
  function ENTER(context) { NetworkMonitor.start() },
  function EXIT(context) { NetworkMonitor.stop() }
);

$(function() {
    FastClick.attach(document.body);
});
PageManager.viewPage(PageIds.START);
