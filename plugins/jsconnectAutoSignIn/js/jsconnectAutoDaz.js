;
if ( ! daz) { var daz = { }; }

(function($, window, document) {

	"use strict";

	if ( ! daz.forum) { daz.forum = { }; }

	daz.forum.logout = function( ) {
		"use strict";

		window.location = gdn.url('/entry/signout');
	};

	daz.forum.process_sso = function(myCall, data, isCache) {
		"use strict";

		var action = '',
			target;

		if (data.hasOwnProperty('verified')) {
			data['verified'] = data['verified'] ? 1 : 0;
		}

		if (data['error']) {
			action = gdn.url('/entry/jsconnect/error');
		}
		else {
			if ( ! data['name']) {
				//data = {'error': 'unauthorized', 'message': 'You are not signed in.' };
				//action = gdn.url('/entry/jsconnect/guest');

				// still logged into vanilla?
				if (magento_id) {
					// nope
					daz.forum.logout( );
				}

				return;
			}
			else {
				if (magento_id === parseInt(data['uniqueid'], 10)) {
					// check the current data against the new data
					var update = false;
					var cur_data = gdn.definition('JsConnectData');
					for (var idx in cur_data) {
						if ('roles' === idx) {
							var new_roles = data['roles'].split(',').sort().join(',').toLowerCase();
							var old_roles = cur_data['roles'].split(',').sort().join(',').toLowerCase();

							if (new_roles !== old_roles) {
								update = true;
								break;
							}
						}
						else if (cur_data[idx] != data[idx]) {
							update = true;
							break;
						}
					}

					if ( ! update) {
						// no need to do anything
						return;
					}
				}
				else if (magento_id) {
					// if this is reached, the wrong person is logged in
					daz.forum.logout( );
					return;
				}

				for (var key in data) {
					if (data[key] == null) {
						data[key] = '';
					}
				}

				target = location.search.match(/Target=([^&]+)/);
				action = gdn.url('/entry/connect/jsconnect?client_id=' + data['client_id'] + (target ? '&' + target[0] : ''));
			}
		}

		var smokescreen = $(
			'<div id="smokescreen-panel" class="Popup">' +
				'<div class="Border">' +
					'<div id="smokescreen-panel-box" class="Body">' +
					'</div>' +
				'</div>' +
			'</div>' +
			'<div id="smokescreen"> </div>'
		);

		$(document.body).append(smokescreen);

		$('#smokescreen-panel-box').append('<h1 style="text-align: center;">' + gdn.definition('Connecting') + '</h1>');
		$('#smokescreen-panel-box').append(('<p class="Message">' + gdn.definition('ConnectingUser') + '</p>').replace(/%/, $(this).children('.Username').text()));
		$('#smokescreen-panel-box').append('<div class="Progress"></div><br />');

		$("#smokescreen, #smokescreen-panel").show( );
		setTimeout(function ( ) { $("#smokescreen, #smokescreen-panel").hide( ); }, 1000 * 60);

		var jsConnectForm = $('<form>').attr({
			'id': 'jsConnectAuto',
			'method': 'post',
			//'style':'display:none;',
			'action': action
		});

		jsConnectForm.append($('<input type="hidden" name="Form/JsConnect" />').val($.param(data)));
		jsConnectForm.append($('<input type="hidden" name="Form/Target" />').val(document.location.toString()));
		jsConnectForm.append($('<input type="hidden" name="Form/TransientKey" />').val(gdn.definition('TransientKey')));
		jsConnectForm.find('input').each(function ( ) {
			if ($(this).attr('name').match(/^Form\//) != -1) {
				jsConnectForm.append($('<input type="hidden" name="' + $(this).attr('name').replace(/^Form\//, '') + '" />').val($(this).val( )));
			}
		});

		$(document.body).append(jsConnectForm);
		$('#jsConnectAuto').submit( );
	};

}(jQuery, window, document));
