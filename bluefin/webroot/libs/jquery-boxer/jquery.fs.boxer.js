/*
 * Boxer [Formstone Library]
 * @author Ben Plum
 * @version 1.6.5
 *
 * Copyright © 2013 Ben Plum <mr@benplum.com>
 * Released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
 */
 
if (jQuery) (function($) {
	
	// Default Options
	var options = {
		callback: function() {},
		customClass: "",
		duration: 250,
		fixed: false,
		formatter: function() {},
		height: 100,
		margin: 100,
		minHeight: 200,
		minWidth: 200,
		opacity: 0.75,
		retina: false,
		requestKey: "boxer",
		top: 0,
		width: 100
	};
	// Internal Data
	var data = {};
	var resizeTimer = null;
	
	// Public Methods
	var pub = {
		
		destroy: function() {
			_close($.Event("click"));
			return $(this).off(".boxer");
		},
		
		resize: function(e, height, width) {
			if (typeof data.$boxer != "undefined") {
				
				if (data.type == "element") {
					_sizeContent(data.$content.find(">:first-child"));
				}
				
				_open();
			}
		}
	};
	
	// Initialize
	function _init(opts) {
		options.formatter = _formatCaption;
		return $(this).on("click.boxer", $.extend({}, options, opts || {}), _build);
	}
	
	// Build Boxer
	function _build(e) {
		e.preventDefault();
		e.stopPropagation();
		
		// Check target type
		var $target = $(this),
			$object = e.data.$object,
			source = $target.attr("href") || "",
			checkExt = source.toLowerCase().split("."),
			extension = checkExt[ checkExt.length - 1 ],
			type = $target.data("type") || "";
		
		var is_image    = ( (type == "image") || (extension == "jpeg" || extension == "jpg" || extension == "gif" || extension == "png" || source.substr(0, 10) == "data:image") ),
			is_url 		= ( (type == "url") || (!is_image && source.substr(0, 4) == "http") ),
			is_element  = ( (type == "element") || (!is_image && !is_url && source.substr(0, 1) == "#") /* $(source).length > 0) */ ),
			is_object   = ( (typeof $object !== "undefined") );
		
		// Check if one already exists
		if ($("#boxer").length < 1 && (is_image || is_url || is_element || is_object)) {
			// Cache internal data
			data = {
				$target: $target,
				$object: $object,
				gallery: {
					active: false
				},
				options: e.data,
				type: (is_image) ? "image" : "element"
			};
			
			if (is_image) {
				// Check for gallery
				var rel = data.$target.attr("rel");
				if (typeof rel !== "undefined" && rel !== false) {
					data.gallery.active = true;
					data.gallery.rel = rel;
					data.gallery.$items = $("a[rel= " + data.gallery.rel + "]");
					data.gallery.index = data.gallery.$items.index(data.$target);
					data.gallery.total = data.gallery.$items.length - 1;
				}
			}
			
			// Assemble HTML
			var html = '<div id="boxer-overlay" class="' + data.options.customClass + '" style="opacity: 0"></div>';
			html += '<div id="boxer" class="' + data.options.customClass;
			if (is_url) {
				html += ' iframe';
			}
			if (is_element || is_object) {
				html += ' inline';
			}
			html += '" style="opacity: 0;';
			if (data.options.fixed === true) {
				html += ' position: fixed;'
			}
			html += '">';
			html += '<span class="boxer-close">Close</span>';
			html += '<div class="boxer-container" style="opacity: 0; height: ' + data.options.height + 'px; width: ' + data.options.width + 'px">';
			html += '<div class="boxer-content">';
			if (is_image) {
				html += '<div class="boxer-meta">';
				
				if (data.gallery.active) {
					html += '<p class="boxer-position"';
					if (data.gallery.total < 1) { 
						html += ' style="display: none;"'; 
					}
					html += '>';
					html += '<span class="current">' + (data.gallery.index + 1) + '</span> of <span class="total">' + (data.gallery.total + 1) + '</span>';
					html += '</p>';
					html += '<div class="boxer-arrow previous">Previous</div>';
					html += '<div class="boxer-arrow next">Next</div>';
					html += '<div class="boxer-caption gallery">';
				} else {
					html += '<div class="boxer-caption">';
				}
				
				html += data.options.formatter.apply($("body"), [data.$target]);
				html += '</div></div>'; // caption, meta
			}
			html += '</div></div></div>'; //container, content, boxer
			
			// Modify Dom
			$("body").append(html);
			
			// Cache jquery objects
			data.$overlay = $("#boxer-overlay");
			data.$boxer = $("#boxer");
			data.$container = data.$boxer.find(".boxer-container");
			data.$content = data.$boxer.find(".boxer-content");
			data.$meta = data.$boxer.find(".boxer-meta");
			data.$position = data.$boxer.find(".boxer-position");
			data.$caption = data.$boxer.find(".boxer-caption");
			data.$arrows = data.$boxer.find(".boxer-arrow");
			data.$animatables = $("#boxer-overlay, #boxer");
			data.padding = parseInt(data.$boxer.css("paddingTop"), 10) * 2;
			
			// Center / update gallery
			_center();
			if (data.gallery.active) {
				_updatePagination();
			}
			
			// Bind events
			$(window).on("resize.boxer", _resize)
					 .on("keydown.boxer", _keypress);
			$("body").on("click.boxer", "#boxer-overlay, #boxer .boxer-close", _close);
			if (data.gallery.active) {
				data.$boxer.on("click.boxer", ".boxer-arrow", _advanceGallery);
			}
			if (is_url || is_element || is_object) {
				data.$boxer.on("resize.boxer", pub.resize)
						   .on("close.boxer", _close);
			}
			
			data.$overlay.stop().animate({ opacity: data.options.opacity }, data.options.duration);
			data.$boxer.stop().animate({ opacity: 1 }, data.options.duration, function() { 
				if (is_image) {
					_loadImage(source);
				} else if(is_url) {
					_loadURL(source);
				} else if (is_element) {
					_cloneElement(source);
				} else if (is_object) {
					_appendObject(data.$object);
				} else {
					$.error("BOXER: '" +  source + "' is not valid.");
				}
			});
		}
		if (is_object) {
			return data.$boxer;
		}
	}
	
	// Open boxer
	function _open() {
		var newLeft = ($(window).width() - data.contentWidth - data.padding) / 2;
		var newTop = (data.options.top <= 0) ? (($(window).height() - data.contentHeight - data.padding) / 2) : data.options.top;
		if (data.options.fixed !== true) {
			newTop += $(window).scrollTop();
		}
		
		var arrowHeight = data.$arrows.outerHeight();
		data.$arrows.css({ marginTop: ((data.contentHeight - data.metaHeight - arrowHeight) / 2) });
		
		data.$boxer.stop().animate({ left: newLeft, top: newTop }, data.options.duration);
		data.$container.show().stop().animate({ height: data.contentHeight, width: data.contentWidth }, data.options.duration, function(e) {
			data.$container.stop().animate({ opacity: 1 }, data.options.duration);
			data.$boxer.find(".boxer-close").stop().animate({ opacity: 1 }, data.options.duration);
			
			// Fire callback
			data.options.callback.apply(data.$boxer);
		});
	}
	
	// Close boxer
	function _close(e) {
		e.preventDefault();
		e.stopPropagation();
		
		if (typeof data.$animatables !== "undefined") {
			data.$animatables.stop().animate({ opacity: 0 }, data.options.duration, function() {
				$(this).remove();
			});
			
			clearTimeout(resizeTimer);
			resizeTimer = null;
			
			// Clean up
			$(window).off(".boxer")
			$("body").off(".boxer");
			if (data.gallery.active) {
				data.$boxer.off(".boxer");
			}
			data = {};
		}
	}
	
	// Debounce resize events
	function _resize() {
		if (resizeTimer !== null) {
			clearTimeout(resizeTimer);
			resizeTimer = null;
		}
		resizeTimer = setTimeout(function() { _center() }, 10);
	}
	
	// Center boxer on resize
	function _center() {
		var newLeft = ($(window).width() - data.$boxer.width() - data.padding) / 2;
		var newTop = (data.options.top <= 0) ? (($(window).height() - data.$boxer.height() - data.padding) / 2) : data.options.top;
		if (data.options.fixed !== true) {
			newTop += $(window).scrollTop();
		}
		data.$boxer.css({ left: newLeft, top: newTop });
	}
	
	// Load new image
	function _loadImage(source) {
		// Cache current image
		data.$image = $("<img />");
		
		data.$image.one("load.boxer", function() {
			data.originalHeight = data.$image[0].height;
			data.originalWidth = data.$image[0].width;
			
			if (data.options.retina) {
				data.originalHeight /= 2;
				data.originalWidth /= 2;
			}
			
			data.$content.prepend(data.$image);
			if (data.$caption.html() == "") { 
				data.$caption.hide(); 
			} else { 
				data.$caption.show(); 
			}
			
			// Size content to be sure it fits the viewport
			if (_sizeImage(0)) {
				_open();
			}
		}).attr("src", source).addClass("boxer-image");
		
		// If image has already loaded into cache, trigger load event
		if (data.$image[0].complete) {
			data.$image.trigger("load");
		}
	}
	
	// Format captions
	function _formatCaption($target) {
		var title = $target.attr("title");
		return (title != "" && title !== undefined) ? '<p class="caption">' + title + '</p>' : "";
	}
	
	// Resize image to fit in viewport
	function _sizeImage(count) {
		var height = (count == 0) ? data.originalHeight : data.$image.outerHeight();
		var width = (count == 0) ? data.originalWidth : data.$image.outerWidth();
		var metaHeight = (count == 0) ? 0 : data.metaHeight;
		var windowWidth = $(window).width() - data.options.margin - data.padding;
		var windowHeight = $(window).height() - data.options.margin - data.padding - metaHeight;
		
		// Double check min and max
		if (height < data.options.minHeight) {
			data.options.minHeight = height;
		}
		if (width < data.options.minWidth) {
			data.options.minWidth = width;
		}
		
		var newWidth = width;
		var newHeight = height;
		var ratio;

		if (width > height) {
			ratio = height / width;
			newWidth = windowWidth;
			newHeight = newWidth * ratio;
			
			if (newHeight > windowHeight) {
				ratio = width / height;
				newHeight = windowHeight;
				newWidth = newHeight * ratio;
			}
		} else {
			ratio = width / height;
			newHeight = windowHeight;
			newWidth = newHeight * ratio;
			
			if (newWidth > windowWidth) {
				ratio = height / width;
				newWidth = windowWidth;
				newHeight = newWidth * ratio;
			}
		}
		
		if (newWidth > width || newHeight > height) {
			newWidth = width;
			newHeight = height;
		}
		
		if (newWidth < data.options.minWidth || newHeight < data.options.minHeight) {
			if (width < height) {
				ratio = height / width;
				newWidth = data.options.minWidth;
				newHeight = newWidth * ratio;
			} else {
				ratio = width / height;
				newHeight = data.options.minHeight;
				newWidth = newHeight * ratio;
			}
		}
		
		// Modify DOM
		data.$content.css({ width: newWidth });
		data.$meta.css({ width: newWidth });
		data.$image.css({ height: newHeight, width: newWidth });
		
		data.metaHeight = data.$meta.outerHeight(true);
		data.contentWidth = newWidth;
		data.contentHeight = newHeight + data.metaHeight;
		
		if (data.contentHeight > windowHeight && count < 2) {
			return _sizeImage(count+1);
		}
		return true;
	}
	
	// Advance gallery
	function _advanceGallery(e) {
		e.preventDefault();
		e.stopPropagation();
		
		// Click target
		var $arrow = $(this);
		
		if (!$arrow.hasClass("disabled")) {
			data.gallery.index += ($arrow.hasClass("next")) ? 1 : -1;
			if (data.gallery.index > data.gallery.total) {
				data.gallery.index = data.gallery.total;
			}
			if (data.gallery.index < 0) {
				data.gallery.index = 0;
			}
			
			data.$container.stop().animate({opacity: 0}, data.options.duration, function() {
				data.$image.remove();
				data.$target = data.gallery.$items.eq(data.gallery.index);
				
				data.$caption.html(data.options.formatter.apply($("body"), [data.$target]));
				data.$position.find(".current").html(data.gallery.index + 1);
				
				_loadImage(data.$target.attr("href"));
				_updatePagination();
			});
		}
	}
	
	// Update galery arrows
	function _updatePagination() {
		data.$arrows.removeClass("disabled");
		if (data.gallery.index == 0) { 
			data.$arrows.filter(".previous").addClass("disabled");
		}
		if (data.gallery.index == data.gallery.total) {
			data.$arrows.filter(".next").addClass("disabled");
		}
	}
	
	// Handle keypress in gallery
	function _keypress(e) {
		if (data.gallery.active && (e.keyCode == 37 || e.keyCode == 39)) {
			e.preventDefault();
			e.stopPropagation();
			
			data.$arrows.filter((e.keyCode == 37) ? ".previous" : ".next").trigger("click");
		} else if (e.keyCode == 27) {
			data.$boxer.find(".boxer-close").trigger("click");
		}
	}
	
	// Clone inline element
	function _cloneElement(id) {
		var $clone = $(id).find(">:first-child").clone();
		_appendObject($clone);
	}
	
	// Load URL into iFrame
	function _loadURL(source) {
		source = source + ((source.indexOf("?") > -1) ? "&"+options.requestKey+"=true" : "?"+options.requestKey+"=true");
		var $iframe = $('<iframe class="boxer-iframe" src="' + source + '" />');
		_appendObject($iframe);
	}
	
	// Append jQuery object
	function _appendObject($obj) {
		data.$content.append($obj);
		_sizeContent($obj);
		_open();
	}
	
	// Size jQuery object
	function _sizeContent($obj) {
		var objHeight = $obj.outerHeight(true),
			objWidth = $obj.outerWidth(true),
			windowHeight = $(window).height() - data.options.margin - data.padding,
			windowWidth = $(window).width() - data.options.margin - data.padding,
			dataHeight = data.$target.data("height"),
			dataWidth = data.$target.data("width"),
			maxHeight = (windowHeight < 0) ? options.minHeight : windowHeight,
			iframe = $obj.is("iframe");
		
		data.contentHeight = (dataHeight != undefined) ? dataHeight : (iframe) ? windowHeight : objHeight;
		data.contentWidth = (dataWidth != undefined) ? dataWidth : (iframe) ? windowWidth : objWidth;
		
		if (data.contentHeight > maxHeight) {
			data.contentHeight = maxHeight;
			if (!iframe) {
				data.$content.css({ overflowY: "scroll" });
			}
		} else {
			data.$content.css({ overflowY: "auto" });
		}
		data.$content.css({ height: data.contentHeight, width: data.contentWidth });
	}
	
	// Define Plugin
	$.fn.boxer = function(method) {
		if (pub[method]) {
			return pub[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method === 'object' || !method) {
			return _init.apply(this, arguments);
		}
		return this;	
	};
	
	$.boxer = function($target, opts) {
		return _build($.Event("click", { data: $.extend({
			$object: $target
		}, options, opts || {}) }));
	}
})(jQuery);