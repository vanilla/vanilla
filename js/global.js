// This file contains javascript that is global to the entire Garden application


// Global vanilla library function.
(function(window, $) {

var Vanilla = function() { };

Vanilla.fn = Vanilla.prototype;

if (!window.console)
   window.console = { log: function() {} };

Vanilla.scrollTo = function(q) {
    var top = $(q).offset().top;
    window.scrollTo(0, top);
    return false;
};

// Add a stub for embedding.
Vanilla.parent = function() {};
Vanilla.parent.callRemote = function(func, args, success, failure) { console.log("callRemote stub: "+func, args); };

window.Vanilla = Vanilla;

})(window, jQuery);

// Stuff to fire on document.ready().
jQuery(document).ready(function($) {

   $.postParseJson = function(json) {
      if (json.Data) json.Data = $.base64Decode(json.Data);
      return json;
   }

	var keyString = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";

	var uTF8Encode = function(string) {
		string = string.replace(/\x0d\x0a/g, "\x0a");
		var output = "";
		for (var n = 0; n < string.length; n++) {
			var c = string.charCodeAt(n);
			if (c < 128) {
				output += String.fromCharCode(c);
			} else if ((c > 127) && (c < 2048)) {
				output += String.fromCharCode((c >> 6) | 192);
				output += String.fromCharCode((c & 63) | 128);
			} else {
				output += String.fromCharCode((c >> 12) | 224);
				output += String.fromCharCode(((c >> 6) & 63) | 128);
				output += String.fromCharCode((c & 63) | 128);
			}
		}
		return output;
	};

	var uTF8Decode = function(input) {
		var string = "";
		var i = 0;
		var c = c1 = c2 = 0;
		while ( i < input.length ) {
			c = input.charCodeAt(i);
			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			} else if ((c > 191) && (c < 224)) {
				c2 = input.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			} else {
				c2 = input.charCodeAt(i+1);
				c3 = input.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}
		}
		return string;
	}

	$.extend({
      // private property
      keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
		base64Encode: function(input) {
			var output = "";
			var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
			var i = 0;
			input = uTF8Encode(input);
			while (i < input.length) {
				chr1 = input.charCodeAt(i++);
				chr2 = input.charCodeAt(i++);
				chr3 = input.charCodeAt(i++);
				enc1 = chr1 >> 2;
				enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
				enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
				enc4 = chr3 & 63;
				if (isNaN(chr2)) {
					enc3 = enc4 = 64;
				} else if (isNaN(chr3)) {
					enc4 = 64;
				}
				output = output + keyString.charAt(enc1) + keyString.charAt(enc2) + keyString.charAt(enc3) + keyString.charAt(enc4);
			}
			return output;
		},
		base64Decode: function(input) {
			var output = "";
			var chr1, chr2, chr3;
			var enc1, enc2, enc3, enc4;
			var i = 0;
			input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
			while (i < input.length) {
				enc1 = keyString.indexOf(input.charAt(i++));
				enc2 = keyString.indexOf(input.charAt(i++));
				enc3 = keyString.indexOf(input.charAt(i++));
				enc4 = keyString.indexOf(input.charAt(i++));
				chr1 = (enc1 << 2) | (enc2 >> 4);
				chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
				chr3 = ((enc3 & 3) << 6) | enc4;
				output = output + String.fromCharCode(chr1);
				if (enc3 != 64) {
					output = output + String.fromCharCode(chr2);
				}
				if (enc4 != 64) {
					output = output + String.fromCharCode(chr3);
				}
			}
			output = uTF8Decode(output);
			return output;
		}
	});

	gdn = {focused: true};
	gdn.Libraries = {};

   $(window).blur(function() {
      gdn.focused = false;
   });
   $(window).focus(function(){
      gdn.focused = true;
   });

   // Grab a definition from hidden inputs in the page
   gdn.definition = function(definition, defaultVal, set) {
      if (defaultVal == null)
         defaultVal = definition;

      var $def = $('#Definitions #' + definition);
      var def;

      if(set) {
         $def.val(defaultVal);
         def = defaultVal;
      } else {
         def = $def.val();
         if ($def.length == 0)
            def = defaultVal;
      }

      return def;
   }

   gdn.disable = function(e, progressClass) {
      var href = $(e).attr('href');
      if (href) {
         $.data(e, 'hrefBak', href);
      }
      $(e).addClass(progressClass ? progressClass : 'InProgress').removeAttr('href').attr('disabled', true);
   }

   gdn.enable = function(e) {
      $(e).attr('disabled', false).removeClass('InProgress');
      var href = $.data(e, 'hrefBak');
      if (href) {
         $(e).attr('href', href);
         $.removeData(e, 'hrefBak');
      }
   }

   gdn.elementSupports = function(element, attribute) {
      var test = document.createElement(element);
      if (attribute in test)
         return true;
      else
         return false;
   }

   gdn.querySep = function(url) {
      return url.indexOf('?') == -1 ? '?' : '&';
   }

   // password strength check
   gdn.password = function(password, username) {
      var translations = gdn.definition('PasswordTranslations', 'Too Short,Contains Username,Very Weak,Weak,Ok,Good,Strong').split(',');

      // calculate entropy
      var alphabet = 0;
      if ( password.match(/[0-9]/) )
         alphabet += 10;
      if ( password.match(/[a-z]/) )
         alphabet += 26;
      if ( password.match(/[A-Z]/) )
         alphabet += 26;
      if ( password.match(/[^a-zA-Z0-9]/) )
         alphabet += 31;
      var natLog = Math.log(Math.pow(alphabet, password.length));
      var entropy = natLog / Math.LN2;

      var response = {
         pass: false,
         symbols: alphabet,
         entropy: entropy,
         score: 0
      };

      // reject on length
      var length = password.length;
      response.length = length;
      var requiredLength = gdn.definition('MinPassLength', 6);
      var requiredScore = gdn.definition('MinPassScore', 2);
      response.required = requiredLength;
      if (length < requiredLength) {
         response.reason = translations[0];
         return response;
      }

      // password1 == username
      if (username) {
         if (password.toLowerCase().indexOf(username.toLowerCase()) >= 0) {
            response.reason = translations[1];
            return response;
         }
      }

      if (entropy < 30) {
         response.score = 1;
         response.reason = translations[2]; // very weak
      } else if (entropy < 40) {
         response.score = 2;
         response.reason = translations[3]; // weak
      } else if (entropy < 55) {
         response.score = 3;
         response.reason = translations[4]; // ok
      } else if (entropy < 70) {
         response.score = 4;
         response.reason = translations[5]; // good
      } else {
         response.score = 5;
         response.reason = translations[6]; // strong
      }

      return response;
   }

   // Go to notifications if clicking on a user's notification count
   $('li.UserNotifications a span').click(function() {
      document.location = gdn.url('/profile/notifications');
      return false;
   });

   // This turns any anchor with the "Popup" class into an in-page pop-up (the
   // view of the requested in-garden link will be displayed in a popup on the
   // current screen).
   if ($.fn.popup) {

      // Previously, jquery.popup used live() to attach events, even to elements
      // that do not yet exist. live() has been deprecated. Vanilla upgraded
      // jQuery to version 1.10.2, which removed a lot of code.  Instead, event
      // delegation will need to be used, which means everywhere that Popup
      // is called, will need to have a very high up parent delegate to it.
      //$('a.Popup').popup();
		//$('a.PopConfirm').popup({'confirm' : true, 'followConfirm' : true});

      $('a.Popup').popup();
      $('a.PopConfirm').popup({'confirm' : true, 'followConfirm' : true});
   }

   $(document).delegate(".PopupWindow", 'click', function() {
      var $this = $(this);

      if ($this.hasClass('NoMSIE') && /msie/.test(navigator.userAgent.toLowerCase())) {
         return;
      }

      var width = $this.attr('popupWidth');width = width ? width : 960;
      var height = $this.attr('popupHeight');height = height ? height : 600;
      var left = (screen.width - width) / 2;
      var top = (screen.height - height) / 2;

      var id = $this.attr('id');
      var href = $this.attr('href');
      if ($this.attr('popupHref'))
         href = $this.attr('popupHref');
      else
         href += gdn.querySep(href)+'display=popup';

      var win = window.open(href, 'Window_' + id, "left="+left+",top="+top+",width="+width+",height="+height+",status=0,scrollbars=0");
      if (win)
         win.focus();
      return false;
   });

   // This turns any anchor with the "Popdown" class into an in-page pop-up, but
   // it does not hijack forms in the popup.
   if ($.fn.popup)
      $('a.Popdown').popup({hijackForms: false});

   // This turns SignInPopup anchors into in-page popups
   if ($.fn.popup)
      $('a.SignInPopup').popup({containerCssClass:'SignInPopup'});

   if ($.fn.popup)
      $(document).delegate('.PopupClose', 'click', function(event){
         var Popup = $(event.target).parents('.Popup');
         if (Popup.length) {
            var PopupID = Popup.prop('id');
            $.popup.close({popupId: PopupID});
         }
      });

   // Make sure that message dismissalls are ajax'd
   $(document).delegate('a.Dismiss', 'click', function() {
      var anchor = this;
      var container = $(anchor).parent();
      var transientKey = gdn.definition('TransientKey');
      var data = 'DeliveryType=BOOL&TransientKey=' + transientKey;
      $.post($(anchor).attr('href'), data, function(response) {
         if (response == 'TRUE')
            $(container).fadeOut('fast',function() {
               $(this).remove();
            });
      });
      return false;
   });

   // This turns any form into a "post-in-place" form so it is ajaxed to save
   // without a refresh. The form must be within an element with the "AjaxForm"
   // class.
   if ($.fn.handleAjaxForm)
      $('.AjaxForm').handleAjaxForm();

   // Make the highlight effect themable.
   if ($.effects && $.effects.highlight) {
      $.effects.highlight0 = $.effects.highlight;

      $.effects.highlight = function(opts) {
         var color = $('#HighlightColor').css('backgroundColor');
         if (color)
            opts.options.color = color;
         return $.effects.highlight0.call(this, opts);
		};
	}

   // Handle ToggleMenu toggling and set up default state
   $('[class^="Toggle-"]').hide(); // hide all toggle containers
   $('.ToggleMenu a').click(function() {
      // Make all toggle buttons and toggle containers inactive
		$(this).parents('.ToggleMenu').find('li').removeClass('Active');
      $('[class^="Toggle-"]').hide();
		var item = $(this).parents('li'); // Identify the clicked container
		// The selector of the container that should be revealed.
      var containerSelector = '.Toggle-' + item.attr('class');
      containerSelector = containerSelector.replace(/Handle-/gi, '');
		// Reveal the container & make the button active
      item.addClass('Active'); // Make the clicked form button active
      $(containerSelector).show();
      return false;
   });
   $('.ToggleMenu .Active a').click(); // reveal the currently active item.

	// Show hoverhelp on hover
	$('.HoverHelp').hover(
		function() {
			$(this).find('.Help').show();
		},
		function() {
			$(this).find('.Help').hide();
		}
	);

   // If a page loads with a hidden redirect url, go there after a few moments.
   var RedirectUrl = gdn.definition('RedirectUrl', '');
   var CheckPopup = gdn.definition('CheckPopup', '');
   if (RedirectUrl != '') {
      if (CheckPopup && window.opener) {
         window.opener.location.replace(RedirectUrl);
         window.close();
      } else {
         document.location.replace(RedirectUrl);
      }
   }

   // Make tables sortable if the tableDnD plugin is present.
   if ($.tableDnD)
      $("table.Sortable").tableDnD({onDrop: function(table, row) {
         var tableId = $($.tableDnD.currentTable).attr('id');
         // Add in the transient key for postback authentication
         var transientKey = gdn.definition('TransientKey');
         var data = $.tableDnD.serialize() + '&DeliveryType=BOOL&TableID=' + tableId + '&TransientKey=' + transientKey;
         var webRoot = gdn.definition('WebRoot', '');
         $.post(gdn.combinePaths(webRoot, 'index.php?p=/dashboard/utility/sort/'), data, function(response) {
            if (response == 'TRUE')
               $('#'+tableId+' tbody tr td').effect("highlight", {}, 1000);

         });
      }});

   // Make sure that the commentbox & aboutbox do not allow more than 1000 characters
   $.fn.setMaxChars = function(iMaxChars) {
      $(this).bind('keyup', function() {
         var txt = $(this).val();
         if (txt.length > iMaxChars)
            $(this).val(txt.substr(0, iMaxChars));
      });
   }

   // Generate a random string of specified length
   gdn.generateString = function(length) {
      if (length == null)
         length = 5;

      var chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%*';
      var string = '';
      var pos = 0;
      for (var i = 0; i < length; i++) {
         pos = Math.floor(Math.random() * chars.length);
         string += chars.substring(pos, pos + 1);
      }
      return string;
   };

   // Combine two paths and make sure that there is only a single directory concatenator
   gdn.combinePaths = function(path1, path2) {
      if (path1.substr(-1, 1) == '/')
         path1 = path1.substr(0, path1.length - 1);

      if (path2.substring(0, 1) == '/')
         path2 = path2.substring(1);

      return path1 + '/' + path2;
   };

   gdn.processTargets = function(targets, $elem, $parent) {
      if(!targets || !targets.length)
         return;

      var tar = function(q) {
         switch (q) {
            case '!element':
               return $elem;
            case '!parent':
               return $parent;
            default:
               return q;
         }
      }

      for(i = 0; i < targets.length; i++) {
         var item = targets[i];

         if (jQuery.isArray(item.Target)) {
            $target = $(tar(item.Target[0]), tar(item.Target[1]));
         } else {
            $target = $(tar(item.Target));
         }

         switch(item.Type) {
            case 'AddClass':
               $target.addClass(item.Data);
               break;
            case 'Ajax':
               $.ajax({
                  type: "POST",
                  url: item.Data
               });
               break;
            case 'Append':
               $target.append(item.Data);
               break;
            case 'Before':
               $target.before(item.Data);
               break;
            case 'After':
               $target.after(item.Data);
               break;
            case 'Highlight':
               $target.effect("highlight", {}, "slow");
               break;
            case 'Prepend':
               $target.prepend(item.Data);
               break;
            case 'Redirect':
               window.location.replace(item.Data);
               break;
            case 'Refresh':
               window.location.reload();
               break;
            case 'Remove':
               $target.remove();
               break;
            case 'RemoveClass':
               $target.removeClass(item.Data);
               break;
            case 'ReplaceWith':
               $target.replaceWith(item.Data);
               break;
            case 'SlideUp':
               $target.slideUp('fast');
               break;
            case 'SlideDown':
               $target.slideDown('fast');
               break;
            case 'Text':
               $target.text(item.Data);
               break;
            case 'Html':
               $target.html(item.Data);
               break;
            case 'Callback':
               jQuery.proxy(window[item.Data],$target)();
               break;
         }
      }
   };

   gdn.requires = function(Library) {
      if (!(Library instanceof Array))
         Library = [Library];

      var Response = true;

      $(Library).each(function(i,Lib){
         // First check if we already have this library
         var LibAvailable = gdn.available(Lib);

         if (!LibAvailable) Response = false;

         // Skip any libs that are ready or processing
         if (gdn.Libraries[Lib] === false || gdn.Libraries[Lib] === true)
            return;

         // As yet unseen. Try to load
         gdn.Libraries[Lib] = false;
         var Src = '/js/'+Lib+'.js';
         var head = document.getElementsByTagName('head')[0];
         var script = document.createElement('script');
         script.type = 'text/javascript';
         script.src = Src;
         head.appendChild(script);
      });

      if (Response) gdn.loaded(null);
      return Response;
   };

   gdn.loaded = function(Library) {
      if (Library != null)
         gdn.Libraries[Library] = true;

      $(document).trigger('libraryloaded',[Library])
   }

   gdn.available = function(Library) {
      if (!(Library instanceof Array))
         Library = [Library];

      for (var i = 0; i<Library.length; i++) {
         var Lib = Library[i];
         if (gdn.Libraries[Lib] !== true) return false;
      }
      return true;
   }

   gdn.url = function(path) {
      if (path.indexOf("//") >= 0)
         return path; // this is an absolute path.

      var urlFormat = gdn.definition("UrlFormat", "/{Path}");

      if (path.substr(0, 1) == "/")
         path = path.substr(1);

      if (urlFormat.indexOf("?") >= 0)
         path = path.replace("?", "&");

      return urlFormat.replace("{Path}", path);
   };

   // Fill in placeholders.
   if (!gdn.elementSupports('input', 'placeholder')) {
      $('input:text,textarea').not('.NoIE').each(function() {
         var $this = $(this);
         var placeholder = $this.attr('placeholder');

         if (!$this.val() && placeholder) {
            $this.val(placeholder);
            $this.blur(function() {
               if ($this.val() == '')
                  $this.val(placeholder);
            });
            $this.focus(function() {
               if ($this.val() == placeholder)
                  $this.val('');
            });
            $this.closest('form').bind('submit', function() {
               if ($this.val() == placeholder)
                  $this.val('');
            });
         }
      });
   }

//   var searchText = gdn.definition('Search', 'Search');
//   if (!$('div.Search input.InputBox').val())
//      $('div.Search input.InputBox').val(searchText);
//   $('div.Search input.InputBox').blur(function() {
//      if (typeof $(this).val() == 'undefined' || $(this).val() == '')
//         $(this).val(searchText);
//   });
//   $('div.Search input.InputBox').focus(function() {
//      if ($(this).val() == searchText)
//         $(this).val('');
//   });

   $.fn.popin = function(options) {
      var settings = $.extend({}, options);

      this.each(function(i, elem) {
         var url = $(elem).attr('rel');
         var $elem = $(elem);
         $.ajax({
            url: gdn.url(url),
            data: {DeliveryType: 'VIEW'},
            success: function(data) {
               $elem.html(data);
            },
            complete: function() {
               $elem.removeClass('Progress TinyProgress InProgress');
               if (settings.complete != undefined) {
                  settings.complete($elem);
               }
            }
         });
     });
   };
   $('.Popin').popin();

   var hijackClick = function(e) {
      var $elem = $(this);
      var $parent = $(this).closest('.Item');
      var $flyout = $elem.closest('.ToggleFlyout');
      var href = $elem.attr('href');
      var progressClass = $elem.hasClass('Bookmark') ? 'Bookmarking' : 'InProgress';

      if (!href)
         return;
      gdn.disable(this, progressClass);
      e.stopPropagation();

      $.ajax({
         type: "POST",
         url: href,
         data: { DeliveryType: 'VIEW', DeliveryMethod: 'JSON', TransientKey: gdn.definition('TransientKey') },
         dataType: 'json',
         complete: function() {
            gdn.enable(this);
            $elem.removeClass(progressClass);
            $elem.attr('href', href);

            // If we are in a flyout, close it.
            $flyout.removeClass('Open').find('.Flyout').hide();
         },
         error: function(xhr) {
            gdn.informError(xhr);
         },
         success: function(json) {
            if (json == null) json = {};

            var informed = gdn.inform(json);
            gdn.processTargets(json.Targets, $elem, $parent);
            // If there is a redirect url, go to it.
            if (json.RedirectUrl) {
               setTimeout(function() {
                     window.location.replace(json.RedirectUrl);
                  },
                  informed ? 3000 : 0);
            }
         }
      });

      return false;
   };
   $(document).delegate('.Hijack', 'click', hijackClick);



   // Activate ToggleFlyout and ButtonGroup menus
   $(document).delegate('.ButtonGroup > .Handle', 'click', function() {
      var buttonGroup = $(this).closest('.ButtonGroup');
      if (buttonGroup.hasClass('Open')) {
         // Close
         $('.ButtonGroup').removeClass('Open');
      } else {
         // Close all other open button groups
         $('.ButtonGroup').removeClass('Open');
         // Open this one
         buttonGroup.addClass('Open');
      }
      return false;
   });
   var lastOpen = null;
   $(document).delegate('.ToggleFlyout', 'click', function(e) {

      var $flyout = $('.Flyout', this);
        var isHandle = false;

        if ($(e.target).closest('.Flyout').length == 0) {
           e.stopPropagation();
           isHandle = true;
        } else if ($(e.target).hasClass('Hijack') || $(e.target).closest('a').hasClass('Hijack')) {
           return;
        }
        e.stopPropagation();

      // Dynamically fill the flyout.
      var rel = $(this).attr('rel');
      if (rel) {
         $(this).attr('rel', '');
         $flyout.html('<div class="InProgress" style="height: 30px"></div>');

         $.ajax({
            url: gdn.url(rel),
            data: {DeliveryType: 'VIEW'},
            success: function(data) {
               $flyout.html(data);
            },
            error: function(xhr) {
               $flyout.html('');
               gdn.informError(xhr, true);
            }
         });
      }

      if ($flyout.css('display') == 'none') {
         if (lastOpen != null) {
            $('.Flyout', lastOpen).hide();
            $(lastOpen).removeClass('Open').closest('.Item').removeClass('Open');
         }

         $(this).addClass('Open').closest('.Item').addClass('Open');
         $flyout.show();
         lastOpen = this;
      } else {
         $flyout.hide();
         $(this).removeClass('Open').closest('.Item').removeClass('Open');
      }

        if (isHandle)
           return false;
   });

   // Close ToggleFlyout menu even if their links are hijacked
   $(document).delegate('.ToggleFlyout a', 'mouseup', function() {
      if ($(this).hasClass('FlyoutButton'))
         return;

      $('.ToggleFlyout').removeClass('Open').closest('.Item').removeClass('Open');
      $('.Flyout').hide();
   });

   $(document).delegate(document, 'click', function() {
      if (lastOpen) {
         $('.Flyout', lastOpen).hide();
         $(lastOpen).removeClass('Open').closest('.Item').removeClass('Open');
      }
      $('.ButtonGroup').removeClass('Open');
   });

   // Add a spinner onclick of buttons with this class
   $(document).delegate('input.SpinOnClick', 'click', function() {
      $(this).before('<span class="AfterButtonLoading">&#160;</span>').removeClass('SpinOnClick');
   });

   // Confirmation for item removals
   $('a.RemoveItem').click(function() {
      if (!confirm('Are you sure you would like to remove this item?')) {
         return false;
      }
   });

   if (window.location.hash == '') {
      // Jump to the hash if desired.
      if (gdn.definition('LocationHash', 0) != 0) {
         $(window).load(function() {
            window.location.hash = gdn.definition('LocationHash');
         });
      }
      if (gdn.definition('ScrollTo', 0) != 0) {
         var scrollTo = $(gdn.definition('ScrollTo'));
         if (scrollTo.length > 0) {
            $('html').animate({
               scrollTop: scrollTo.offset().top - 10
            });
         }
      }
   }

   gdn.stats = function() {
      // Call directly back to the deployment and invoke the stats handler
      var StatsURL = gdn.url('settings/analyticstick.json');
      var SendData = {
            'TransientKey': gdn.definition('TransientKey'),
            'Path': gdn.definition('Path'),
            'Args': gdn.definition('Args'),
            'ResolvedPath': gdn.definition('ResolvedPath'),
            'ResolvedArgs': gdn.definition('ResolvedArgs')
         };

      if (gdn.definition('TickExtra', null) != null)
         SendData.TickExtra = gdn.definition('TickExtra');

      jQuery.ajax({
         dataType: 'json',
         type: 'post',
         url: StatsURL,
         data: SendData,
         success: function(json) {
            gdn.inform(json);
         }
      });
   }

   // Ping back to the deployment server to track views, and trigger
   // conditional stats tasks
   var AnalyticsTask = gdn.definition('AnalyticsTask', false);
   if (AnalyticsTask == 'tick')
	     gdn.stats();

   // If a dismissable InformMessage close button is clicked, hide it.
   $(document).delegate('div.InformWrapper.Dismissable a.Close', 'click', function() {
      $(this).parents('div.InformWrapper').fadeOut('fast', function() {
         $(this).remove();
      });
   });

	gdn.setAutoDismiss = function() {
		var timerId = $('div.InformMessages').attr('autodismisstimerid');
		if (!timerId) {
			timerId = setTimeout(function() {
				$('div.InformWrapper.AutoDismiss').fadeOut('fast', function() {
					$(this).remove();
				});
				$('div.InformMessages').removeAttr('autodismisstimerid');
			}, 7000);
			$('div.InformMessages').attr('autodismisstimerid', timerId);
		}
	}

	// Handle autodismissals
	$('div.InformWrapper.AutoDismiss:first').livequery(function() {
		gdn.setAutoDismiss();
	});

	// Prevent autodismiss if hovering any inform messages
	$(document).delegate('div.InformWrapper', 'mouseover mouseout', function(e) {
		if (e.type == 'mouseover') {
			var timerId = $('div.InformMessages').attr('autodismisstimerid');
			if (timerId) {
				clearTimeout(timerId);
				$('div.InformMessages').removeAttr('autodismisstimerid');
			}
		} else {
			gdn.setAutoDismiss();
		}
	});

   // Take any "inform" messages out of an ajax response and display them on the screen.
   gdn.inform = function(response) {
		if (!response)
			return false;

      if (!response.InformMessages || response.InformMessages.length == 0)
         return false;

		// If there is no message container in the page, add one
		var informMessages = $('div.InformMessages');
		if (informMessages.length == 0) {
			$('<div class="InformMessages"></div>').appendTo('body');
			informMessages = $('div.InformMessages');
		}
		var wrappers = $('div.InformMessages div.InformWrapper');

		// Loop through the inform messages and add them to the container
		for (var i = 0; i < response.InformMessages.length; i++) {
			css = 'InformWrapper';
			if (response.InformMessages[i]['CssClass'])
				css += ' ' + response.InformMessages[i]['CssClass'];

			elementId = '';
			if (response.InformMessages[i]['id'])
				elementId = response.InformMessages[i]['id'];

			sprite = '';
			if (response.InformMessages[i]['Sprite']) {
				css += ' HasSprite';
				sprite = response.InformMessages[i]['Sprite'];
			}

			dismissCallback = response.InformMessages[i]['DismissCallback'];
			dismissCallbackUrl = response.InformMessages[i]['DismissCallbackUrl'];
			if (dismissCallbackUrl)
				dismissCallbackUrl = gdn.url(dismissCallbackUrl);

			try {
				var message = response.InformMessages[i]['Message'];
				var emptyMessage = message == '';

				// Is there a sprite?
				if (sprite != '')
					message = '<span class="InformSprite '+sprite+'"></span>' + message;

				// If the message is dismissable, add a close button
				if (css.indexOf('Dismissable') > 0)
					message = '<a class="Close"><span>×</span></a>' + message;

				message = '<div class="InformMessage">'+message+'</div>';
				// Insert any transient keys into the message (prevents csrf attacks in follow-on action urls).
				message = message.replace(/{TransientKey}/g, gdn.definition('TransientKey'));
				// Insert the current url as a target for inform anchors
				message = message.replace(/{SelfUrl}/g, document.URL);

				var skip = false;
				for (var j = 0; j < wrappers.length; j++) {
					if ($(wrappers[j]).text() == $(message).text()) {
						skip = true;
					}
				}
				if (!skip) {
					if (elementId != '') {
						$('#'+elementId).remove();
						elementId = ' id="'+elementId+'"';
					}
					if (!emptyMessage) {
						informMessages.prepend('<div class="'+css+'"'+elementId+'>'+message+'</div>');
						// Is there a callback or callback url to request on dismiss of the inform message?
						if (dismissCallback) {
							$('div.InformWrapper:first').find('a.Close').click(eval(dismissCallback));
						} else if (dismissCallbackUrl) {
							dismissCallbackUrl = dismissCallbackUrl.replace(/{TransientKey}/g, gdn.definition('TransientKey'));
							var closeAnchor = $('div.InformWrapper:first').find('a.Close');
							closeAnchor.attr('callbackurl', dismissCallbackUrl);
							closeAnchor.click(function () {
								$.ajax({
									type: "POST",
									url: $(this).attr('callbackurl'),
									data: 'TransientKey='+gdn.definition('TransientKey'),
									dataType: 'json',
									error: function(XMLHttpRequest, textStatus, errorThrown) {
										gdn.informMessage(XMLHttpRequest.responseText, 'Dismissable AjaxError');
									},
									success: function(json) {
										gdn.inform(json);
									}
								});
							});
						}
					}
				}
			} catch (e) {
			}
		}
		informMessages.show();
        $(document).trigger('informMessage');
      return true;
   }

	// Send an informMessage to the screen (same arguments as controller.InformMessage).
	gdn.informMessage = function(message, options) {
		if (!options)
			options = new Array();

		if (typeof(options) == 'string') {
			var css = options;
			options = new Array();
			options['CssClass'] = css;
		}
		options['Message'] = message;
		if (!options['CssClass'])
			options['CssClass'] = 'Dismissable AutoDismiss';

		gdn.inform({'InformMessages' : new Array(options)});
	}

   // Inform an error returned from an ajax call.
   gdn.informError = function(xhr, silentAbort) {
      if (xhr == undefined || xhr == null)
         return;

      if (typeof(xhr) == 'string')
         xhr = {responseText: xhr, code: 500};

      var message = xhr.responseText;
      var code = xhr.status;

      if (message == undefined || message == null || message == '') {
         switch (xhr.statusText) {
            case 'error':
               if (silentAbort)
                  return;
               message = 'There was an error performing your request. Please try again.';
               break;
            case 'timeout':
               message = 'Your request timed out. Please try again.';
               break;
            case 'abort':
               return;
         }
      }

      try {
         var data = $.parseJSON(message);
         if (typeof(data.Exception) == 'string')
            message = data.Exception;
      } catch(e) {
      }

      if (message == '')
         message = 'There was an error performing your request. Please try again.';

      gdn.informMessage('<span class="InformSprite Lightbulb Error'+code+'"></span>'+message, 'HasSprite Dismissable');
   }

	// Pick up the inform message stack and display it on page load
	var informMessageStack = gdn.definition('InformMessageStack', false);
	if (informMessageStack) {
		informMessageStack = {'InformMessages' : eval($.base64Decode(informMessageStack))};
		gdn.inform(informMessageStack);
	}

	// Ping for new notifications on pageload, and subsequently every 1 minute.
   var notificationsPinging = 0, pingCount = 0;
	var pingForNotifications = function() {
      if (notificationsPinging > 0 || !gdn.focused)
         return;
      notificationsPinging++;

      $.ajax({
         type: "POST",
         url: gdn.url('dashboard/notifications/inform'),
         data: {'TransientKey': gdn.definition('TransientKey'), 'Path': gdn.definition('Path'), 'DeliveryMethod': 'JSON', 'Count': pingCount++},
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            console.log(XMLHttpRequest.responseText);
         },
         success: function(json) {
            gdn.inform(json);
         },
         complete: function() {
            notificationsPinging--;
         }
      });
	}
   gdn.pingForNotifications = pingForNotifications;

   if (gdn.definition('SignedIn', '0') != '0' && gdn.definition('DoInform', '1') != '0') {
      setTimeout(pingForNotifications, 3000);
      setInterval(pingForNotifications, 60000);
   }

	// Stash something in the user's session (or unstash the value if it was not provided)
	stash = function(name, value, callback) {
		$.ajax({
			type: "POST",
			url: gdn.url('session/stash'),
			data: {'TransientKey' : gdn.definition('TransientKey'), 'Name' : name, 'Value' : value},
			dataType: 'json',
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				gdn.informMessage(XMLHttpRequest.responseText, 'Dismissable AjaxError');
			},
			success: function(json) {
				gdn.inform(json);

                if (typeof(callback) === 'function') {
                    callback();
                } else {
				    return json.Unstash;
                }
			}
		});

		return '';
	}

	// When a stash anchor is clicked, look for inputs with values to stash
	$('a.Stash').click(function(e) {
        // Embedded comments
		var comment = $('#Form_Comment textarea').val(),
        placeholder = $('#Form_Comment textarea').attr('placeholder'),
        vanilla_identifier = gdn.definition('vanilla_identifier');

		if (vanilla_identifier && comment != '' && comment != placeholder) {
            var href = $(this).attr('href');
            e.preventDefault();


			stash('CommentForForeignID_' + vanilla_identifier, comment, function() {
                window.top.location = href;
            });
        }
	});

   String.prototype.addCommas = function() {
      nStr = this;
      x = nStr.split('.');
      x1 = x[0];
      x2 = x.length > 1 ? '.' + x[1] : '';
      var rgx = /(\d+)(\d{3})/;
      while (rgx.test(x1)) {
         x1 = x1.replace(rgx, '$1' + ',' + '$2');
      }
      return x1 + x2;
   }

   Array.prototype.sum = function(){
      for(var i=0,sum=0;i<this.length;sum+=this[i++]);
      return sum;
   }
   Array.prototype.max = function(){
      return Math.max.apply({},this)
   }
   Array.prototype.min = function(){
      return Math.min.apply({},this)
   }

   if (/msie/.test(navigator.userAgent.toLowerCase())) {
      $('body').addClass('MSIE');
   }

   var d = new Date()
   var hourOffset = -Math.round(d.getTimezoneOffset() / 60);

   // Set the ClientHour if there is an input looking for it.
   $('input:hidden[name$=HourOffset]').livequery(function() {
      $(this).val(hourOffset);
   });

   // Ajax/Save the ClientHour if it is different from the value in the db.
   $('input:hidden[id$=SetHourOffset]').livequery(function() {
      if (hourOffset != $(this).val()) {
         $.post(
            gdn.url('/utility/sethouroffset.json'),
            { HourOffset: hourOffset, TransientKey: gdn.definition('TransientKey') }
         );
      }
   });

   // Add "checked" class to item rows if checkboxes are checked within.
   checkItems = function() {
      var container = $(this).parents('.Item');
      if ($(this).prop('checked'))
         $(container).addClass('Checked');
      else
         $(container).removeClass('Checked');
   }
   $('.Item :checkbox').each(checkItems);
   $('.Item :checkbox').change(checkItems);

   // Hide/Reveal the "forgot your password" form if the ForgotPassword button is clicked.
   $(document).delegate('a.ForgotPassword', 'click', function() {
      // Make sure we have both forms
      if ($('#Form_User_SignIn').length) {
         $('.Methods').toggle();
         $('#Form_User_Password').toggle();
		   $('#Form_User_SignIn').toggle();
         return false;
      }
   });

   // Convert date fields to datepickers
   if ($.fn.datepicker) {
      $('input.DatePicker').datepicker({
         showOn: "focus",
         dateFormat: 'mm/dd/yy'
      });
   }

   /**
    * Youtube preview revealing
    *
    */

   // Reveal youtube player when preview clicked.
   function Youtube($container) {
      var $preview = $container.find('.VideoPreview');
      var $player = $container.find('.VideoPlayer');

      $container.addClass('Open').closest('.ImgExt').addClass('Open');

      var width = $preview.width(), height = $preview.height(), videoid = $container.attr('id').replace('youtube-', '');


      var html = '<iframe width="'+width+'" height="'+height+'" src="//www.youtube.com/embed/'+videoid+'?autoplay=1" frameborder="0" allowfullscreen></iframe>';
      $player.html(html);

      $preview.hide();
      $player.show();

      return false;
   }
   $(document).delegate('.Video.YouTube .VideoPreview', 'click', function(e) {
      var $target = $(e.target);
      var $container = $target.closest('.Video.YouTube');
      return Youtube($container);
   });

   /**
    * Twitter card embedding
    *
    */

   if ($('div.twitter-card').length) {
      // Twitter widgets library
      window.twttr = (function (d,s,id) {
         var t, js, fjs = d.getElementsByTagName(s)[0];
         if (d.getElementById(id)) return; js=d.createElement(s); js.id=id;
         js.src="https://platform.twitter.com/widgets.js"; fjs.parentNode.insertBefore(js, fjs);
         return window.twttr || (t = { _e: [], ready: function(f){ t._e.push(f) } });
      }(document, "script", "twitter-wjs"));

      twttr.ready(function(twttr){
         setTimeout(tweets, 300);
      });
   }

   function tweets() {
      $('div.twitter-card').each(function(i, el){
         var card = $(el);
         var tweetUrl = card.attr('data-tweeturl');
         var tweetID = card.attr('data-tweetid');
         var cardref = card.get(0);

         twttr.widgets.createTweet(
            tweetID,
            cardref,
            function(iframe){ card.find('a.tweet-url').remove(); },
            {
               conversation: "none"
            }
         );

      });
   }

   /**
    * GitHub commit embedding
    *
    */

// @tim : 2013-08-24
// Experiment on hold.
//   if ($('div.github-commit').length) {
//      // Github embed library
//      window.GitHubCommit = (function (d,s,id) {
//         var t, js, fjs = d.getElementsByTagName(s)[0];
//         if (d.getElementById(id)) return; js=d.createElement(s); js.id=id;
//         js.src=gdn.url('js/library/github.embed.js'); fjs.parentNode.insertBefore(js, fjs);
//         return window.GitHubCommit || (t = { _e: [], ready: function(f){ t._e.push(f) } });
//      }(document, "script", "github-embd"));
//
//      GitHubCommit.ready(function(GitHubCommit){
//         setTimeout(commits, 300);
//      });
//   }
//
//   function commits(GitHubCommit) {
//      $('div.github-commit').each(function(i, el){
//         var commit = $(el);
//         var commiturl = commit.attr('data-commiturl');
//         var commituser = commit.attr('data-commituser');
//         var commitrepo = commit.attr('data-commitrepo');
//         var commithash = commit.attr('data-commithash');
//         console.log(el);
//      });
//   }

   /**
    * Vine image embedding
    *
    */

   // Automatic, requires no JS

   /**
    * Pintrest pin embedding
    *
    */

   if ($('a.pintrest-pin').length) {
      (function(d){
         var f = d.getElementsByTagName('SCRIPT')[0], p = d.createElement('SCRIPT');
         p.type = 'text/javascript';
         p.async = true;
         p.src = '//assets.pinterest.com/js/pinit.js';
         f.parentNode.insertBefore(p, f);
       }(document));
   }

   /**
    * Textarea autogrow
    *
    */

   if ($.fn.autogrow) {
      $('textarea.Autogrow').livequery(function() {
         $(this).autogrow();
      });
   }

   /**
    * Uses highly modified jquery.atwho.js library. See the note in that
    * file for details about the modifications.
    *
    * This allows @mentions and :emoji: autocomplete. It's also possible to
    * add other types of suggestions.
    *
    * @param node editorElement Pass the textarea or the body within iframe
    * @param node iframe If there's an iframe body getting passed in the
    *                    first param, also pass just the iframe node itself.
    *                    If there's no iframe, pass a blank string or false.
    *
    * @author Dane MacMillan
    */
   gdn.atCompleteInit = function(editorElement, iframe) {

      if (!jQuery.fn.atwho) {
         //console.warn('Editor missing atwho dependency.');
         return false;
      }

      // Added cache results to global, so all instances share the same data
      // and can build the cache together.

      // Cache non-empty server requests
      gdn.atcache = gdn.atcache || {};

      // Cache empty server requests to prevent similarly-started requests
      // from being sent.
      gdn.atempty = gdn.atempty || {};

      // Set minimum characters to type for @mentions to fire
      var min_characters = 2;

      // Max suggestions to show in dropdown.
      var max_suggestions = 5;

      // Server response limit. This should match the limit set in
      // *UserController->TagSearch* and UserModel->TagSearch
      var server_limit = 30;

      // Emoji, set in definition list in foot, by Emoji class. Make sure
      // that class is getting instantiated, otherwise emoji will be empty.
      var emoji_array = gdn.definition('emoji', []);
      var emoji = (emoji_array.length)
         ? $.parseJSON(emoji_array)
         : [];

      // Handle iframe situation
      var iframe_window = (iframe)
         ? iframe.contentWindow
         : '';

      $(editorElement)
         .atwho({
            at: '@',
            tpl: '<li data-value="@${name}" data-id="${id}">${name}</li>',
            limit: max_suggestions,
            callbacks: {

               // Custom data source.
               remote_filter: function(query, callback) {
                  // Do this because of undefined when adding spaces to
                  // matcher callback, as it will be monitoring changes.
                  var query = query || '';

                  // Only all query strings greater than min_characters
                  if (query.length >= min_characters) {

                     // If the cache array contains less than LIMIT 30
                     // (according to server logic), then there's no
                     // point sending another request to server, as there
                     // won't be any more results, as this is the maximum.
                     var filter_more = true;

                     // Remove last character so that the string can be
                     // found in the cache, if exists, then check if its
                     // matching array has less than the server limit of
                     // matches, which means there are no more, so save the
                     // additional server request from being sent.
                     var filter_string = '';

                     // Loop through string and find first closest match in
                     // the cache, and if a match, check if more filtering
                     // is required.
                     for (var i = 0, l = query.length; i < l; i++) {
                        filter_string = query.slice(0, -i);

                        if (gdn.atcache[filter_string]
                        && gdn.atcache[filter_string].length < server_limit) {
                           //console.log('no more filtering for "'+ query + '" as its parent filter array, "'+ filter_string +'" is not maxed out.');

                           // Add this other query to empty array, so that it
                           // will not fire off another request.
                           gdn.atempty[query] = query;

                           // Do not filter more, meaning, do not send
                           // another server request, as all the necessary
                           // data is already in memory.
                           filter_more = false;
                           break;
                        }
                     }

                     // Check if query would be empty, based on previously
                     // cached empty results. Compare against the start of
                     // the latest query string.
                     var empty_query = false;

                     // Loop through cache of empty query strings.
                     for (key in gdn.atempty) {
                        if (gdn.atempty.hasOwnProperty(key)) {
                           // See if cached empty results match the start
                           // of the latest query. If so, then no point
                           // sending new request, as it will return empty.
                           if (query.match(new RegExp('^'+ key +'+')) !== null) {
                              empty_query = true;
                              break;
                           }
                        }
                     }

                     // Produce the suggestions based on data either
                     // cached or retrieved.
                     if (filter_more && !empty_query  && !gdn.atcache[query]) {
                        $.getJSON('/user/tagsearch', {"q": query, "limit": server_limit}, function(data) {
                           callback(data);

                           // If data is empty, cache the results to prevent
                           // other requests against similarly-started
                           // query strings.
                           if (data.length) {
                              gdn.atcache[query] = data;
                           } else {
                              gdn.atempty[query] = query;
                           }
                        });
                     } else {
                        // If no point filtering more as the parent filter
                        // has not been maxed out with responses, use the
                        // closest parent filter instead of the latest
                        // query string.
                        if (!filter_more) {
                           callback(gdn.atcache[filter_string]);
                        } else {
                           callback(gdn.atcache[query]);
                        }
                     }
                  }
               },

               // Note, in contenteditable mode (iframe for us), the value
               // is surrounded by span tags.
               before_insert: function(value, $li) {

                  // It's better to use the value provided, as it may have
                  // html tags around it, depending on mode. Using the
                  // regular expression avoids the need to check what mode
                  // the suggestion is made in, and then constructing
                  // it based on that. Optional assignment for undefined
                  // matcher callback results.
                  var username = $li.data('value') || '';
                  // Pop off the flag--usually @ or :
                  username = username.slice(1, username.length);

                  // Check if there are any whitespaces, and if so, add
                  // quotation marks around the whole name.
                  var requires_quotation = (/\s/g.test(username))
                     ? true
                     : false;

                  // Check if there are already quotation marks around
                  // the string--double or single.
                  var has_quotation = (/(\"|\')(.+)(\"|\')/g.test(username))
                     ? true
                     : false;

                  var insert = username;

                  if (requires_quotation && !has_quotation) {
                     // Do not even need to have value wrapped in
                     // any tags at all. It will be done automatically.
                     //insert = value.replace(/(.*\>?)@([\w\d\s\-\+\_]+)(\<?.*)/, '$1@"$2"$3');
                     insert = '"' + username + '"';
                  }

                  // This is needed for checking quotation mark directly
                  // after at character, and preventing another at character
                  // from being inserted into the page.
                  var raw_at_match = this.raw_at_match || '';

                  var at_quote = (/.?@(\"|\')/.test(raw_at_match))
                     ? true
                     : false;

                  // If at_quote is false, then insert the at character,
                  // otherwise it means the user typed a quotation mark
                  // directly after the at character, which, would get
                  // inserted again if not checked. at_quote would
                  // be false most of the time; the exception is when
                  // it's true.
                  if (!at_quote) {
                     insert = this.at + insert;
                  }

                  // Keep for reference, but also, spaces add complexity,
                  // so use zero-width non-joiner delimiting those advanced
                  // username mentions.
                  var hidden_unicode_chars = {
                     zws:  '\u200b',
                     zwnj: '\u200C',
                     nbsp: '\u00A0' // \xA0
                  };

                  // The last character prevents the matcher from trigger
                  // on nearly everything.
                  return insert + hidden_unicode_chars.zwnj;
               },

               // Custom highlighting to accept spaces in names. This is
               // almost a copy of the default in the library, with tweaks
               // in the regex.
               highlighter: function(li, query) {
                  var regexp;
                  if (!query) {
                     return li;
                  }
                  regexp = new RegExp(">\\s*(\\w*)(" + query.replace("+", "\\+") + ")(\\w*)\\s*(\\s+.+)?<", 'ig');
                  // Capture group 4 for possible spaces
                  return li.replace(regexp, function(str, $1, $2, $3, $4) {
                     // Weird Chrome behaviour, so check for undefined, then
                     // set to empty string if so.
                     if (typeof $3 == 'undefined') {
                        $3 = '';
                     }
                     if (typeof $4 == 'undefined') {
                        $4 = '';
                     }

                     return '> ' + $1 + '<strong>' + $2 + '</strong>' + $3 + $4 + ' <';
                  });
               },

               // Custom matching to allow quotation marks in the matching
               // string as well as spaces. Spaces make things more
               // complicated.
               matcher: function(flag, subtext, should_start_with_space) {
                  var match, regexp;
                  flag = flag.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");

                  if (should_start_with_space) {
                     flag = '(?:^|\\s)' + flag;
                  }

                  // Note: adding whitespace to regex makes the query in
                  // remote_filter and before_insert callbacks throw
                  // undefined when not accounted for, so optional
                  // assigments added to each.
                  //regexp = new RegExp(flag + '([A-Za-z0-9_\+\-]*)$|' + flag + '([^\\x00-\\xff]*)$', 'gi');
                  // Note: this does make the searching a bit more loose,
                  // but it's the only way, as spaces make searching
                  // more ambiguous.
                  // \xA0 non-breaking space
                  regexp = new RegExp(flag + '\"?([\\sA-Za-z0-9_\+\-]*)\"?$|' + flag + '\"?([^\\x00-\\xff]*)\"?$', 'gi');

                  match = regexp.exec(subtext);
                  if (match) {
                     // Store the original matching string to check against
                     // quotation marks after the at symbol, to prevent
                     // double insertions of the at symbol. This will be
                     // used in the before_insert callback.
                     this.raw_at_match = match[0];

                     return match[2] || match[1];
                  } else {
                     return null;
                  }
               }
            },
            display_timeout: 0,
            cWindow: iframe_window
         })
         .atwho({
            at: ':',
            tpl: '<li data-value=":${name}:" class="at-suggest-emoji"><img src="${url}" width="20" height="20" alt=":${name}:" class="emoji-img" /> <span class="emoji-name">${name}</span></li>',
            limit: max_suggestions,
            data: emoji,
            cWindow: iframe_window
         });

      // http://stackoverflow.com/questions/118241/calculate-text-width-with-javascript
      String.prototype.width = function(font) {
         var f = font || "15px 'lucida grande','Lucida Sans Unicode',tahoma,sans-serif'";
         o = $('<div>' + this + '</div>')
            .css({'position': 'absolute', 'float': 'left', 'white-space': 'nowrap', 'visibility': 'hidden', 'font': f})
            .appendTo($('body')),
         w = o.width();
         o.remove();
         return w;
      }

      // Only necessary for iframe.
      // Based on work here: https://github.com/ichord/At.js/issues/124
      if (iframe_window) {
         // This hook is triggered when atWho places a selection list in the
         // window. The context is passed implicitly when triggered by at.js.
         $(iframe_window).on("reposition.atwho", function(e, offset, context) {

            // Actual suggestion box that will appear.
            var suggest_el = context.view.$el;

            // The area where text will be typed (contenteditable body).
            var $inputor = context.$inputor;

            // Display it below the text.
            var line_height = parseInt($inputor.css('line-height'));

            // offset contains the top left values of the offset to the iframe
            // we need to convert that to main window coordinates
            var oIframe = $(iframe).offset(),
               iLeft = oIframe.left + offset.left,
               iTop = oIframe.top,
               select_height = 0;

            // In wysiwyg mode, the suggestbox follows the typing, which
            // does not happen in regular mode, so adjust it.
            // Either @ or : for now.
            var at = context.at;
            var text = context.query.text;
            var font_mirror = $('.BodyBox');
            var font = font_mirror.css('font-size') + ' ' + font_mirror.css('font-family');

            // Get font width
            var font_width = (at+text).width(font) - 2;

            if (at == '@') {
               iLeft -= font_width;
            }

            if (at == ':') {
               iLeft -= 2;
            }

            // atWho adds 3 select areas, presumably for differnet positing on screen (above below etc)
            // This finds the active one and gets the container height
            $(suggest_el).each(function(i, el) {
               if ($(this).outerHeight() > 0) {
                  select_height += $(this).height() + line_height;
               }
            });

            // Now should we show the selection box above or below?
            var iWindowHeight = $(window).height(),
               iDocViewTop = $(window).scrollTop(),
               iSelectionPosition = iTop + offset.top - $(window).scrollTop(),
               iAvailableSpace = iWindowHeight - (iSelectionPosition - iDocViewTop);

            if (iAvailableSpace >= select_height) {
               // Enough space below
               iTop = iTop + offset.top + select_height - $(window).scrollTop();
            }
            else {
               // Place it above instead
               // @todo should check if this is more space than below
               iTop= iTop + offset.top - $(window).scrollTop();
            }

            // Move the select box
            offset = {left: iLeft, top: iTop};
            $(suggest_el).offset(offset);
         });
      }
   };
   // Now call atCompleteInit on all .BodyBox elements. The advanced editor
   // calls this function directly when in wysiwyg format, as it needs to
   // handle an iframe, and the editor instance needs to be referenced.
   if ($.fn.atwho && gdn.atCompleteInit) {
      $('.BodyBox').livequery(function() {
         gdn.atCompleteInit(this, '');
      });
   }

});

// Shrink large images to fit into message space, and pop into new window when clicked.
// This needs to happen in onload because otherwise the image sizes are not yet known.
jQuery(window).load(function() {
   /*
   Adds .naturalWidth() and .naturalHeight() methods to jQuery for retreaving a
   normalized naturalWidth and naturalHeight.
   // Example usage:
   var
   nWidth = $('img#example').naturalWidth(),
   nHeight = $('img#example').naturalHeight();
   */

   (function($){
      var
      props = ['Width', 'Height'],
      prop;

      while (prop = props.pop()) {
         (function (natural, prop) {
            $.fn[natural] = (natural in new Image()) ?
            function () {
               return this[0][natural];
            } :
            function () {
               var
                  node = this[0],
                  img,
                  value;

               if (node.tagName.toLowerCase() === 'img') {
                  img = new Image();
                  img.src = node.src,
                  value = img[prop];
               }
               return value;
            };
         }('natural' + prop, prop.toLowerCase()));
      }
   }(jQuery));

   jQuery('div.Message img').each(function(i,img) {
      var img = jQuery(img);
      var container = img.closest('div.Message');
      if (img.naturalWidth() > container.width() && container.width() > 0) {
         img.after('<div class="ImageResized">' + gdn.definition('ImageResized', 'This image has been resized to fit in the page. Click to enlarge.') + '</div>');
         img.wrap('<a href="'+$(img).attr('src')+'"></a>');
      }
   });

   // Let the world know we're done here
   jQuery(window).trigger('ImagesResized');
});

if(typeof String.prototype.trim !== 'function') {
  String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g, '');
  }
}
