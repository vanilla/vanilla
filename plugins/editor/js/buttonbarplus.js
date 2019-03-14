(function($) {


    /*
     * Caret insert JS
     *
     * This code extends the base object with a method called 'insertAtCaret', which
     * allows text to be added to a textArea at the cursor position.
     *
     * Thanks to http://technology.hostei.com/?p=3
     */
    $.fn.insertAtCaret = function(tagName) {
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

    $.fn.insertRoundCaret = function(strStart, strEnd, strReplace) {
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

    $.fn.hasSelection = function() {
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
        $(this).insertRoundCaret(strStart + prepend, strEnd, strReplace);
    }


    // TODO get rid of above functions and replace all functionality with rangy
    // inputs library, which is far more versatile.
    /**
     * At the moment this is a mish-mash of different code that I inherited.
     * Eventually clean it all up and remove the redundancy, which there is a
     * lot of. I hacked a lot of new functionality into it, improved from non-plus
     * version of ButtonBar.
     */
    $(document).ready(function() {

        ButtonBar = {

            Const: {
                URL_PREFIX: 'http://',
                //EMOJI_ALIAS_REGEX: /^[\:\'\*\)\(\;\>\<\#\-\+\&\\\|\/a-zA-Z0-9]+$/
                // let all emojis through
                EMOJI_ALIAS_REGEX: /./
            },

            AttachTo: function(TextArea, format) {
                // Load the buttonbar and bind this textarea to it
                var ThisButtonBar = $(TextArea).closest('form').find('.editor');
                $(ThisButtonBar).data('ButtonBarTarget', TextArea);

                //var format = gdn.definition('editorInputFormat', 'Html');
                format = format.toLowerCase();

                // Do this because formats provided by different sites are not all
                // the same: some have capitalizations, others do not.
                var inputFormats = {
                    'bbcode': 'BBCode',
                    'html': 'Html',
                    'markdown': 'Markdown',
                    'textex': 'TextEx',
                    'text': 'Text'
                };

                // Run autobulleting functions on load, so user can just naturally
                // begin bulleting and it'll be completed for them.
                // Define as many types of bullets to auto-bullet, and the function
                // will take care of the rest.
                switch (format) {
                    case 'bbcode':
                        autoBulletTextarea(TextArea, "[*]");
                        break;
                    case 'html':
                        autoBulletTextarea(TextArea, "<li>");
                        break;
                    case 'markdown':
                        // Markdown supports multiple bulleting syntaxes, so support
                        // ALL OF THEM.
                        autoBulletTextarea(TextArea, "1.");
                        autoBulletTextarea(TextArea, "*");
                        autoBulletTextarea(TextArea, "-");
                        autoBulletTextarea(TextArea, "+");
                        break;
                }

                // Apply the page's InputFormat to this textarea.
                $(TextArea).data('InputFormat', inputFormats[format]);

                // Attach events
                $(ThisButtonBar).find('.editor-action').on('mousedown', function(event) {

                    var MyButtonBar = $(event.target).closest('.editor');
                    var Button = $(event.target);

                    var TargetTextArea = $(MyButtonBar).data('ButtonBarTarget');
                    if (!TargetTextArea) return false;

                    var Operation = '';
                    var Value = '';

                    // Change in emoji markup, now wrapped, so check for this case.
                    if ($(Button).hasClass('emoji')) {
                        Button = $(Button).closest('.editor-action');
                    }

                    if ($(Button).data('editor')) {
                        // :\ and :'( break object and return string, while server
                        // needs to add slashes. Without addslashes :\ does not work but
                        // :'( does, while with addslashes :\ works.
                        if (typeof $(Button).data('editor') == 'object') {
                            Operation = $(Button).data('editor').action;
                            Value = $(Button).data('editor').value;
                        } else if (typeof $(Button).data('editor') == 'string') {
                            var objFix = eval("(" + $(Button).data('editor') + ")");
                            Operation = objFix.action;
                            Value = objFix.value;
                        }
                    }

                    // when checking value, make sure user did not type their own, as
                    // the value is being used directly below. If it fails the regex
                    // clear it out and fail the emoji code.

                    if (Operation == 'emoji'
                        && Value.length
                        && !(ButtonBar.Const.EMOJI_ALIAS_REGEX.test(Value))) {
                        Value = ':warning:'; // was tampered with.
                    }

                    ButtonBar.Perform(TargetTextArea, Operation, event, Value);
                    return false;
                });

                // Attach shortcut keys
                // TODO use these for whole editor.
                ButtonBar.BindShortcuts(TextArea);
            },

            BindShortcuts: function(TextArea) {
                ButtonBar.BindShortcut(TextArea, 'bold', 'ctrl+B');
                ButtonBar.BindShortcut(TextArea, 'italic', 'ctrl+I');
                ButtonBar.BindShortcut(TextArea, 'underline', 'ctrl+U');
                ButtonBar.BindShortcut(TextArea, 'strike', 'ctrl+S');
                ButtonBar.BindShortcut(TextArea, 'url', 'ctrl+L');
                ButtonBar.BindShortcut(TextArea, 'code', 'ctrl+O');
                ButtonBar.BindShortcut(TextArea, 'blockquote', 'ctrl+Q');
            },

            BindShortcut: function(TextArea, Operation, Shortcut, ShortcutMode, OpFunction) {
                if (OpFunction == undefined) {
                    OpFunction = function(e) {
                        // For now, e is empty, and last value is there as hint
                        ButtonBar.Perform(TextArea, Operation, e, 'keyshortcut');
                    }
                }

                if (ShortcutMode == undefined)
                    ShortcutMode = 'keydown';

                $(TextArea).bind(ShortcutMode, Shortcut, OpFunction);
            },

            Perform: function(TextArea, Operation, Event, Value) {
                Event.preventDefault();

                if (Operation === 'emoji' && Value === '') {
                    return;
                }

                var InputFormat = $(TextArea).data('InputFormat');

                var PerformMethod = 'Perform' + InputFormat;
                if (ButtonBar[PerformMethod] == undefined)
                    return;

                // Add space on either side, in case user clicks emoji right after bit of text.
                Value = ' ' + Value + ' '; // for now just used for emoji to reduce redundancy

                // Call performer
                ButtonBar[PerformMethod](TextArea, Operation, Value);

                switch (Operation) {
                    case 'post':
                        $(TextArea).closest('form').find('.CommentButton').focus();
                        break;
                }
            },

            PerformBBCode: function(TextArea, Operation, Value) {
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
                    /*
                     case 'underline':
                     $(TextArea).insertRoundTag('u',bbcodeOpts);
                     break;
                     */

                    case 'strike':
                        $(TextArea).insertRoundTag('s', bbcodeOpts);
                        break;

                    case 'code':
                        $(TextArea).insertRoundTag('code', bbcodeOpts);
                        break;

                    case 'blockquote':
                        $(TextArea).insertRoundTag('quote', bbcodeOpts);
                        break;

                    case 'spoiler':
                        $(TextArea).insertRoundTag('spoiler', bbcodeOpts);
                        break;

                    case 'url':

                        // Special handling of this case, when using keyboard shortcuts.
                        if (Value.trim() == 'keyshortcut') {
                            var currentText = $(TextArea).getSelection().text;
                            $(TextArea).surroundSelectedText('[url="' + currentText + '"]', '[/url]', 'select');
                        }

                        // Hooking in to standardized dropdown for submitting links
                        var inputBox = $('.editor-input-url');
                        $(inputBox).parent().find('.Button')
                            .off('click.insertData')
                            .on('click.insertData', function(e) {
                                if (!$(this).hasClass('Cancel')) {
                                    var thisOpts = $.extend(bbcodeOpts, {});
                                    var val = inputBox[0].value;
                                    var GuessText = val.replace(ButtonBar.Const.URL_PREFIX, '').replace('www.', '');
                                    var CurrentSelect = $(TextArea).hasSelection();

                                    CurrentSelectText = (CurrentSelect)
                                        ? CurrentSelect.toString()
                                        : GuessText;

                                    thisOpts.shortprop = val;
                                    thisOpts.replace = CurrentSelectText;

                                    $(TextArea).insertRoundTag('url', thisOpts);

                                    // Close dropdowns
                                    $('.editor-dialog-fire-close').trigger('mouseup.fireclose');

                                    // Set standard text
                                    inputBox[0].value = ButtonBar.Const.URL_PREFIX;
                                }
                            });


                        break;

                    case 'image':

                        // Hooking in to standardized dropdown for submitting links
                        var inputBox = $('.editor-input-image');
                        $(inputBox).parent().find('.Button')
                            .off('click.insertData')
                            .on('click.insertData', function(e) {
                                if (!$(this).hasClass('Cancel')) {
                                    var thisOpts = $.extend(bbcodeOpts, {});
                                    var val = inputBox[0].value;
                                    thisOpts.replace = val;
                                    $(TextArea).insertRoundTag('img', thisOpts);

                                    // Close dropdowns
                                    $('.editor-dialog-fire-close').trigger('mouseup.fireclose');

                                    // Set standard text
                                    inputBox[0].value = ButtonBar.Const.URL_PREFIX;
                                }
                            });

                        break;

                    case 'alignleft':
                        $(TextArea).insertRoundTag('left', bbcodeOpts);
                        break;
                    case 'aligncenter':
                        $(TextArea).insertRoundTag('center', bbcodeOpts);
                        break;
                    case 'alignright':
                        $(TextArea).insertRoundTag('right', bbcodeOpts);
                        break;

                    case 'orderedlist':
                        $(TextArea).surroundSelectedText('[list=1]', '\n[/list]', 'select');
                        var tagListItem = '\n[*] ';
                        var selection = '\n' + $(TextArea).getSelection().text;
                        selection = selection.replace(/(\r\n|\n|\r)/gm, tagListItem);
                        $(TextArea).replaceSelectedText(selection, 'collapseToEnd');
                        break;

                    case 'unorderedlist':
                        $(TextArea).surroundSelectedText('[list]', '\n[/list]', 'select');
                        var tagListItem = '\n[*] ';
                        var selection = '\n' + $(TextArea).getSelection().text;
                        selection = selection.replace(/(\r\n|\n|\r)/gm, tagListItem);
                        $(TextArea).replaceSelectedText(selection, 'collapseToEnd');
                        break;

                    case 'emoji':
                        $(TextArea).insertText(Value, $(TextArea).getSelection().start, "collapseToEnd");
                        break;
                }
            },

            PerformHtml: function(TextArea, Operation, Value) {
                var htmlOpts = {
                    opener: '<',
                    closer: '>'
                }
                switch (Operation) {
                    case 'bold':
                        $(TextArea).insertRoundTag('b', htmlOpts, {'class': 'Bold'});
                        break;

                    case 'italic':

                        $(TextArea).insertRoundTag('i', htmlOpts, {'class': 'Italic'});
                        break;

                    /*
                     case 'underline':
                     $(TextArea).insertRoundTag('u',htmlOpts, {'class':'Underline'});
                     break;
                     */

                    case 'strike':
                        $(TextArea).insertRoundTag('del', htmlOpts, {'class': 'Delete'});
                        break;

                    case 'code':
                        var multiline = $(TextArea).hasSelection().indexOf('\n') >= 0;
                        if (multiline) {
                            var thisOpts = $.extend(htmlOpts, {
                                opentag: '<pre class="CodeBlock"><code>',
                                closetag: '</code></pre>',
                                opener: '',
                                closer: '',
                                closeslice: ''
                            });
                            $(TextArea).insertRoundTag('', thisOpts);
                        } else {
                            $(TextArea).insertRoundTag('code', htmlOpts, {'class': 'CodeInline'});
                        }
                        break;

                    case 'blockquote':
                        $(TextArea).insertRoundTag('div', htmlOpts, {'class': 'Quote'});
                        break;

                    case 'spoiler':
                        $(TextArea).insertRoundTag('div', htmlOpts, {'class': 'Spoiler'});
                        break;

                    case 'url':

                        // Special handling of this case, when using keyboard shortcuts.
                        if (Value.trim() == 'keyshortcut') {
                            var currentText = $(TextArea).getSelection().text;
                            $(TextArea).surroundSelectedText('<a href="' + currentText + '">', '</a>', 'select');
                        }

                        // Hooking in to standardized dropdown for submitting links
                        var inputBox = $('.editor-input-url');
                        $(inputBox).parent().find('.Button')
                            .off('click.insertData')
                            .on('click.insertData', function(e) {
                                if (!$(this).hasClass('Cancel')) {
                                    var urlOpts = {};
                                    var thisOpts = $.extend(htmlOpts, {});

                                    var val = inputBox[0].value;
                                    var GuessText = val.replace(ButtonBar.Const.URL_PREFIX, '').replace('www.', '');
                                    var CurrentSelect = $(TextArea).hasSelection();

                                    CurrentSelectText = (CurrentSelect)
                                        ? CurrentSelect.toString()
                                        : GuessText;

                                    urlOpts.href = val;
                                    thisOpts.replace = CurrentSelectText;

                                    $(TextArea).insertRoundTag('a', thisOpts, urlOpts);

                                    // Close dropdowns
                                    $('.editor-dialog-fire-close').trigger('mouseup.fireclose');

                                    // Set standard text
                                    inputBox[0].value = ButtonBar.Const.URL_PREFIX;
                                }
                            });

                        break;

                    case 'image':

                        // Hooking in to standardized dropdown for submitting links
                        var inputBox = $('.editor-input-image');
                        $(inputBox).parent().find('.Button')
                            .off('click.insertData')
                            .on('click.insertData', function(e) {
                                if (!$(this).hasClass('Cancel')) {

                                    var urlOpts = {};
                                    var thisOpts = $.extend(htmlOpts, {
                                        closetype: 'short'
                                    });

                                    var val = inputBox[0].value;
                                    urlOpts.src = val;
                                    $(TextArea).insertRoundTag('img', thisOpts, urlOpts);

                                    // Close dropdowns
                                    $('.editor-dialog-fire-close').trigger('mouseup.fireclose');

                                    // Set standard text
                                    inputBox[0].value = ButtonBar.Const.URL_PREFIX;
                                }
                            });

                        break;

                    case 'alignleft':
                        $(TextArea).insertRoundTag('div', htmlOpts, {'class': 'AlignLeft'});
                        break;
                    case 'aligncenter':
                        $(TextArea).insertRoundTag('div', htmlOpts, {'class': 'AlignCenter'});
                        break;
                    case 'alignright':
                        $(TextArea).insertRoundTag('div', htmlOpts, {'class': 'AlignRight'});
                        break;

                    case 'heading1':
                        $(TextArea).insertRoundTag('h1', htmlOpts);
                        break;
                    case 'heading2':
                        $(TextArea).insertRoundTag('h2', htmlOpts);
                        break;

                    case 'orderedlist':
                        $(TextArea).surroundSelectedText('<ol>', '\n</ol>', 'select');
                        var tagListItem = '\n<li> ';
                        var selection = '\n' + $(TextArea).getSelection().text;
                        selection = selection.replace(/(\r\n|\n|\r)/gm, tagListItem);
                        $(TextArea).replaceSelectedText(selection, 'collapseToEnd');
                        break;

                    case 'unorderedlist':
                        $(TextArea).surroundSelectedText('<ul>', '\n</ul>', 'select');
                        var tagListItem = '\n<li> ';
                        var selection = '\n' + $(TextArea).getSelection().text;
                        selection = selection.replace(/(\r\n|\n|\r)/gm, tagListItem);
                        $(TextArea).replaceSelectedText(selection, 'collapseToEnd');
                        break;

                    case 'emoji':
                        $(TextArea).insertText(Value, $(TextArea).getSelection().start, "collapseToEnd");
                        break;
                }
            },

            PerformMarkdown: function(TextArea, Operation, Value) {
                var markdownOpts = {
                    opener: '',
                    closer: '',
                    closeslice: ''
                };

                switch (Operation) {
                    case 'bold':
                        $(TextArea).insertRoundTag('**', markdownOpts);
                        break;

                    case 'italic':
                        $(TextArea).insertRoundTag('_', markdownOpts);
                        break;

                    case 'strike':
                        $(TextArea).insertRoundTag('~~', markdownOpts);
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

                    case 'blockquote':
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
                        var thisOpts = $.extend(markdownOpts, {
                            prefix: '>! ',
                            opentag: '',
                            closetag: '',
                            opener: '',
                            closer: ''
                        });
                        $(TextArea).insertRoundTag('', thisOpts);
                        break;


                    case 'heading1':
                        var thisOpts = $.extend(markdownOpts, {
                            prefix: '# ',
                            opentag: '',
                            closetag: '',
                            opener: '',
                            closer: ''
                        });
                        $(TextArea).insertRoundTag('', thisOpts);
                        break;

                    case 'heading2':
                        var thisOpts = $.extend(markdownOpts, {
                            prefix: '## ',
                            opentag: '',
                            closetag: '',
                            opener: '',
                            closer: ''
                        });
                        $(TextArea).insertRoundTag('', thisOpts);
                        break;

                    case 'url':

                        var currentText = $(TextArea).getSelection().text;

                        // Special handling of this case, when using keyboard shortcuts.
                        if (Value.trim() == 'keyshortcut') {
                            $(TextArea).surroundSelectedText('[' + currentText + '](', ')', 'select');
                        }

                        // Hooking in to standardized dropdown for submitting links
                        var inputBox = $('.editor-input-url');
                        $(inputBox).parent().find('.Button')
                            .off('click.insertData')
                            .on('click.insertData', function(e) {
                                if (!$(this).hasClass('Cancel')) {

                                    var val = inputBox[0].value;
                                    var GuessText = val.replace(ButtonBar.Const.URL_PREFIX, '').replace('www.', '');

                                    CurrentSelectText = (currentText)
                                        ? currentText.toString()
                                        : GuessText;

                                    $(TextArea).focus();
                                    $(TextArea).replaceSelectedText('[' + CurrentSelectText + '](' + val + ' "' + CurrentSelectText + '")', 'select');

                                    // Close dropdowns
                                    $('.editor-dialog-fire-close').trigger('mouseup.fireclose');

                                    // Set standard text
                                    inputBox[0].value = ButtonBar.Const.URL_PREFIX;
                                }
                            });

                        break;

                    case 'image':

                        // Grab this immediately, because focus may be set to input
                        // in a moment.
                        var currentText = $(TextArea).getSelection().text;

                        // Hooking in to standardized dropdown for submitting links
                        var inputBox = $('.editor-input-image');
                        $(inputBox).parent().find('.Button')
                            .off('click.insertData')
                            .on('click.insertData', function(e) {
                                if (!$(this).hasClass('Cancel')) {
                                    var val = inputBox[0].value;
                                    $(TextArea).focus();
                                    $(TextArea).replaceSelectedText('![' + currentText + '](' + val + ' "' + currentText + '")', 'select');
                                    // Close dropdowns
                                    $('.editor-dialog-fire-close').trigger('mouseup.fireclose');

                                    // Set standard text
                                    inputBox[0].value = ButtonBar.Const.URL_PREFIX;
                                }
                            });

                        break;

                    case 'orderedlist':
                        var bullet = '1.';
                        var newLine = '\n';
                        var newList = '';
                        var lines = $(TextArea).getSelection().text.split(newLine);

                        for (var i = 0, l = lines.length; i < l; i++) {
                            newList += i + 1 + '. ' + lines[i] + newLine;

                            // If last line, no new line, so that user can start typing
                            // and automatically insert a new list item
                            if (i + 1 == l) {
                                newList = newList.slice(0, -newLine.length);
                            }
                        }

                        $(TextArea).replaceSelectedText(newList, 'collapseToEnd');
                        break;

                    case 'unorderedlist':
                        var bullet = '*';
                        // When selecting several rows, place bullets before every one.
                        var tagListItem = '\n' + bullet + ' ';
                        var selection = bullet + ' ' + $(TextArea).getSelection().text;
                        selection = selection.replace(/(\r\n|\n|\r)/gm, tagListItem);
                        $(TextArea).replaceSelectedText(selection, 'collapseToEnd');
                        break;

                    case 'emoji':
                        $(TextArea).insertText(Value, $(TextArea).getSelection().start, "collapseToEnd");
                        break;
                }
            },

            PerformText: function(TextArea, Operation, Value) {
                switch (Operation) {
                    case 'emoji':
                        $(TextArea).insertText(Value, $(TextArea).getSelection().start, "collapseToEnd");
                        break;
                }

            },

            PerformTextEx: function(TextArea, Operation, Value) {
                switch (Operation) {
                    case 'emoji':
                        $(TextArea).insertText(Value, $(TextArea).getSelection().start, "collapseToEnd");
                        break;
                }
            }

        }
    });

    /**
     * Auto-bullet regular textareas, using any type of bullet. Simply provide the
     * function with the textarea to target, and the bullet string, e.g., "*" or
     * "-" or "1." and even the numbers will increment.
     *
     * This depends on Tim Down's rangy inputs.
     *
     * TODO clean up regexs. Go over this part, particular:
     * ("+ RegExp.escape(bullet) +"|\\d+\\.|[\\*\\+\\-])
     * because the last OR statement contains hardcoded values. They can be replaced
     * by .{3}, but then any newline char space will autobullet.
     *
     * For now this is in global scope and only used by buttonbarplus.
     * TODO integrate in editor.js instead of this. Make sure it is abstracted
     * from everything.
     *
     * @author Dane MacMillan <dane@vanillaforums.com>
     */
    function autoBulletTextarea(textarea, bullet) {

        var originalBullet = bullet;
        var lastBullet = bullet;

        // Auto-bullet
        $(textarea).off('keyup.autoBullet click.autoBullet').on('keyup.autoBullet click.autoBullet', function(e) {
            // For dynamically escaping literal characters in lineInsert,
            // as they could be anything
            RegExp.escape = function(str) {
                return str.replace(new RegExp("[\\$\\^.*+?|()\\[\\]{}\\\\]", "g"), "\\$&");
            };

            var end = $(this).getSelection().end;
            var result = (new RegExp("(\\n|^)(" + RegExp.escape(bullet) + "|\\d+\\.|[\\*\\+\\-])([\\s\\w\\W]+)\\n?$")).exec(this.value.slice(0, end));
            var lastWord = (result) ? result[0] : null;

            if (lastWord
            //&& lastWord.indexOf(bullet) >= 0
            ) {
                var lines = lastWord.split('\n');
                var currentLine = lines[lines.length - 1];

                // lastLine can be 'undefined' in some situations, and though it
                // doesn't break anything, it produces an error in the console. The
                // issue that prompted the optional assignment was @mentions with
                // usernames that have spaces, but in particular, characters that
                // are used as list starters, such as - + and *.
                var lastLine = lines[lines.length - 2] || '';

                if (e.which == 13) {
                    lastBullet = (new RegExp("^\\n?(" + RegExp.escape(bullet) + "|\\d+\\.|[\\*\\+\\-])([\\s\\w\\W]+)\\n?")).exec(lastLine);

                    if (lastBullet) {
                        lastBullet = lastBullet[1].toString().split(' ')[0];
                    } else {
                        lastBullet = bullet;
                    }

                    // If bullet is a number (for ordered lists in
                    // markdown, for example, then increment it from
                    // the current line that it is on.
                    if (lastBullet.match(/\d+\./)) {
                        var nextNumber = parseInt(lastLine.match(/^\d+\./)) + 1;
                        bullet = nextNumber.toString() + '.';
                    } else if (lastBullet != bullet) {
                        // This is important in case you want textarea to support
                        // multiple bullets, on top of numbered bullets.
                        bullet = lastBullet;
                    }

                    // If last line does not have any bullets, just
                    // cancel out this whole operation
                    if (!lastLine.match(new RegExp("^(" + RegExp.escape(bullet) + "|\\d+\\.|[\\*\\+\\-])\\s"))) {
                        // only time bullet is not original, is when incrementing a
                        // number, so set bullet to original. For the meantime, only
                        // markdown ordered lists will have any change here.
                        //console.log('cancel: '+ JSON.stringify(lastLine));
                        bullet = originalBullet;
                        return false;
                    }

                    // Either delete the last empty bullet or add a new one
                    if (!lastLine.match(new RegExp("^(" + RegExp.escape(bullet) + "|\\d+\\.|[\\*\\+\\-])\\s(.*)?[\\w\\W]+"))) {
                        $(this).deleteText(end - bullet.length - 2, end, true); // 2 is a newline and space
                        $(this).replaceSelectedText('\n', 'collapseToEnd');
                        bullet = originalBullet;
                    } else {
                        $(this).replaceSelectedText(bullet + ' ', 'collapseToEnd');

                        if (lastBullet.match(/\d+\./)) {
                            // If user adding a new numbered list in an established list,
                            // reflow all the numbers below it.
                            var initialCaretPosition = $(this).getSelection().end;
                            var allTextBelow = this.value.slice(initialCaretPosition, this.value.length);
                            var affectedListItems = /[\s\S]+?(\n\n)/.exec(allTextBelow);

                            if (affectedListItems) {
                                affectedListItems = affectedListItems[0];

                                var reflowedItemList = affectedListItems.replace(/\n?(\d+)\.\s(.*)/gim, function(match, p1, p2) {
                                    var num = parseInt(p1) + 1;
                                    var txt = p2;
                                    return '\n' + num + '. ' + txt;
                                });

                                $(this).deleteText(initialCaretPosition, initialCaretPosition + affectedListItems.length, false);
                                $(this).insertText(reflowedItemList, initialCaretPosition, 'collapseToStart');
                            }
                        }
                    }
                }
            }
        });
    }

}(jQuery));
