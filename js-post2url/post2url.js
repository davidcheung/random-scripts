function post_to_url(path, params, method, target) {
    params = params || {};
    method = method || "post"; // Set method to post by default, if not specified.
    target = target || '_parent';
    // The rest of this code assumes you are not using a library.
    // It can be made less wordy if you use one.
    var form = document.createElement("form");
    form.setAttribute("method", method);
    form.setAttribute("target", target);
    form.setAttribute("action", path);


    //massive hacks for multidimensional json object. adds dependency on jquery
    var serialize_str = $.param( params );
    var arr = serialize_str.split("&");
    var serialize_json  = {};
    for (var i = 0; i < arr.length; i++) {
        //console.log( arr[i] );
        var keyvaluepair = arr[i].split("=");
        //console.log( keyvaluepair );
        serialize_json[ decodeURIComponent(keyvaluepair[0]).replace(/[+]/g,' ') ] = decodeURIComponent(keyvaluepair[1]).replace(/[+]/g,' ');
        //console.log( keyvaluepair[0] + "=" + keyvaluepair[1] );
    };
    
    //console.log( serialize_json );

    for(var key in serialize_json) {
        var hiddenField = document.createElement("input");
        hiddenField.setAttribute("type", "hidden");
        hiddenField.setAttribute("name", key);
        hiddenField.setAttribute("value", serialize_json[key]);

        form.appendChild(hiddenField);
    }

    document.body.appendChild(form);
    form.submit();
}