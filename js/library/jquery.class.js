/**
 * The Class class
 *
 * Copyright (c) 2008, Digg, Inc.
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice, 
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice, 
 *   this list of conditions and the following disclaimer in the documentation 
 *   and/or other materials provided with the distribution.
 * - Neither the name of the Digg, Inc. nor the names of its contributors 
 *   may be used to endorse or promote products derived from this software 
 *   without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" 
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE 
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE 
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF 
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS 
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) 
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @module Class
 * @author Micah Snyder <micah@digg.com>
 * @description Class creation and management for use with jQuery
 * @link http://code.google.com/p/digg
 *
 * @requires Array.indexOf -- If you support older browsers, make sure you prototype this in
 */

/**
 * @class Class A singleton that handles static and dynamic classes, as well as namespaces
 */
(function($) {
Class = {
    /**
     * @function create Make a class! Do work son, do work
     * @param {optional Object} methods Any number of objects can be passed in as arguments to be added to the class upon creation
     * @param {optional Boolean} static If the last argument is Boolean, it will be treated as the static flag. Defaults to false (dynamic)
     */
    create: function() {
        //figure out if we're creating a static or dynamic class
        var s = (arguments.length > 0 && //if we have arguments...
                arguments[arguments.length - 1].constructor == Boolean) ? //...and the last one is Boolean...
                    arguments[arguments.length - 1] : //...then it's the static flag...
                    false; //...otherwise default to a dynamic class
        
        //static: Object, dynamic: Function
        var c = s ? {} : function() {
            this.init.apply(this, arguments);
        }
        
        //all of our classes have these in common
        var methods = {
            //a basic namespace container to pass objects through
            ns: [],
            
            //a container to hold one level of overwritten methods
            supers: {},
            
            //a constructor
            init: function() {},
            
            //our namespace function
            namespace:function(ns) {
                //don't add nothing
                if (!ns) return null;
                
                //closures are neat
                var _this = this;
                
                //handle ['ns1', 'ns2'... 'nsN'] format
                if(ns.constructor == Array) {
                    //call namespace normally for each array item...
                    $.each(ns, function() {
                        _this.namespace.apply(_this, [this]);
                    });
                    
                    //...then get out of this call to namespace
                    return;
                
                //handle {'ns': contents} format
                } else if(ns.constructor == Object) {
                    //loop through the object passed to namespace
                    for(var key in ns) {
                        //only operate on vanilla Objects and Functions
                        if([Object, Function].indexOf(ns[key].constructor) > -1) {
                            //in case this.ns has been deleted
                            if(!this.ns) this.ns = [];
                            
                            //copy the namespace into an array holder
                            this.ns[key] = ns[key];
                            
                            //apply namespace, this will be caught by the ['ns1', 'ns2'... 'nsN'] format above
                            this.namespace.apply(this, [key]);
                        }
                    }
                    
                    //we're done with namespace for now
                    return;
                }
                
                //note: [{'ns': contents}, {'ns2': contents2}... {'nsN': contentsN}] is inherently handled by the above two cases
                
                var levels = ns.split(".");
                
                //if init && constructor == Object or Function
                var nsobj = this.prototype ? this.prototype : this;
                
                $.each(levels, function() {
                    /* When adding a namespace check to see, in order:
                     * 1) does the ns exist in our ns passthrough object?
                     * 2) does the ns already exist in our class
                     * 3) does the ns exist as a global var?
                        * NOTE: support for this was added so that you can namespace classes
                          into other classes, i.e. MyContainer.namespace('MyUtilClass'). this
                          behaviour is dangerously greedy though, so it may be removed.
                     * 4) if none of the above, make a new static class
                     */
                    nsobj[this] = _this.ns[this] || nsobj[this] || window[this] || Class.create(true);
                    
                    //remove our temp passthrough if it exists
                    delete _this.ns[this];
                    
                    //move one level deeper for the next iteration
                    nsobj = nsobj[this];
                });
                
                //TODO: do we really need to return this? it's not that useful anymore
                return nsobj;
            },
            
            /* create exists inside classes too. neat huh?
                usage differs slightly: MyClass.create('MySubClass', { myMethod: function() }); */
            create: function() {
                //turn arguments into a regular Array
                var args = Array.prototype.slice.call(arguments);
                
                //pull the name of the new class out
                var name = args.shift();
                
                //create a new class with the rest of the arguments
                var temp = Class.create.apply(Class, args);
                
                //load our new class into the {name: class} format to pass it into namespace()
                var ns = {};
                ns[name] = temp;
                
                //put the new class into the current one
                this.namespace(ns);
            },
            
            //call the super of a method
            sup: function() {
                try {
                    var caller = this.sup.caller.name;
                    this.supers[caller].apply(this, arguments);
                } catch(noSuper) {
                    return false;
                }
            }
        }
        
        //static: doesn't need a constructor
        s ? delete methods.init : null;
        
        //put default stuff in the thing before the other stuff
        $.extend(c, methods);
        
        //double copy methods for dynamic classes
        //they get our common utils in their class definition AND their prototype
        if(!s) $.extend(c.prototype, methods);
        
        //static: extend the Object, dynamic: extend the prototype
        var extendee = s ? c : c.prototype;
        
        //loop through arguments. if they're the right type, tack them on
        $.each(arguments, function() {
            //either we're passing in an object full of methods, or the prototype of an existing class
            if(this.constructor == Object || typeof this.init != undefined) {
                /* here we're going per-property instead of doing $.extend(extendee, this) so that
                we overwrite each property instead of the whole namespace. also: we omit the 'namespace'
                helper method that Class tacks on, as there's no point in storing it as a super */
                for(i in this) {
                    /* if a property is a function (other than our built-in helpers) and it already exists
                    in the class, save it as a super. note that this only saves the last occurrence */
                    if(extendee[i] && extendee[i].constructor == Function && ['namespace','create','sup'].indexOf(i) == -1) {
                        //since Function.name is almost never set for us, do it manually
                        this[i].name = extendee[i].name = i;
                        
                        //throw the existing function into this.supers before it's overwritten
                        extendee.supers[i] = extendee[i];
                    }
                    
                    //extend the current property into our class
                    extendee[i] = this[i];
                }
            }
        });
        
        //shiny new class, ready to go
        return c;
    }
};
})(jQuery);
