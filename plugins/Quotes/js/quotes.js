// Quotes object constructor
function Gdn_Quotes() {
    var currentEditor = null;
}

// Attach event handler for quotes on the page.
Gdn_Quotes.prototype.Prepare = function () {
    // Capture "this" for use in callbacks.
    var Quotes = this,
        $document = $(document),
        QuoteFoldingLevel,
        MaxFoldingLevel;

    // Attach quote event to each Quote button, and return false to prevent link follow.
    $document.on('click', 'a.ReactButton.Quote', function(event) {
        var QuoteLink = $(event.target).closest('a'),
            ObjectID = QuoteLink.attr('href').split('/').pop();

        Quotes.Quote(ObjectID, QuoteLink);
        return false;
    });

    // Track active editor.
    $document.on('focus', 'textarea.TextBox', function () {
        Quotes.currentEditor = this;
    });

    // Handle quote folding.
    QuoteFoldingLevel = gdn.definition('QuotesFolding', 1);

    function folding() {
        $('.Discussion .Message, .Comment .Message').each(function () {
            // Find the closest child quote
            var Message = $(this),
                PetQuote = Message.children('.Quote, .UserQuote');

            if (Message.data('QuoteFolding') || !PetQuote.length) {
                return;
            }
            Message.data('QuoteFolding', '1');

            Quotes.ExploreFold(PetQuote, 1, MaxFoldingLevel, QuoteFoldingLevel);
        });
    }

    if (QuoteFoldingLevel != 'None') {
        QuoteFoldingLevel = parseInt(QuoteFoldingLevel) + 1;
        MaxFoldingLevel = 6;

        $document.on('CommentAdded CommentEditingComplete CommentPagingComplete', folding);
        folding();

        $document.on('click', 'a.QuoteFolding', function () {
            var Anchor = $(this),
                QuoteTarget = Anchor
                    .closest('.QuoteText')
                    .children('.Quote, .UserQuote')
                    .toggle();

            if (QuoteTarget.css('display') != 'none') {
                Anchor.html(gdn.definition('&laquo; hide previous quotes'));
            } else {
                Anchor.html(gdn.definition('&raquo; show previous quotes'));
            }

            return false;
        });
    }
};


// Recursively transform folded quotes.
Gdn_Quotes.prototype.ExploreFold = function(QuoteTree, FoldingLevel, MaxLevel, TargetLevel) {
    if (FoldingLevel > MaxLevel || FoldingLevel > TargetLevel) {
        return;
    }

    var Quotes = this;
    $(QuoteTree).each(function(i, el) {
        var ExamineQuote = $(el),
            FoldQuote;

        if (FoldingLevel == TargetLevel) {
            ExamineQuote
                .addClass('QuoteFolded')
                .hide()
                .before(
                    '<div><a href="" class="QuoteFolding">' +
                    gdn.definition('&raquo; show previous quotes') +
                    '</a></div>'
                );
            return;
        }

        FoldQuote = ExamineQuote.children('.QuoteText').children('.Quote, .UserQuote');
        if (!FoldQuote.length) {
            return;
        }

        Quotes.ExploreFold(FoldQuote, FoldingLevel + 1, MaxLevel, TargetLevel);
    });
};


// Get the currently active editor (last in focus).
Gdn_Quotes.prototype.GetEditor = function () {
    var editor = $(this.currentEditor);
    if (!document.body.contains(this.currentEditor) || !editor.length) {
        editor = $('textarea.TextBox').first();
    }

    return editor;
};


// Handle quote insertion clicks.
Gdn_Quotes.prototype.Quote = function(ObjectID, QuoteLink) {
    if (!this.GetQuoteData(ObjectID)) {
        return;
    }

    var ScrollY;

    // DEPRECATED: cleditor support
    if ($('div.cleditorMain').length) {
        ScrollY = $(this.GetEditor().get(0).editor.$frame).offset().top - 100;
        $('html,body').animate({scrollTop: ScrollY}, 800);
    }
};


// Request the quote data.
Gdn_Quotes.prototype.GetQuoteData = function(ObjectID) {
    this.AddSpinner();

    $.getJSON(
        gdn.url('/discussion/getquote/' + ObjectID),
        {format: $('#Form_Format').val()},
        $.proxy(this.QuoteResponse, this)
    );
    return true;
};


// Overridable function.
Gdn_Quotes.prototype.AddSpinner = function () {

};


// Overridable function.
Gdn_Quotes.prototype.RemoveSpinner = function () {

};


// Handle a successful request for quote data.
Gdn_Quotes.prototype.QuoteResponse = function(Data, Status, XHR) {
    gdn.inform(Data);

    if (Data && Data.Quote.selector) {
        this.RemoveSpinner();
    } else {
        return;
    }

    this.ApplyQuoteText(Data.Quote.body);
};


// Insert the quote text into the editor.
Gdn_Quotes.prototype.ApplyQuoteText = function(QuoteText) {
    var Editor = this.GetEditor();

    // First try and throw an event.
    Editor.trigger('appendHtml', QuoteText + '<br />');

    QuoteText = QuoteText + '\n';
    Editor.val(Editor.val() + QuoteText);

    // DEPRECATED: cleditor support
    if ($('div.cleditorMain').length) {
        Editor.val(Editor.val() + '<br/>');
        Editor.get(0).editor.updateFrame();
    }

    Editor
        .focus()
        .trigger('autosize.resize');
};


// Instance for global access.
var GdnQuotes = null;

// document.ready
$(function () {
    GdnQuotes = new Gdn_Quotes();
    GdnQuotes.Prepare();
});
