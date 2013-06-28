var bluefinBH = window.bluefinBH || (function (document, $) {

    var _locale        = 'zh_CN',
        _defaultLocale = 'zh_CN',
        _scriptsLoaded = [],
        _pendingScripts = 0,
        _registry = {},
        that = {};

    that.setLocale = function(locale) {
        for (var i in _locales) {
            if (i == locale) {
                _locale = locale;
                return;
            }
        }
        throw new Error('Invalid locale: '+locale);
    };

    that.addLocale = function(locale, translations) {
        if (typeof _locales[locale] === 'undefined') {
            _locales[locale] = {};
        }
        for (var str in translations) {
            _locales[locale][str] = translations[str];
        }
    };

    that.showError = function (message, handler) {
        return that.dialog(_translate('ERROR'), '<i class="icon-exclamation-sign"></i> ' + message, [{label:_translate('OK'),style:'btn-danger',callback:handler}], {dialogClass: 'alert-block alert-error', footerClass: 'text-align-center', lite: true});
    };

    that.showInfo = function (message, handler) {
        return that.dialog(_translate('INFO'), '<i class="icon-info-sign"></i> ' + message, [{label:_translate('OK'),style:'btn-info',callback:handler}], {dialogClass: 'alert-block alert-success', footerClass: 'text-align-center', backdrop: false, lite: true});
    };

    that.confirm = function (message, handler) {
        return that.dialog(_translate('CONFIRM'), '<i class="icon-question-sign"></i> ' + message, [{label:_translate('CONFIRM'),style:'btn-danger',callback:handler},{label:_translate('CANCEL')}], {dialogClass: 'alert-block alert-info', lite: true, closeButton: true});
    };

    that.register = function (key, value) {
        if (value) {
            _registry[key] = value;
        }
        return _registry[key];
    };

    that.isRegistered = function (key) {
        return key in _registry;
    };

    that.unregister = function (key) {
        delete _registry[key];
    };

    that.addScript = function (url) {
        _scriptsLoaded.push(url);
    };

    that.loadScript = function (urls, loadedHandler) {
        if (!$.isArray(urls)) urls = [urls];

        var len = urls.length,
            pending = [],
            i = 0,
            url;
        for (; i < len; i++)
        {
            url = urls[i];
            if (_scriptsLoaded.indexOf(url) != -1) continue;
            pending.push(url);
        }

        _pendingScripts = pending.length;
        if (_pendingScripts == 0)
        {
            if (loadedHandler) loadedHandler();
            return;
        }

        for (i = 0; i < _pendingScripts; i++)
        {
            url = pending[i];
            _scriptsLoaded.push(url);
            $.getScript(url, function(data, textStatus, jqxhr) {
                _pendingScripts--;
                if (_pendingScripts == 0 && loadedHandler)
                {
                    loadedHandler();
                }
            }).fail(function (jqxhr, settings, exception) { alert(exception); });
        }
    };

    that.buildButtons = function (handlers) {
        var buttons = "",
            callbacks = [],
            i = handlers.length;
        while (i--) {
            var _handler  = handlers[i],
                _label    = null,
                _href     = null,
                _class    = "",
                _icon     = "",
                _callback = null;

            if (typeof _handler['callback'] == 'function') {
                _callback = _handler['callback'];
            }

            if (_handler['style']) {
                _class = _handler['style'];
            } else if (i == 0 && handlers.length <= 2) {
                _class = 'btn-primary';
            }

            if (_handler['label']) {
                _label = _handler['label'];
            }

            if (_handler['icon']) {
                _icon = '<i class="'+_handler['icon']+'"></i> ';
            }

            if (_handler['href']) {
                _href = _handler['href'];
            } else {
                _href = 'javascript:;';
            }

            buttons = '<a data-handler="'+i+'" class="btn '+_class+'" href="' + _href + '">'+_icon+_label+'</a>' + buttons;

            callbacks[i] = _callback;
        }

        return [ buttons, callbacks ]
    };

    that.dialog = function (title, content, footer, options) {
        var callbacks  = [],
            dialogClass = null,
            closeButton = false,
            headerClass = null,
            bodyClass = null,
            footerClass = null,
            lite = false,
            id = null;

        if (typeof options == 'object') {
            if ('dialogClass' in options) {
                dialogClass = options['dialogClass'];
                delete options['dialogClass'];
            }
            if ('closeButton' in options) {
                closeButton = options['closeButton'];
                delete options['closeButton'];
            }
            if ('headerClass' in options) {
                headerClass = options['headerClass'];
                delete options['headerClass'];
            }
            if ('bodyClass' in options) {
                bodyClass = options['bodyClass'];
                delete options['bodyClass'];
            }
            if ('footerClass' in options) {
                footerClass = options['footerClass'];
                delete options['footerClass'];
            }
            if ('lite' in options) {
                lite = options['lite'];
                delete options['lite'];
            }
            if ('id' in options) {
                id = options['id'];
                delete options['id'];
            }
        }
            
        var tmpl = lite ? _liteDialogTmpl : _heavyDialogTmpl;
        var div = $(tmpl).appendTo('body');

        if (title) {
            if (lite) {
                div.find('.dialog-title').append('<h4 class="alert-heading">' + title + '</h4>');
            } else {
                div.find('.dialog-title').append('<h3>' + title + '</h3>');
            }

            if (!closeButton) {
                div.find('.dialog-title button').remove();
            }
        } else {
            div.find('.dialog-title').remove();
        }

        div.find('.dialog-body').html(content);
        if (id) { div.attr('id', id); }

        if (footer) {
            if (typeof footer == 'object') {
                var buttons = that.buildButtons(footer);
                footer = buttons[0];
                callbacks = buttons[1];
            }

            div.find('.dialog-buttons').html(footer);
        } else {
            div.find('.dialog-buttons').remove();
        }

        if (dialogClass) {
            div.addClass(dialogClass);
        }

        if (headerClass) {
            div.find('.dialog-title').addClass(headerClass);
        }

        if (bodyClass) {
            div.find('.dialog-body').addClass(bodyClass);
        }

        if (footerClass) {
            div.find('.dialog-buttons').addClass(footerClass);
        }

        div.on('hidden', function(e) {
            if (div.is(e.target)) {
                div.remove();
            }
        });

        // wire up button handlers
        div.on('click', '.dialog-buttons a', function(e) {
            var handler   = $(this).data("handler"),
                cb        = callbacks[handler],
                hideModal = null;

            if (cb) {
                e.preventDefault();
                hideModal = cb();
            }

            if (hideModal !== false) {
                div.modal("hide");
            }
        });

        var modalOverflow = $(window).height() < div.height();
        div.toggleClass('page-overflow', modalOverflow)
            .toggleClass('modal-overflow', modalOverflow)
            .css('top', '50%')
            .css('margin-top', modalOverflow ? 0 : -div.height()/2);

        if (options) {
            div.modal(options);
        } else {
            div.modal();
        }

        return div;
    };

    that.ajaxDialog = function (url, options) {
        var dlg = that.dialog(null, '<img src="/libs/bluefin/loading.gif">', null, options);
        dlg.find('.dialog-body').load(url, function() {
            var body = $(this),
                box = body.parent(),
                bodyWidth = body.width(),
                contentWidth = body.find('fieldset').width() || body.children().first().width();
            contentWidth += parseInt(body.css('padding-left')) + parseInt(body.css('padding-right'));

            if (contentWidth > bodyWidth) {
                box.css('width', contentWidth + 'px').css('margin-left', (-contentWidth/2) + 'px');
            }

            body.css('max-height', '100%');
            body.on('click', 'button.btn-cancel', function(e) {
                box.modal("hide");
            });

            var maxHeight = $(window).height(),
                modalOverflow = maxHeight < box.height();
            box.css('overflow', 'auto')
                .toggleClass('page-overflow', modalOverflow)
                .toggleClass('modal-overflow', modalOverflow);

            if (modalOverflow) {
                box.css('top', 5).css('margin-top', 0).css('max-height', maxHeight - 10);
            } else {
                box.css('margin-top', -box.height()/2);
            }
        });
    };

    that.showIFrame = function (url, options) {
        var dialog = that.dialog(null, '<img class="show" src="/libs/bluefin/loading.gif"><iframe src="' + url + '" frameborder="0" marginwidth="0" marginheight="0" class="hide"></iframe>', null, options);

        dialog.find('iframe').load(function(){
            dialog.find('img.show').remove();
            $(this).removeClass('hide');
        });
    };

    that.closeDialog = function (dialog) {
        if (!(dialog instanceof jQuery)){
            dialog = $(dialog);
        }
        dialog.parents('.dialog-box').first().modal("hide");
    };

    that.parseUrl = function (str, component) {
      var key = ['source', 'scheme', 'authority', 'userInfo', 'user', 'pass', 'host', 'port',
                'relative', 'path', 'directory', 'file', 'query', 'fragment'],
        ini = {},
        parser = /^(?:([^:\/?#]+):)?(?:\/\/()(?:(?:()(?:([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?))?()(?:(()(?:(?:[^?#\/]*\/)*)()(?:[^?#]*))(?:\?([^#]*))?(?:#(.*))?)/;

      var m = parser.exec(str),
        uri = {},
        i = 14;
      while (i--) {
        if (m[i]) {
          uri[key[i]] = m[i];
        }
      }

      if (component) {
        return uri[component];
      }

      delete uri.source;
      return uri;
    };

    that.parseQueryString = function(str, array) {
      var strArr = String(str).replace(/^&/, '').replace(/&$/, '').split('&'),
        sal = strArr.length,
        i, j, ct, p, lastObj, obj, lastIter, undef, chr, tmp, key, value,
        postLeftBracketPos, keys, keysLen,
        fixStr = function (str) {
          return decodeURIComponent(str.replace(/\+/g, '%20'));
        };

      if (!array) {
        array = this.window;
      }

      for (i = 0; i < sal; i++) {
        tmp = strArr[i].split('=');
        key = fixStr(tmp[0]);
        value = (tmp.length < 2) ? '' : fixStr(tmp[1]);

        while (key.charAt(0) === ' ') {
          key = key.slice(1);
        }
        if (key.indexOf('\x00') > -1) {
          key = key.slice(0, key.indexOf('\x00'));
        }
        if (key && key.charAt(0) !== '[') {
          keys = [];
          postLeftBracketPos = 0;
          for (j = 0; j < key.length; j++) {
            if (key.charAt(j) === '[' && !postLeftBracketPos) {
              postLeftBracketPos = j + 1;
            }
            else if (key.charAt(j) === ']') {
              if (postLeftBracketPos) {
                if (!keys.length) {
                  keys.push(key.slice(0, postLeftBracketPos - 1));
                }
                keys.push(key.substr(postLeftBracketPos, j - postLeftBracketPos));
                postLeftBracketPos = 0;
                if (key.charAt(j + 1) !== '[') {
                  break;
                }
              }
            }
          }
          if (!keys.length) {
            keys = [key];
          }
          for (j = 0; j < keys[0].length; j++) {
            chr = keys[0].charAt(j);
            if (chr === ' ' || chr === '.' || chr === '[') {
              keys[0] = keys[0].substr(0, j) + '_' + keys[0].substr(j + 1);
            }
            if (chr === '[') {
              break;
            }
          }

          obj = array;
          for (j = 0, keysLen = keys.length; j < keysLen; j++) {
            key = keys[j].replace(/^['"]/, '').replace(/['"]$/, '');
            lastIter = j !== keys.length - 1;
            lastObj = obj;
            if ((key !== '' && key !== ' ') || j === 0) {
              if (obj[key] === undef) {
                obj[key] = {};
              }
              obj = obj[key];
            }
            else { // To insert new dimension
              ct = -1;
              for (p in obj) {
                if (obj.hasOwnProperty(p)) {
                  if (+p > ct && p.match(/^\d+$/g)) {
                    ct = +p;
                  }
                }
              }
              key = ct + 1;
            }
          }
          lastObj[key] = value;
        }
      }
    };

    that.urlencode = function(str) {
      str = (str + '').toString();
      return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').
      replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
    };

    that.buildQueryString = function(formdata, numeric_prefix, arg_separator) {
      var value, key, tmp = [];

      var _http_build_query_helper = function (key, val, arg_separator) {
        var k, tmp = [];
        if (val === true) {
          val = "1";
        } else if (val === false) {
          val = "0";
        }
        if (val != null) {
          if(typeof(val) === "object") {
            for (k in val) {
              if (val[k] != null) {
                tmp.push(_http_build_query_helper(key + "[" + k + "]", val[k], arg_separator));
              }
            }
            return tmp.join(arg_separator);
          } else if (typeof(val) !== "function") {
            return that.urlencode(key) + "=" + that.urlencode(val);
          } else {
            throw new Error('There was an error processing for http_build_query().');
          }
        } else {
          return '';
        }
      };

      if (!arg_separator) {
        arg_separator = "&";
      }
      for (key in formdata) {
        value = formdata[key];
        if (numeric_prefix && !isNaN(key)) {
          key = String(numeric_prefix) + key;
        }
        var query=_http_build_query_helper(key, value, arg_separator);
        if(query != '') {
          tmp.push(query);
        }
      }

      return tmp.join(arg_separator);
    };

    that.buildUrl = function (url, query, fragment)
    {
        if (query || fragment) {
            var components = that.parseUrl(url);
            if (query) {
                if (typeof query != 'object') {
                    throw new Error('Argument "query" should be a "set" object.');
                }

                if (components.query)
                {
                    var oldQuery = {};
                    that.parseQueryString(components.query, oldQuery);
                    query = $.extend(oldQuery, query);
                }
                components.query = that.buildQueryString(query);
            }

            if (fragment) {
                if (typeof fragment != 'object') {
                    throw new Error('Argument "fragment" should be a "set" object.');
                }

                if (components.fragment){
                    var oldFragment = {};
                    that.parseQueryString(components.fragment, oldFragment);
                    fragment = $.extend(oldFragment, fragment);
                }
                components.fragment = that.buildQueryString(fragment);
            }

            url = (components.scheme ? components.scheme + "://" : "")
              + (components.user ? components.user + (components.pass ? ":" + components.pass : "") + "@" : "")
              + (components.host ? components.host : "")
              + (components.port ? components.port : "")
              + (components.path ? components.path : "")
              + ((components.query && components.query != '') ? "?" + components.query : "")
              + ((components.fragment && components.fragment != '') ? "#" + components.fragment : "");
        }

        return url;
    };

    var _locales = {
        'en' : {
            CONFIRM : 'Confirmation',
            ERROR   : 'Error',
            INFO    : 'Information',
            OK      : 'OK',
            CANCEL  : 'Cancel'
        },
        'zh_CN' : {
            CONFIRM : '确认',
            ERROR   : '错误',
            INFO    : '提示',
            OK      : 'OK',
            CANCEL  : '取消'
        }
    };

    var _heavyDialogTmpl = '<div class="dialog-box modal" tabindex="-1">' +
        '<div class="dialog-title modal-header">' +
    	'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' +
        '</div>' +
        '<div class="dialog-body modal-body"></div>' +
        '<div class="dialog-buttons modal-footer"></div>' +
        '</div>';

    var _liteDialogTmpl = '<div class="dialog-box modal-lite alert" tabindex="-1">' +
        '<p class="dialog-title"><button type="button" class="close" data-dismiss="modal">&times;</button></p>' +
        '<p class="dialog-body"></p><br>' +
        '<p class="dialog-buttons"></p>' +
        '</div>';

    function _translate(str, locale) {
        // we assume if no target locale is probided then we should take it from current setting
        if (locale == null) {
            locale = _locale;
        }
        if (typeof _locales[locale][str] === 'string') {
            return _locales[locale][str];
        }

        // if we couldn't find a lookup then try and fallback to a default translation
        if (locale != _defaultLocale) {
            return _translate(str, _defaultLocale);
        }

        // if we can't do anything then bail out with whatever string was passed in - last resort
        return str;
    }

    $('body').on('click', 'button[data-link]', function (e) {
        location.href = $(this).attr('data-link');
    });

    return that;

}(document, window.jQuery));

window.bluefinBH = bluefinBH;