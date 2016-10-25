/**
 * Takes a jQuery function that updates the DOM and the HTML to add. Converts the html to a jQuery object
 * and then adds it to the DOM. Triggers 'contentLoad' to allow javascript manipulation of the new DOM elements.
 *
 * @param func The jQuery function name.
 * @param html The html to add.
 */
var funcTrigger = function(func, html) {
    this.each(function() {
        var $elem = $($.parseHTML(html + '')); // Typecast html to a string and create a DOM node
        $(this)[func]($elem);
        $elem.trigger('contentLoad');
    });
    return this;
};

$.fn.extend({
    appendTrigger: function(html) {
        return funcTrigger.call(this, 'append', html);
    },

    beforeTrigger: function(html) {
        return funcTrigger.call(this, 'before', html);
    },

    afterTrigger: function(html) {
        return funcTrigger.call(this, 'after', html);
    },

    prependTrigger: function(html) {
        return funcTrigger.call(this, 'prepend', html);
    },

    htmlTrigger: function(html) {
        funcTrigger.call(this, 'html', html);
    },

    replaceWithTrigger: function(html) {
        return funcTrigger.call(this, 'replaceWith', html);
    }
});

gdn = function() {};

gdn.definition = function(text, defaultText) {
    return defaultText;
};

gdn.informError = function(text) {
    return text;
};

$(document).ready(function() {
    $(this).trigger('contentLoad');
});
