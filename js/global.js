
// This file contains javascript that is global to the entire Garden application
jQuery(document).ready(function($) {
   
   // Set the ClientHour if there is an input looking for it.
   $('input:hidden[name$=ClientHour]').livequery(function() {
      var d = new Date();
      $(this).val(d.getHours());
   });

   // Ajax/Save the ClientHour if it is different from the value in the db.
   $('input:hidden[id$=SetClientHour]').livequery(function() {
      var d = new Date();
      if (d.getHours() != $(this).val()) {
         $.post(
            gdn.url('/utility/setclienthour/' + d.getHours() + '/' + gdn.definition('TransientKey')),
            'DeliveryType=BOOL'
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
		
	gdn = { };

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
         if (typeof def == 'undefined' || def == '')
            def = defaultVal;
      }
         
      return def;
   }

   // Main Menu dropdowns
	/*
   if ($.fn.menu)
      $('#Menu').menu({
         showDelay: 0,
         hideDelay: 0
      });
	*/
	
   // Go to notifications if clicking on a user's notification count
   $('li.UserNotifications a span').click(function() {
      document.location = gdn.url('/profile/notifications');
      return false;
   });
   
   // This turns any anchor with the "Popup" class into an in-page pop-up (the
   // view of the requested in-garden link will be displayed in a popup on the
   // current screen).
   if ($.fn.popup)
      $('a.Popup').popup();

   $(".PopupWindow").live('click', function() {
      var $this = $(this);

      var width = $this.attr('popupWidth');
      var height = $this.attr('popupHeight');
      var left = (screen.width - width) / 2;
      var top = (screen.height - height) / 2;

      var id = $this.attr('id');
      var href = $this.attr('href');
      if ($this.attr('popupHref'))
         href = $this.attr('popupHref');

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

   // Make sure that message dismissalls are ajax'd
   $('a.Dismiss').live('click', function() {
      var anchor = this;
      var container = $(anchor).parent();
      var transientKey = gdn.definition('TransientKey');
      var data = 'DeliveryType=BOOL&TransientKey=' + transientKey;
      $.post($(anchor).attr('href'), data, function(response) {
         if (response == 'TRUE')
            $(container).slideUp('fast',function() {
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
   
   // If a message group is clicked, hide it.
   $('div.Messages').live('click', function() {
      $(this).fadeOut('fast', function() {
         $(this).remove();
      });
   });
   
   // If an information message appears on the screen, hide it after a few moments.
   $('div.Information').livequery(function() {
      setTimeout(function(){
         $('div.Information').fadeOut('fast', function() {
            $(this).remove();
         });
      }, 3000);
   });
	
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
         setTimeout("document.location = '"+RedirectUrl+"';", 2000);
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

   // Notify the user with a message
   gdn.inform = function(message, wrapInfo) {
      if(wrapInfo == undefined) {
         wrapInfo = true;
      }
      
      if (message && message != null && message != '') {
         $('div.Messages').remove();
         if(wrapInfo)
            $('<div class="Messages Information"><ul><li>' + message + '</li></ul></div>').appendTo('body').show();
         else
            $(message).appendTo('body').show();
      }
   };
   
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
            case 'Text':
               $target.text(item.Data);
               break;
            case 'Html':
               $target.html(item.Data);
         }
      }
   };

   gdn.url = function(path) {
      if (path.indexOf("//") >= 0)
         return path; // this is an absolute path.

      var urlFormat = gdn.definition("UrlFormat", "");

      if (path[0] == "/")
         path = path.substr(1);

      if (urlFormat.indexOf("?") >= 0)
         path = path.replace("?", "&");

      var result = urlFormat.replace("{Path}", path);
      return result;
   };

   // Fill the search input with "search" if empty and blurred
   var searchText = gdn.definition('Search', 'Search');
   $('#Search input.InputBox').val(searchText);
   $('#Search input.InputBox').blur(function() {
      var searchText = gdn.definition('Search', 'Search');
      if (typeof $(this).val() == 'undefined' || $(this).val() == '')
         $(this).val(searchText);
   });
   $('#Search input.InputBox').focus(function() {
      var searchText = gdn.definition('Search', 'Search');
      if ($(this).val() == searchText)
         $(this).val('');
   });
   
   // Add a spinner onclick of buttons with this class
   $('input.SpinOnClick').live('click', function() {
      $(this).before('<span class="AfterButtonLoading">&nbsp;</span>').removeClass('SpinOnClick');
   });
   
   // Confirmation for item removals
   $('a.RemoveItem').click(function() {
      if (!confirm('Are you sure you would like to remove this item?')) {
         return false;
      }
   });
	
	// Shrink large images to fit into message space, and pop into new window when clicked.
	$('div.Message img').livequery(function() {
		var img = $(this);
		var container = img.parents('div.Message');
		if (img.width() > container.width()) {
			img.css('width', container.width()).css('cursor', 'pointer');
			img.after('<div class="ImageResized">' + gdn.definition('ImageResized', 'This image has been resized to fit in the page. Click to enlarge.') + '</div>');
			img.next().click(function() {
				window.open($(img).attr('src'));
				return false;
			});
			img.click(function() {
				window.open($(this).attr('src'));
				return false;
			})
		}
	}); 
   
});

	
/**
 * jQuery BASE64 functions
 * 
 * 	<code>
 * 		Encodes the given data with base64. 
 * 		String $.base64Encode ( String str )
 *		<br />
 * 		Decodes a base64 encoded data.
 * 		String $.base64Decode ( String str )
 * 	</code>
 * 
 * Encodes and Decodes the given data in base64.
 * This encoding is designed to make binary data survive transport through transport layers that are not 8-bit clean, such as mail bodies.
 * Base64-encoded data takes about 33% more space than the original data. 
 * This javascript code is used to encode / decode data using base64 (this encoding is designed to make binary data survive transport through transport layers that are not 8-bit clean). Script is fully compatible with UTF-8 encoding. You can use base64 encoded data as simple encryption mechanism.
 * If you plan using UTF-8 encoding in your project don't forget to set the page encoding to UTF-8 (Content-Type meta tag). 
 * This function orginally get from the WebToolkit and rewrite for using as the jQuery plugin.
 * 
 * Example
 * 	Code
 * 		<code>
 * 			$.base64Encode("I'm Persian."); 
 * 		</code>
 * 	Result
 * 		<code>
 * 			"SSdtIFBlcnNpYW4u"
 * 		</code>
 * 	Code
 * 		<code>
 * 			$.base64Decode("SSdtIFBlcnNpYW4u");
 * 		</code>
 * 	Result
 * 		<code>
 * 			"I'm Persian."
 * 		</code>
 * 
 * @alias Muhammad Hussein Fattahizadeh < muhammad [AT] semnanweb [DOT] com >
 * @link http://www.semnanweb.com/jquery-plugin/base64.html
 * @see http://www.webtoolkit.info/
 * @license http://www.gnu.org/licenses/gpl.html [GNU General Public License]
 * @param {jQuery} {base64Encode:function(input))
 * @param {jQuery} {base64Decode:function(input))
 * @return string
 */

(function($){
	
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
})(jQuery);
