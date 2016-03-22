var jsonpCallbacks = {cntr: 0};

function doJSONP(from, to, callbackFuncName) {
	var url = "//query.yahooapis.com/v1/public/yql?q=select%20rate%2Cname%20from%20csv%20where%20url%3D'http%3A%2F%2Fdownload.finance.yahoo.com%2Fd%2Fquotes%3Fs%3D"+from+to+"%253DX%26f%3Dl1n'%20and%20columns%3D'rate%2Cname'&format=json";
    var fullURL = url + "&callback=" + callbackFuncName;
    // generate the script tag here
	var script = document.createElement('script');
	script.setAttribute('src', fullURL);
	document.body.appendChild(script);
}

function getRate(from, to, id, fn) {
    // create a globally unique function name
    var name = "fn" + jsonpCallbacks.cntr++;

    // put that function in a globally accessible place for JSONP to call
    jsonpCallbacks[name] = function() {
        // upon success, remove the name
        delete jsonpCallbacks[name];
        // now call the desired callback internally and pass it the id
        var args = Array.prototype.slice.call(arguments);
        args.unshift(id);
        fn.apply(this, args);
    }

    doJSONP(from, to, "jsonpCallbacks." + name);
}

function parseExchangeRate(id, data) {
	var name = data.query.results.row.name;
	var rate = parseFloat(data.query.results.row.rate, 10);
	jQuery("#"+id).val(rate);
}

jQuery(document).ready(function() {
	jQuery(".exchage_rate_button").click(function(){
		getRate(this.getAttribute('currency_from'), this.getAttribute('currency_to'), this.getAttribute('multiply_by_field_id'), parseExchangeRate);
		return false;
	});
});