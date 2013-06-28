var wbtAPI = window.wbtAPI || (function (document, $) {

    var _serviceURL = 'http://' + window.location.host + '/api/wbt/',
        that = {};

    that.setServiceURL = function (url) { _serviceURL = url; };

    that.call = function (service, resourceID, params, success) {

        if (arguments.length == 2) {
            var type = typeof resourceID;
            if (type == 'function') {
                success = resourceID;
                resourceID = null;
            }
            else if (type == 'object') {
                params = resourceID;
                resourceID = null;
            }
        }
        else if (arguments.length == 3) {
            if (typeof params == 'function') {
                success = params;
                params = null;
            }
            if (typeof resourceID == 'object') {
                params = resourceID;
                resourceID = null;
            }
        }

        $.ajax({
            type:'POST',
            url:_serviceURL + (resourceID ? (service + '/' + resourceID) : service),
            data:params,
            datatype:'JSON',
            async:false,
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                bluefinBH.showError(errorThrown);
            },
            success: function (data) {
                if (data.error) {
                    if (data.errorno == 0) {
                        bluefinBH.showInfo(data.error, function() { location.reload(); });
                    } else {
                        bluefinBH.showError(data.error);
                    }
                }
                else if (success)
                {
                    success(data);
                }
            }
        });
    };

    return that;

}(document, window.jQuery));

window.wbtAPI = wbtAPI;
