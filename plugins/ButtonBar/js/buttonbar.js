/*
 * Caret insert JS
 *
 * This code extends the base object with a method called 'insertAtCaret', which
 * allows text to be added to a textArea at the cursor position.
 *
 * Thanks to http://technology.hostei.com/?p=3
 */
jQuery.fn.insertAtCaret = function(tagName) {
    return this.each(function() {
        if (document.selection) {
            //IE support
            this.focus();
            sel = document.selection.createRange();
            sel.text = tagName;
            this.focus();
        } else if (this.selectionStart || this.selectionStart == '0') {
            //MOZILLA/NETSCAPE support
            startPos = this.selectionStart;
            endPos = this.selectionEnd;
            scrollTop = this.scrollTop;
            this.value = this.value.substring(0, startPos) + tagName + this.value.substring(endPos, this.value.length);
            this.focus();
            this.selectionStart = startPos + tagName.length;
            this.selectionEnd = startPos + tagName.length;
            this.scrollTop = scrollTop;
        } else {
            this.value += tagName;
            this.focus();
        }
    });
};

jQuery.fn.insertRoundCaret = function(strStart, strEnd, strReplace) {
    return this.each(function() {
        if (document.selection) {
            // IE support
            stringBefore = this.value;
            this.focus();
            sel = document.selection.createRange();
            insertString = strReplace ? strReplace : sel.text;
            fullinsertstring = strStart + insertString + strEnd;
            sel.text = fullinsertstring;
            document.selection.empty();
            this.focus();
            stringAfter = this.value;
            i = stringAfter.lastIndexOf(fullinsertstring);
            range = this.createTextRange();
            numlines = stringBefore.substring(0, i).split("\n").length;
            i = i + 3 - numlines + tagName.length;
            j = insertstring.length;
            range.move("character", i);
            range.moveEnd("character", j);
            range.select();
        } else if (this.selectionStart || this.selectionStart == '0') {
            // MOZILLA/NETSCAPE support
            startPos = this.selectionStart;
            endPos = this.selectionEnd;
            scrollTop = this.scrollTop;

            if (!strReplace)
                strReplace = this.value.substring(startPos, endPos);

            this.value = this.value.substring(0, startPos) + strStart
            + strReplace + strEnd
            + this.value.substring(endPos, this.value.length);
            this.focus();
            this.selectionStart = startPos + strStart.length;
            this.selectionEnd = this.selectionStart + strReplace.length;
            this.scrollTop = scrollTop;
        } else {
            if (!strReplace)
                strReplace = '';
            this.value += strStart + strReplace + strEnd;
            this.focus();
        }

    });
}

jQuery.fn.hasSelection = function() {
    var sel = false;
    this.each(function() {
        if (document.selection) {
            sel = document.selection.createRange().text;
        } else if (this.selectionStart || this.selectionStart == '0') {
            startPos = this.selectionStart;
            endPos = this.selectionEnd;
            scrollTop = this.scrollTop;
            sel = this.value.substring(startPos, endPos);
        }
    });

    return sel;
}

/*
 * Caret insert advanced
 *
 * This code allows insertion on complex tags, and was extended by @Barrakketh
 * (barrakketh@gmail.com) from http://forums.penny-arcade.com to allow
 * parameters.
 *
 * Thanks!
 */

//$.fn.insertRoundTag = function(tagName, opts, props) {
//   return this.each(function() {
//      var opener = opts.opener || '[';
//      var closer = opts.closer || ']';
//      var closetype = opts.closetype || 'full';
//      var shortporp = opts.shortprop;
//
//      strStart = opener + tagName;
//      strEnd = '';
//
//      if (shortprop)
//         strStart = strStart + '="' + opt + '"';
//
//      if (props) {
//         for ( var param in props) {
//            strStart = strStart + ' ' + param + '="' + props[param] + '"';
//         }
//      }
//
//      if (closetype == 'full') {
//         strStart = strStart + closer;
//         strEnd = opener + '/' + tagName + closer;
//      } else {
//         strStart = strStart + '/' + closer;
//      }
//
//      $(this).insertRoundCaret(strStart, strEnd);
//    });
//};

$.fn.insertRoundTag = function(tagName, opts, props) {
    var opentag = opts.opentag != undefined ? opts.opentag : tagName;
    var closetag = opts.closetag != undefined ? opts.closetag : tagName;
    var prefix = opts.prefix != undefined ? opts.prefix : '';
    var suffix = opts.suffix != undefined ? opts.suffix : '';
    var prepend = opts.prepend != undefined ? opts.prepend : '';
    var replace = opts.replace != undefined ? opts.replace : false;
    var opener = opts.opener != undefined ? opts.opener : '';
    var closer = opts.closer != undefined ? opts.closer : '';
    var closeslice = opts.closeslice != undefined ? opts.closeslice : '/';
    var closetype = opts.closetype != undefined ? opts.closetype : 'full';
    var shortprop = opts.shortprop;
    var focusprop = opts.center;
    var hasFocused = false;

    strStart = prefix + opener + opentag;
    strEnd = '';

    if (shortprop) {
        strStart = strStart + '="' + shortprop;
        if (focusprop == 'short') {
            strEnd = strEnd + '"';
            hasFocused = true;
        }
        else
            strStart = strStart + '"';
    }
    if (props) {
        var focusing = false;
        for (var param in props) {
            if (hasFocused) {
                strEnd = strEnd + ' ' + param + '="' + props[param] + '"';
                continue;
            }

            if (!hasFocused) {
                strStart = strStart + ' ' + param + '="' + props[param];
                if (param == focusprop) {
                    focusing = true;
                    hasFocused = true;
                }
            }

            if (focusing) {
                strEnd = strEnd + '"';
                focusing = false;
            } else {
                strStart = strStart + '"';
            }
        }
    }

    strReplace = '';
    if (prefix) {
        var selection = $(this).hasSelection();
        if (selection) {
            strReplace = selection.replace(/\n/g, '\n' + prefix);
        }
    }

    if (replace != false) {
        strReplace = replace;
    }

    if (closetype == 'full') {
        if (!hasFocused)
            strStart = strStart + closer;
        else
            strEnd = strEnd + closer;

        strEnd = strEnd + opener + closeslice + closetag + closer + suffix;
    } else {
        if (closeslice && closeslice.length)
            closeslice = " " + closeslice;
        if (!hasFocused)
            strStart = strStart + closeslice + closer + suffix;
        else
            strEnd = strEnd + closeslice + closer + suffix;
    }
    jQuery(this).insertRoundCaret(strStart + prepend, strEnd, strReplace);
}

ButtonBar = {

    // Search for new button bars and handle their events.
    init: function () {
        $('.ButtonBar', this)
            .closest('form')
            .find('div.TextBoxWrapper textarea')
            .each(function (i, textarea) {
                var $textarea = $(textarea);

                if (($textarea.hasClass('BodyBox') || $textarea.hasClass('.js-bodybox')) && !$textarea.data('ButtonBar')) {
                    $textarea.data('ButtonBar', '1');
                    ButtonBar.AttachTo(textarea);
                }
            });
    },

    AttachTo: function(TextArea) {
        // Load the buttonbar and bind this textarea to it
        var ThisButtonBar = $(TextArea).closest('form').find('.ButtonBar');
        $(ThisButtonBar).data('ButtonBarTarget', TextArea);

        var format = $(TextArea).attr('format');
        if (!format)
            format = gdn.definition('InputFormat', 'Html');

        switch (format) {
            case 'Raw':
            case 'Wysiwyg':
                format = 'Html';
                break;
        }

        // Apply the page's InputFormat to this textarea.
        $(TextArea).data('InputFormat', format);

        // Build button UIs
        $(ThisButtonBar).find('.ButtonWrap').each(function(i, el) {
            var Operation = $(el).find('span').text();

            var UIOperation = Operation.charAt(0).toUpperCase() + Operation.slice(1);
            $(el).attr('title', UIOperation);

            var Action = "ButtonBar" + UIOperation;
            $(el).addClass(Action);
        });

        // Attach shortcut keys
        ButtonBar.BindShortcuts(TextArea);

        // Attach events
        $(ThisButtonBar).find('.ButtonWrap').mousedown(function(event) {
            var MyButtonBar = $(event.target).closest('.ButtonBar');
            var Button = $(event.target).find('span').closest('.ButtonWrap');
            if ($(Button).hasClass('ButtonOff')) return;

            var TargetTextArea = $(MyButtonBar).data('ButtonBarTarget');
            if (!TargetTextArea) return false;

            var Operation = $(Button).find('span').text();
            ButtonBar.Perform(TargetTextArea, Operation, event);
            return false;
        });

        ButtonBar.Prepare(ThisButtonBar, TextArea);
    },

    BindShortcuts: function(TextArea) {
        ButtonBar.BindShortcut(TextArea, 'bold', 'ctrl+B');
        ButtonBar.BindShortcut(TextArea, 'italic', 'ctrl+I');
        ButtonBar.BindShortcut(TextArea, 'underline', 'ctrl+U');
        ButtonBar.BindShortcut(TextArea, 'strike', 'ctrl+S');
        ButtonBar.BindShortcut(TextArea, 'url', 'ctrl+L');
        ButtonBar.BindShortcut(TextArea, 'code', 'ctrl+O');
        ButtonBar.BindShortcut(TextArea, 'quote', 'ctrl+Q');
        ButtonBar.BindShortcut(TextArea, 'quickurl', 'ctrl+shift+L');
        ButtonBar.BindShortcut(TextArea, 'post', 'tab');
    },

    BindShortcut: function(TextArea, Operation, Shortcut, ShortcutMode, OpFunction) {
        if (OpFunction == undefined)
            OpFunction = function(e) {
                ButtonBar.Perform(TextArea, Operation, e);
            }

        if (ShortcutMode == undefined)
            ShortcutMode = 'keydown';

        $(TextArea).bind(ShortcutMode, Shortcut, OpFunction);

        var UIOperation = Operation.charAt(0).toUpperCase() + Operation.slice(1);
        var Action = "ButtonBar" + UIOperation;

        var ButtonBarObj = $(TextArea).closest('form').find('.ButtonBar');
        var Button = $(ButtonBarObj).find('.' + Action);
        Button.attr('title', Button.attr('title') + ', ' + Shortcut);

    },

    DisableButton: function(ButtonBarObj, Operation) {
        $(ButtonBarObj).find('.ButtonWrap').each(function(i, Button) {
            var ButtonOperation = $(Button).find('span').text();
            if (ButtonOperation == Operation)
                $(Button).addClass('ButtonOff');
        });
    },

    Prepare: function(ButtonBarObj, TextArea) {
        var InputFormat = $(TextArea).data('InputFormat');
        var PrepareMethod = 'Prepare' + InputFormat;
        if (ButtonBar[PrepareMethod] == undefined)
            return;

        // Call preparer
        ButtonBar[PrepareMethod](ButtonBarObj, TextArea);
    },

    PrepareBBCode: function(ButtonBarObj, TextArea) {
        var HelpText = gdn.definition('ButtonBarBBCodeHelpText', 'ButtonBar.BBCodeHelp');
        $("<div></div>")
            .addClass('ButtonBarMarkupHint')
            .html(HelpText)
            .insertAfter(TextArea);
    },

    PrepareHtml: function(ButtonBarObj, TextArea) {
        ButtonBar.DisableButton(ButtonBarObj, 'spoiler');

        var HelpText = gdn.definition('ButtonBarHtmlHelpText', 'ButtonBar.HtmlHelp');
        $("<div></div>")
            .addClass('ButtonBarMarkupHint')
            .html(HelpText)
            .insertAfter(TextArea);
    },

    PrepareMarkdown: function(ButtonBarObj, TextArea) {
        ButtonBar.DisableButton(ButtonBarObj, 'underline');
        ButtonBar.DisableButton(ButtonBarObj, 'spoiler');

        var HelpText = gdn.definition('ButtonBarMarkdownHelpText', 'ButtonBar.MarkdownHelp');
        $("<div></div>")
            .addClass('ButtonBarMarkupHint')
            .html(HelpText)
            .insertAfter(TextArea);
    },

    Perform: function(TextArea, Operation, Event) {
        Event.preventDefault();

        var InputFormat = $(TextArea).data('InputFormat');
        var PerformMethod = 'Perform' + InputFormat;
        if (ButtonBar[PerformMethod] == undefined)
            return;

        // Call performer
        ButtonBar[PerformMethod](TextArea, Operation);

        switch (Operation) {
            case 'post':
                $(TextArea).closest('form').find('.CommentButton').focus();
                break;
        }
    },

    PerformBBCode: function(TextArea, Operation) {
        bbcodeOpts = {
            opener: '[',
            closer: ']'
        }
        switch (Operation) {
            case 'bold':
                $(TextArea).insertRoundTag('b', bbcodeOpts);
                break;

            case 'italic':
                $(TextArea).insertRoundTag('i', bbcodeOpts);
                break;

            case 'underline':
                $(TextArea).insertRoundTag('u', bbcodeOpts);
                break;

            case 'strike':
                $(TextArea).insertRoundTag('s', bbcodeOpts);
                break;

            case 'code':
                $(TextArea).insertRoundTag('code', bbcodeOpts);
                break;

            case 'image':
                var thisOpts = $.extend(bbcodeOpts, {});

                var PromptText = gdn.definition('ButtonBarImageUrl', 'ButtonBar.ImageUrlText');
                NewURL = prompt(PromptText);
                thisOpts.replace = NewURL;

                $(TextArea).insertRoundTag('img', thisOpts);
                break;

            case 'quickurl':
                var thisOpts = $.extend(bbcodeOpts, {});

                var hasSelection = $(TextArea).hasSelection();
                console.log("sel: " + hasSelection);
                var NewURL = '';
                var PromptText = gdn.definition('ButtonBarLinkUrl', 'ButtonBar.LinkUrlText');
                if (hasSelection !== false)
                    NewURL = hasSelection;
                else
                    NewURL = prompt(PromptText, 'http://');

                thisOpts.shortprop = NewURL;

                $(TextArea).insertRoundTag('url', thisOpts);
                break;

            case 'quote':
                $(TextArea).insertRoundTag('quote', bbcodeOpts);
                break;

            case 'spoiler':
                $(TextArea).insertRoundTag('spoiler', bbcodeOpts);
                break;

            case 'url':
                var thisOpts = $.extend(bbcodeOpts, {});

                var PromptText = gdn.definition('ButtonBarLinkUrl', 'ButtonBar.LinkUrlText');
                var NewURL = prompt(PromptText, 'http://');
                var GuessText = NewURL.replace('http://', '').replace('www.', '');
                thisOpts.shortprop = NewURL;

                var CurrentSelectText = GuessText;
                var CurrentSelect = $(TextArea).hasSelection();
                if (CurrentSelect)
                    CurrentSelectText = CurrentSelect.toString();

                thisOpts.replace = CurrentSelectText;

                $(TextArea).insertRoundTag('url', thisOpts);
                break;
        }
    },

    PerformHtml: function(TextArea, Operation) {
        var htmlOpts = {
            opener: '<',
            closer: '>'
        }
        switch (Operation) {
            case 'bold':
                $(TextArea).insertRoundTag('b', htmlOpts);
                break;

            case 'italic':
                $(TextArea).insertRoundTag('i', htmlOpts);
                break;

            case 'underline':
                $(TextArea).insertRoundTag('u', htmlOpts);
                break;

            case 'strike':
                $(TextArea).insertRoundTag('del', htmlOpts);
                break;

            case 'code':
                var multiline = $(TextArea).hasSelection().indexOf('\n') >= 0;
                if (multiline) {
                    var thisOpts = $.extend(htmlOpts, {
                        opentag: '<pre><code>',
                        closetag: '</code></pre>',
                        opener: '',
                        closer: '',
                        closeslice: ''
                    });
                    $(TextArea).insertRoundTag('', thisOpts);
                } else {
                    $(TextArea).insertRoundTag('code', htmlOpts);
                }
                break;

            case 'image':
                var urlOpts = {};
                var thisOpts = $.extend(htmlOpts, {
                    closetype: 'short'
                });

                var PromptText = gdn.definition('ButtonBarImageUrl', 'ButtonBar.ImageUrlText');
                NewURL = prompt(PromptText);
                urlOpts.src = NewURL;

                $(TextArea).insertRoundTag('img', thisOpts, urlOpts);
                break;

            case 'quickurl':
                var urlOpts = {};
                var thisOpts = $.extend(htmlOpts, {
                    center: 'href'
                });

                var hasSelection = $(TextArea).hasSelection();
                var NewURL = '';
                var PromptText = gdn.definition('ButtonBarLinkUrl', 'ButtonBar.LinkUrlText');
                if (hasSelection !== false) {
                    NewURL = hasSelection;
                    delete thisOpts.center;
                } else
                    NewURL = prompt(PromptText, 'http://');

                urlOpts.href = NewURL;

                $(TextArea).insertRoundTag('a', thisOpts, urlOpts);
                break;

            case 'quote':
                $(TextArea).insertRoundTag('blockquote', htmlOpts);
                break;

            case 'spoiler':
                $(TextArea).insertRoundTag('div', htmlOpts, {'class': 'Spoiler'});
                break;

            case 'url':
                var urlOpts = {};
                var thisOpts = $.extend(htmlOpts, {});

                var PromptText = gdn.definition('ButtonBarLinkUrl', 'ButtonBar.LinkUrlText');
                var NewURL = prompt(PromptText, 'http://');
                var GuessText = NewURL.replace('http://', '').replace('www.', '');
                urlOpts.href = NewURL;

                var CurrentSelectText = GuessText;

                var CurrentSelect = $(TextArea).hasSelection();
                if (CurrentSelect)
                    CurrentSelectText = CurrentSelect.toString();

                thisOpts.replace = CurrentSelectText;

                $(TextArea).insertRoundTag('a', thisOpts, urlOpts);
                break;
        }
    },

    PerformMarkdown: function(TextArea, Operation) {
        var markdownOpts = {
            opener: '',
            closer: '',
            closeslice: ''
        }
        switch (Operation) {
            case 'bold':
                $(TextArea).insertRoundTag('**', markdownOpts);
                break;

            case 'italic':
                $(TextArea).insertRoundTag('_', markdownOpts);
                break;

            case 'underline':
                // DISABLED
                return;
                break;

            case 'strike':
                $(TextArea).insertRoundTag('del', {opener: '<', closer: '>'});
                break;

            case 'code':
                var multiline = $(TextArea).hasSelection().indexOf('\n') >= 0;
                if (multiline) {
                    var thisOpts = $.extend(markdownOpts, {
                        prefix: '    ',
                        opentag: '',
                        closetag: '',
                        opener: '',
                        closer: ''
                    });
                    $(TextArea).insertRoundTag('', thisOpts);
                } else {
                    $(TextArea).insertRoundTag('`', markdownOpts);
                }
                break;

            case 'image':
                var thisOpts = $.extend(markdownOpts, {
                    prefix: '',
                    opentag: '![](',
                    closetag: ')',
                    opener: '',
                    closer: ''
                });
                var PromptText = gdn.definition('ButtonBarImageUrl', 'ButtonBar.ImageUrlText');
                var NewURL = prompt(PromptText, '');
                thisOpts.prepend = NewURL;
                $(TextArea).insertRoundTag('', thisOpts);
                break;

            case 'quickurl':
                var thisOpts = $.extend(markdownOpts, {
                    opentag: '(',
                    closetag: ')'
                });

                var hasSelection = $(TextArea).hasSelection();
                var PromptText = gdn.definition('ButtonBarLinkUrl', 'ButtonBar.LinkUrlText');
                if (hasSelection !== false)
                    var NewURL = hasSelection;
                else {
                    var NewURL = prompt(PromptText, 'http://');
                    thisOpts.prepend = NewURL;
                }

                var GuessText = NewURL.replace('http://', '').replace('www.', '');
                thisOpts.prefix = '[' + GuessText + ']';

                $(TextArea).insertRoundTag('', thisOpts);
                break;

            case 'quote':
                var thisOpts = $.extend(markdownOpts, {
                    prefix: '> ',
                    opentag: '',
                    closetag: '',
                    opener: '',
                    closer: ''
                });
                $(TextArea).insertRoundTag('', thisOpts);
                break;

            case 'spoiler':
                // DISABLED
                return;
                break;

            case 'url':
                var PromptText = gdn.definition('ButtonBarLinkUrl', 'ButtonBar.LinkUrlText');
                var NewURL = prompt(PromptText, 'http://');
                var GuessText = NewURL.replace('http://', '').replace('www.', '');

                var CurrentSelectText = GuessText;
                var CurrentSelect = $(TextArea).hasSelection();
                if (CurrentSelect)
                    CurrentSelectText = CurrentSelect.toString();

                var thisOpts = $.extend(markdownOpts, {
                    prefix: '[' + CurrentSelectText + ']',
                    opentag: '(',
                    closetag: ')',
                    opener: '',
                    closer: '',
                    replace: NewURL
                });
                $(TextArea).insertRoundTag('', markdownOpts);
                break;
        }
    }

}

$(document).on('contentLoad', function(e) {
    ButtonBar.init.apply(e.target);
});
