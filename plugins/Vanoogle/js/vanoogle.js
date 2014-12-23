$(document).ready(function () {
	$("form").each(function(){
		 if (this.getAttribute('action') == '/search') {
			 var parent = (this.parentElement || this.parentNode);
			 parent.insertBefore($('#VanoogleSearch')[0], this);
			 parent.removeChild(this);
			 return false;  // do not continue
		 }
	});
});

//Load the Search API
google.load('search', '1');

//Set a callback to load the Custom Search Element when you page loads
google.setOnLoadCallback(function(){
	$("script").each(function(){
    	if (/^https:\/\/www\.google\.com\/jsapi/.test(this.getAttribute('src'))) {
    		if (this.id) {
    			var csc = new google.search.CustomSearchControl(this.id);
    			csc.setResultSetSize(8);  // TODO: make this a Vanoogle preference.

    		    var results = document.createElement('ul');
    		    results.id = 'VanoogleResults';
    		    results.className = 'DataList MessageList';
    		    $('#Body').append(results);
    		    
    		    var options = new google.search.DrawOptions();
    		    options.setSearchFormRoot('VanoogleSearch');
    		    csc.draw(results, options);
    		    
    		    // Use "vanoogle_" as a unique ID to override the default rendering.
    		    google.search.Csedr.addOverride("vanoogle_");
    		}
    		
    		return false;  // do not continue
    	}
    });
}, true);