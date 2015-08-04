function Gdn_Quotes() {

    this.InsertMode = 'default';
    this.Editors = [];
    this.EditorID = 1;

    Gdn_Quotes.prototype.Prepare = function() {

        // Attach quote event to each Quote button, and return false to prevent link follow
        $('a.ReactButton.Quote').livequery('click', jQuery.proxy(function(event) {
            var QuoteLink = jQuery(event.target).closest('a');
            var ObjectID = QuoteLink.attr('href').split('/').pop();
            this.Quote(ObjectID, QuoteLink);
            return false;
        }, this));

        /**
         * Always track which editor we're looking at
         *  - Follow clicks to Edit button
         *  - Follow clicks to Submits and Cancels
         */

        // Follow edit clicks
        var Quotes = this;
        jQuery('textarea.TextBox').livequery(function() {
            Quotes.EditorStack(this);
        }, function() {
            Quotes.EditorStack(this, true);
        });

        // Determine what mode we're in (default, cleditor... ?)
        jQuery('div.cleditorMain').livequery(function() {
            Quotes.SetInsertMode('cleditor', this);
        });

        var QuoteFoldingLevel = gdn.definition('QuotesFolding', 1);

        if (QuoteFoldingLevel != 'None') {
            QuoteFoldingLevel = parseInt(QuoteFoldingLevel) + 1;
            var MaxFoldingLevel = 6;
            jQuery('.Comment .Message').livequery(function() {


                // Find the closest child quote
                var PetQuote = jQuery(this).children('.UserQuote');
                if (!PetQuote.length) return;

                Quotes.ExploreFold(PetQuote, 1, MaxFoldingLevel, QuoteFoldingLevel);

            });

            jQuery('a.QuoteFolding').livequery('click', function() {
                var QuoteTarget = jQuery(this).closest('.QuoteText').children('.UserQuote');
                QuoteTarget = jQuery(QuoteTarget);
                QuoteTarget.toggle();

                if (QuoteTarget.css('display') != 'none')
                    jQuery(this).html('&laquo; hide previous quotes');
                else
                    jQuery(this).html('&raquo; show previous quotes');

                return false;
            });
        }
    }

    Gdn_Quotes.prototype.ExploreFold = function(QuoteTree, FoldingLevel, MaxLevel, TargetLevel) {
        if (FoldingLevel > MaxLevel || FoldingLevel > TargetLevel) return;
        var Quotes = this;
        jQuery(QuoteTree).each(function(i, el) {
            var ExamineQuote = jQuery(el);

            if (FoldingLevel == TargetLevel) {
                jQuery(ExamineQuote).addClass('QuoteFolded').hide();
                jQuery(ExamineQuote).before('<div><a href="" class="QuoteFolding">&raquo; show previous quotes</a></div>');
                return;
            }

            var FoldQuote = jQuery(ExamineQuote).children('.QuoteText').children('.UserQuote');
            if (!FoldQuote.length) return;

            Quotes.ExploreFold(FoldQuote, FoldingLevel + 1, MaxLevel, TargetLevel);
        });
    }

    Gdn_Quotes.prototype.SetInsertMode = function(InsertMode, ChangeElement) {
        var OldInsert = this.InsertMode;
        var Changed = (OldInsert == InsertMode);
        this.InsertMode = InsertMode;

        switch (this.InsertMode) {
            case 'cleditor':

                var Frame = jQuery(jQuery(ChangeElement).find('textarea.TextBox').get(0).editor.$frame).get(0);
                var FrameBody = null;
                var FrameDocument = null;

                // DOM
                if (Frame.contentDocument) {
                    FrameDocument = Frame.contentDocument;
                    FrameBody = FrameDocument.body;
                    // IE
                } else if (Frame.contentWindow) {
                    FrameDocument = Frame.contentWindow.document;
                    FrameBody = FrameDocument.body;
                }

                if (FrameBody == null) return;

                /*
                 console.log(FrameDocument.getElementsByTagName('head')[0]);

                 // make a new stylesheet
                 var NewStyle = FrameDocument.createElement('style');
                 FrameDocument.getElementsByTagName('head')[0].appendChild(NewStyle);

                 // Safari does not see the new stylesheet unless you append something.
                 // However!  IE will blow chunks, so ... filter it thusly:
                 if (!window.createPopup) {
                 console.log('appending');
                 NewStyle.appendChild(FrameDocument.createTextNode(''));
                 }

                 var Style = FrameDocument.styleSheets[FrameDocument.styleSheets.length - 1];
                 console.log(Style);
                 // some rules to apply
                 var Rules = {
                 "blockquote" : "{ color: red; padding: 5px; }"
                 }

                 // loop through and insert
                 for (Selector in Rules) {
                 if (Style.insertRule) {
                 // it's an IE browser
                 try {
                 console.log('insertrule');
                 Style.insertRule(Selector + Rules[Selector], 0);
                 } catch(e) { console.log(e); }
                 } else {
                 // it's a W3C browser
                 try {
                 console.log('addrule');
                 Style.addRule(Selector, Rules[Selector]);
                 } catch(e) { console.log(e); }
                 }
                 }
                 */
//				var webRoot = gdn.definition('WebRoot', '');
//            var ss = document.createElement("link");
//            ss.type = "text/css";
//            ss.rel = "stylesheet";
//            ss.href = gdn.combinePaths(webRoot, '/plugins/Quotes/css/cleditor.css');
//
//            if (document.all)
//            	FrameDocument.createStyleSheet(ss.href);
//            else
//            	FrameDocument.getElementsByTagName("head")[0].appendChild(ss);

                break;

            case 'default':
            default:
                // Nothing for now
                break;
        }
    }

    Gdn_Quotes.prototype.GetObjectID = function(Anchor) {
        return jQuery(Anchor).attr('href').split('/').pop();
    }

    Gdn_Quotes.prototype.EditorStack = function(AreaContainer, Remove) {
        if (AreaContainer == undefined) return false;

        var TextArea = null;
        if (jQuery(AreaContainer).get(0).nodeName.toLowerCase() == 'textarea')
            TextArea = jQuery(AreaContainer);
        else {
            TextArea = jQuery(AreaContainer).find('textarea.TextBox');
            if (TextArea.length == 0) return false;
        }

        if (Remove == undefined || Remove == false) {
            // Add an editor
            if (TextArea.length) {
                TextArea.get(0).eid = this.EditorID++;
                this.Editors.push(TextArea);
            }
        } else {
            var EID = TextArea.get(0).eid;

            // Get rid of an editor
            jQuery(this.Editors).each(jQuery.proxy(function(i, el) {
                if (el.get(0).eid == EID) {
                    this.Editors.splice(i, 1);
                    return;
                }
            }, this));
        }

        return true;
    }

    Gdn_Quotes.prototype.GetEditor = function() {
        return this.Editors[this.Editors.length - 1];
    }

    Gdn_Quotes.prototype.Quote = function(ObjectID, QuoteLink) {
        var QuotingStatus = this.GetQuoteData(ObjectID);
        if (!QuotingStatus) return;

        switch (this.InsertMode) {
            case 'cleditor':
                var ScrollY = jQuery(this.GetEditor().get(0).editor.$frame).offset().top - 100; // 100 provides buffer in viewport
                break;

            case 'default':
            default:
                var ScrollY = this.GetEditor().offset().top - 100; // 100 provides buffer in viewport
                break;
        }

        jQuery('html,body').animate({scrollTop: ScrollY}, 800);
    }

    Gdn_Quotes.prototype.GetQuoteData = function(ObjectID) {
        var QuotedElement = jQuery('#' + ObjectID);
        if (!QuotedElement) return false;

        this.AddSpinner();
        var QuotebackURL = gdn.url('/discussion/getquote/' + ObjectID);
        jQuery.ajax({
            url: QuotebackURL,
            data: {format: jQuery('#Form_Format').val()},
            type: 'GET',
            dataType: 'json',
            success: jQuery.proxy(this.QuoteResponse, this)
        });
        return true;
    }

    Gdn_Quotes.prototype.AddSpinner = function() {

    }

    Gdn_Quotes.prototype.RemoveSpinner = function() {

    }

    Gdn_Quotes.prototype.QuoteResponse = function(Data, Status, XHR) {
        gdn.inform(Data);

        if (Data && Data.Quote.selector) {
            var ObjectID = Data.Quote.selector;
            this.RemoveSpinner();
        } else {
            return;
        }

        this.ApplyQuoteText(Data.Quote.body);
    }

    Gdn_Quotes.prototype.ApplyQuoteText = function(QuoteText) {
        var Editor = this.GetEditor();

        // First try and throw an event.
        var r = jQuery(Editor).trigger('appendHtml', QuoteText + "<br />");

        QuoteText = QuoteText + "\n";
        Editor.val(Editor.val() + QuoteText);

        switch (this.InsertMode) {
            case 'cleditor':
                Editor.val(Editor.val() + "<br/>");
                Editor.get(0).editor.updateFrame();
                break;

            case 'default':
            default:
                // Do nothing special
                break;
        }

        jQuery(Editor).trigger('autosize.resize');
    }
}

var GdnQuotes = null;
jQuery(document).ready(function() {
    GdnQuotes = new Gdn_Quotes();
    GdnQuotes.Prepare()
});
