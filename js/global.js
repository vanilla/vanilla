// This file contains javascript that is global to the entire Garden application
jQuery(document).ready(function($) {
   if ($.browser.msie) {
      $('body').addClass('MSIE');
   }
   
   var d = new Date();
   var clientDate = d.getFullYear()+'-'+(d.getMonth() + 1)+'-'+d.getDate()+' '+d.getHours()+':'+d.getMinutes();

   // Set the ClientHour if there is an input looking for it.
   $('input:hidden[name$=ClientHour]').livequery(function() {
      $(this).val(clientDate);
   });

   // Ajax/Save the ClientHour if it is different from the value in the db.
   $('input:hidden[id$=SetClientHour]').livequery(function() {
      if (d.getHours() != $(this).val()) {
         $.get(
            gdn.url('/utility/setclienthour'),
            {'ClientDate': clientDate, 'TransientKey': gdn.definition('TransientKey'), 'DeliveryType': 'BOOL'}
         );
      }
   });
   
   // Hide/Reveal the "forgot your password" form if the ForgotPassword button is clicked.
   $('a.ForgotPassword').live('click', function() {
      $('.Methods').toggle();
      $('#Form_User_Password').toggle();
		$('#Form_User_SignIn').toggle();
      return false;
   });
   
   if ($.fn.autogrow)
      $('textarea.Autogrow').livequery(function() {
         $(this).autogrow();
      });
      
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
		
	gdn = { focused: true };
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
   
   gdn.disable = function(e) {
      var href = $(e).attr('href');
      if (href) {
         $.data(e, 'hrefBak', href);
      }
      $(e).addClass('InProgress').removeAttr('href').attr('disabled', true);
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

   // Go to notifications if clicking on a user's notification count
   $('li.UserNotifications a span').click(function() {
      document.location = gdn.url('/profile/notifications');
      return false;
   });
   
   // This turns any anchor with the "Popup" class into an in-page pop-up (the
   // view of the requested in-garden link will be displayed in a popup on the
   // current screen).
   if ($.fn.popup) {
      $('a.Popup').popup();
		$('a.PopConfirm').popup({'confirm' : true, 'followConfirm' : true});
   }

   $(".PopupWindow").live('click', function() {
      var $this = $(this);
      
      if ($this.hasClass('NoMSIE') && $.browser.misie) {
         return;
      }

      var width = $this.attr('popupWidth'); width = width ? width : 960;
      var height = $this.attr('popupHeight'); height = height ? height : 600;
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
      $('.PopupClose').live('click', function(event){
         var Popup = $(event.target).parents('.Popup');
         if (Popup.length) {
            var PopupID = Popup.prop('id');
            $.popup.close({popupId: PopupID});
         }
      });

   // Make sure that message dismissalls are ajax'd
   $('a.Dismiss').live('click', function() {
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

   // Format email addresses
   $('span.Email.EmailUnformatted').livequery(function() {
      var el = $(this);
      el.removeClass('EmailUnformatted');
	  var email = $(this).html().replace(/<em[^>]*>dot<\/em>/ig, '.').replace(/<strong[^>]*>at<\/strong>/ig, '@');
      el.html('<a href="mailto:' + email + '">' + email + '</a>');
   });

   // Make sure that the commentbox & aboutbox do not allow more than 1000 characters
   $.fn.setMaxChars = function(iMaxChars) {
      $(this).live('keyup', function() {
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

   gdn.processTargets = function(targets) {
      if(!targets || !targets.length)
         return;
      for(i = 0; i < targets.length; i++) {
         var item = targets[i];
         $target = $(item.Target);
         switch(item.Type) {
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
            case 'Prepend':
               $target.prepend(item.Data);
               break;
            case 'Redirect':
               window.location.replace(item.Data);
               break;
            case 'Remove':
               $target.remove();
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

      var urlFormat = gdn.definition("UrlFormat", "");
      
      if (path.substr(0, 1) == "/")
         path = path.substr(1);

      if (urlFormat.indexOf("?") >= 0)
         path = path.replace("?", "&");

      return urlFormat.replace("{Path}", path);
   };

   // Fill in placeholders.
   if (!gdn.elementSupports('input', 'placeholder')) {
      $('input:text').each(function() {
         var $this = $(this);
         var placeholder = $this.attr('placeholder');
         
         if (!$this.val() && placeholder) {
            $this.val(placeholder);
            $this.blur(function() {
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
               $elem.removeClass('Progress TinyProgress');
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
      var href = $elem.attr('href');
      if (!href)
         return;
      gdn.disable(this);
      

      $.ajax({
         type: "POST",
         url: href,
         data: {DeliveryType: 'VIEW', 'DeliveryMethod': 'JSON'},
         dataType: 'json',
         complete: function() {
            gdn.enable(this);
            $elem.removeClass('InProgress');
            $elem.attr('href', href);
         },
         error: function(xhr) {
            gdn.informError(xhr);
         },
         success: function(json) {
            if (json == null) json = {};
            
            var informed = gdn.inform(json);
            gdn.processTargets(json.Targets);
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
   $('.Hijack').live('click', hijackClick);

   $.fn.openToggler = function() {
      var lastOpen = null;
      
     $(this).click(function() {
        var $flyout = $('.Flyout', this);

        // Dynamically fill the flyout.
        var rel = $(this).attr('rel');
        if (rel) {
           $(this).attr('rel', '');
           $flyout.addClass('Progress');
            $.ajax({
               url: gdn.url(rel),
               data: {DeliveryType: 'VIEW'},
               success: function(data) {
                  $flyout.html(data);
               },
               complete: function() {
                  $flyout.removeClass('Progress');
               }
            });
        }

        if ($flyout.css('display') == 'none') {
           if (lastOpen != null) {
              $('.Flyout', lastOpen).hide();
              $(lastOpen).removeClass('Open');
           }
           
           $(this).addClass('Open')
           $flyout.show();
           
           lastOpen = this;
        } else {
           $flyout.hide()
           $(this).removeClass('Open');
        }
     });
   }
   $('.ToggleFlyout').openToggler();
   
   // Add a spinner onclick of buttons with this class
   $('input.SpinOnClick').live('click', function() {
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
         window.location.hash = gdn.definition('LocationHash');
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
      jQuery.ajax({
         dataType: 'json',
         type: 'post',
         url: StatsURL,
         data: {'TransientKey': gdn.definition('TransientKey'), 'Path': gdn.definition('Path')},
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
   $('div.InformWrapper.Dismissable a.Close').live('click', function() {
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
	$('div.InformWrapper').live('mouseover mouseout', function(e) {
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
      
      gdn.informMessage('<span class="InformSprite SkullBones Error'+code+'"></span>'+message, 'HasSprite Dismissable AutoDismiss');
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
   
   if (gdn.definition('SignedIn', '0') != '0') {
      setTimeout(pingForNotifications, 3000);
      setInterval(pingForNotifications, 60000);
   }
	
	// Stash something in the user's session (or unstash the value if it was not provided)
	stash = function(name, value) {
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
				return json.Unstash;
			}
		});
		
		return '';
	}
	
	// When a stash anchor is clicked, look for inputs with values to stash
	$('a.Stash').click(function() {
		var comment = $('#Form_Comment textarea').val();
		if (comment != '')
			stash('CommentForDiscussionID_' + gdn.definition('DiscussionID'), comment);
	});
});

// Shrink large images to fit into message space, and pop into new window when clicked.
// This needs to happen in onload because otherwise the image sizes are not yet known.
jQuery(window).load(function() {
   
   var toggler = function(t_img, t_width) {
      if (parseInt(t_img.css('width'), 10) > t_width)
         t_img.css('width',t_width);
      else
         t_img.css('width','auto');
      return false;
   }
   
   jQuery('div.Message img').each(function(i,img) {
      var img = jQuery(img);
      var container = img.parents('div.Message');
      if (img.width() > container.width()) {
         var smwidth = container.width();
         
         img.css('width', smwidth).css('cursor', 'pointer');
         img.after('<div class="ImageResized">' + gdn.definition('ImageResized', 'This image has been resized to fit in the page. Click to enlarge.') + '</div>');
         
         img.next().click(function() {
            return toggler(img, smwidth);
         });
         img.click(function() {
            return toggler(img, smwidth);
         })
      }
   });
});