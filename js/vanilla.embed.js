(function(window) {

var 
   Vanilla = function() { },
   
   // Save a reference to some core methods
//	core_push = Array.prototype.push,
//	core_slice = Array.prototype.slice,
//	core_indexOf = Array.prototype.indexOf,
	core_toString = Object.prototype.toString,
	core_hasOwn = Object.prototype.hasOwnProperty,
//	core_trim = String.prototype.trim,
   
   // [[Class]] -> type pairs
	class2type = {};
   
   
Vanilla.fn = Vanilla.prototype;

// Merge the contents of two or more objects together into the first object. (from jQuery)
Vanilla.extend = Vanilla.fn.extend = function() {
	var options, name, src, copy, copyIsArray, clone,
		target = arguments[0] || {},
		i = 1,
		length = arguments.length,
		deep = false;

	// Handle a deep copy situation
	if ( typeof target === "boolean" ) {
		deep = target;
		target = arguments[1] || {};
		// skip the boolean and the target
		i = 2;
	}

	// Handle case when target is a string or something (possible in deep copy)
	if ( typeof target !== "object" && !Vanilla.isFunction(target) ) {
		target = {};
	}

	// extend jQuery itself if only one argument is passed
	if ( length === i ) {
		target = this;
		--i;
	}

	for ( ; i < length; i++ ) {
		// Only deal with non-null/undefined values
		if ( (options = arguments[ i ]) != null ) {
			// Extend the base object
			for ( name in options ) {
				src = target[ name ];
				copy = options[ name ];

				// Prevent never-ending loop
				if ( target === copy ) {
					continue;
				}

				// Recurse if we're merging plain objects or arrays
				if ( deep && copy && ( Vanilla.isPlainObject(copy) || (copyIsArray = Vanilla.isArray(copy)) ) ) {
					if ( copyIsArray ) {
						copyIsArray = false;
						clone = src && Vanilla.isArray(src) ? src : [];

					} else {
						clone = src && Vanilla.isPlainObject(src) ? src : {};
					}

					// Never move original objects, clone them
					target[ name ] = Vanilla.extend( deep, clone, copy );

				// Don't bring in undefined values
				} else if ( copy !== undefined ) {
					target[ name ] = copy;
				}
			}
		}
	}

	// Return the modified object
	return target;
};

Vanilla.extend({
	// See test/unit/core.js for details concerning isFunction.
	// Since version 1.3, DOM methods and functions like alert
	// aren't supported. They return false on IE (#2968).
	isFunction: function( obj ) {
		return Vanilla.type(obj) === "function";
	},

	isArray: Array.isArray || function( obj ) {
		return Vanilla.type(obj) === "array";
	},

	isWindow: function( obj ) {
		return obj != null && obj == obj.window;
	},

	isNumeric: function( obj ) {
		return !isNaN( parseFloat(obj) ) && isFinite( obj );
	},

	type: function( obj ) {
		return obj == null ?
			String( obj ) :
			class2type[ core_toString.call(obj) ] || "object";
	},

	isPlainObject: function( obj ) {
		// Must be an Object.
		// Because of IE, we also have to check the presence of the constructor property.
		// Make sure that DOM nodes and window objects don't pass through, as well
		if ( !obj || Vanilla.type(obj) !== "object" || obj.nodeType || Vanilla.isWindow( obj ) ) {
			return false;
		}

		try {
			// Not own constructor property must be Object
			if ( obj.constructor &&
				!core_hasOwn.call(obj, "constructor") &&
				!core_hasOwn.call(obj.constructor.prototype, "isPrototypeOf") ) {
				return false;
			}
		} catch ( e ) {
			// IE8,9 Will throw exceptions on certain host objects #9897
			return false;
		}

		// Own properties are enumerated firstly, so to speed up,
		// if last one is own, then all properties are own.

		var key;
		for ( key in obj ) {}

		return key === undefined || core_hasOwn.call( obj, key );
	},

	isEmptyObject: function( obj ) {
		var name;
		for ( name in obj ) {
			return false;
		}
		return true;
	},
   
   error: function( msg ) {
		throw new Error( msg );
	},
   
   // args is for internal usage only
	each: function( obj, callback, args ) {
		var name,
			i = 0,
			length = obj.length,
			isObj = length === undefined || Vanilla.isFunction( obj );

		if ( args ) {
			if ( isObj ) {
				for ( name in obj ) {
					if ( callback.apply( obj[ name ], args ) === false ) {
						break;
					}
				}
			} else {
				for ( ; i < length; ) {
					if ( callback.apply( obj[ i++ ], args ) === false ) {
						break;
					}
				}
			}

		// A special, fast, case for the most common use of each
		} else {
			if ( isObj ) {
				for ( name in obj ) {
					if ( callback.call( obj[ name ], name, obj[ name ] ) === false ) {
						break;
					}
				}
			} else {
				for ( ; i < length; ) {
					if ( callback.call( obj[ i ], i, obj[ i++ ] ) === false ) {
						break;
					}
				}
			}
		}

		return obj;
	},
   
   slash: function(str) {
      if (str.substring(0, 1) != '/')
         return '/'+str;
      return str;
   }
});

// Populate the class2type map
Vanilla.each("Boolean Number String Function Array Date RegExp Object".split(" "), function(i, name) {
	class2type[ "[object " + name + "]" ] = name.toLowerCase();
});

var embed = function(options) {
   var me = this == Vanilla ? Vanilla.embed : this;
   
   Vanilla.extend(
      me,
      {
         initialPath: '/',
         autoStart: true
      },
      options);
      
   me.isReady = false;
      
   if (me.container && ((typeof me.container) == "string")) {
      var container = document.getElementById(me.container);
      if (!container)
         Vanilla.error("Could not find element #"+container);
      me.container = container;
   }
   
   // TODO: ensure easyXDM.
   
   if (me.autoStart)
      me.start();
   
};

// Generates a random ID for use as a callback id
var generateCbid = function() {
	var genRand = function(){
		return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
	};
	return genRand() + genRand();
};

// Object containing callbacks
var callbacks = {};

embed.fn = embed.prototype;

embed.callRemote = embed.fn.callRemote = function(func, args, callback) {
   var options = { func: func, args: args };
   
   if (callback) {
      options.callbackID = generateCbid();
      callbacks[options.callbackID] = callback;
   }
   
   this.socket.postMessage(JSON.stringify(options));
};

embed.callback = embed.fn.callRemoteCallback = function(callbackID, args) {
   if (callbacks[callbackID] == undefined) {
      Vanilla.error("Unkown callback ID: "+callbackID);
   }
   
   args = args || [];
   if (!Vanilla.isArray(args))
      args = [args];
   callbacks[callbackID].apply(this, args)
   
   delete callbacks[callbackID];
}

embed.height = embed.fn.height = function(height) {
   this.iframe.height = height;
   this.iframe.style.height = height+'px';
}

embed.notifyLocation = embed.fn.notifyLocation = function(path) {
   // Check to see if we really need to update the hash.
   var currentLocation = Vanilla.slash(window.location.hash.substr(1));
   
   if (path != currentLocation) {
      if (path != this.initialPath)
         window.location.hash = path;
      else
         window.location.hash = '';
   }
}

embed.onMessage = embed.fn.onMessage = function(message, origin) {
   var me = this,
      data = JSON.parse(message);
  
   var func = this[data.func];
   if (!Vanilla.isFunction(func))
      Vanilla.error(data.func+' needs to be added to Vanilla.embed.');
   
   data.args = data.args || [];
   if (!Vanilla.isArray(data.args))
      data.args = [data.args];
   
   if (data.func == 'notifyLocation') {
      // Strip the root from the location.
      // Just doing this here so that it's easier for people that override the embed.
      
      var path = data.args[0];
      
      if (path.substring(0, this.root.length) == this.root) {
         path = path.substring(this.root.length);
      }
      // Strip the sso stuff out of the path.
      path = path.replace(/\??sso=[^&]*/, '');
      
      data.args[0] = path;
   }
   
   if (data.callbackID) {
      // The function was called with a callback.
      var callback = function() {
         me.callRemote("callback", [data.callbackID, Array.prototype.slice.call(arguments)]);
      }
      
      data.args.push(callback);
   }
   
   var result = func.apply(this, data.args);
};

embed.scrollTo = embed.fn.scrollTo = function(top) {
   window.scrollTo(0, this.iframe.offsetTop + top);
};

embed.setLocation = embed.fn.setLocation = function(path) {
   if (!this.isReady)
      Vanilla.error("The embed is not ready.");
   
   var url = this.root+Vanilla.slash(path);
   this.callRemote('setLocation', url);
};

embed.signout = embed.fn.signout = function() {
   this.callRemote('signout');
};

embed.start = embed.fn.start = function() {
   var me = this;

   // Destroy a previous socket.
   if (me.socket) {
      try {
         me.socket.destroy();
      } catch(ex) {
      }
   }
   
   var url = me.root+(me.initialPath || '/');
   
   if (me.sso)
      url += (url.indexOf('?') == -1 ? '?' : '&') + 'sso='+encodeURIComponent(me.sso)
   
   me.socket = new easyXDM.Socket({
      remote: me.root+'/container.html?url='+encodeURIComponent(url),
      swf: me.root+'/js/easyXDM/easyxdm.swf',
      remoteHelper: me.root+'/js/easyXDM/name.html',
      container: me.container,
      props: { allowtransparency: "true", scrolling: "no", style: { visibility: "hidden" } },
      onReady: function() {
         me.iframe = me.container.getElementsByTagName('iframe')[0];
         me.iframe.style.visibility = "visible";
         me.isReady = true;
         
         if (me.onReady)
            me.onReady.apply(me);
      },
      onMessage: function(message, origin) {
         me.onMessage(message, origin);
      }
   });
};

embed.stop = embed.fn.stop = function() {
   if (this.socket) {
      var me = this;
      
      if (!this.isReady) {
         this.onReady = function() {
            me.socket.destroy();
            me.isReady = false;
         };
      } else {
         this.socket.destroy();
         this.isReady = false;
      }
   }
};

Vanilla.embed = embed;

// Expose Vanilla to the global object
window.Vanilla = Vanilla;

})(window);