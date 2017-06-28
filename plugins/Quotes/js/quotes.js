// Quotes object constructor
function Gdn_Quotes() {
    var currentEditor = null;
}

// Attach event handler for quotes on the page.
Gdn_Quotes.prototype.Prepare = function () {
    // Capture "this" for use in callbacks.
    var Quotes = this,
        $document = $(document);

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

    // Handle quote folding clicks.
    $document.on('click', 'a.QuoteFolding', function () {
        var Anchor = $(this),
            QuoteTarget = Anchor
                .parent()
                .next()
                .toggle();

        if (QuoteTarget.css('display') != 'none') {
            Anchor.html(gdn.definition('hide previous quotes'));
        } else {
            Anchor.html(gdn.definition('show previous quotes'));
        }

        return false;
    });
};

/**
 * Format the quotes within a given parent element.
 *
 * @param elem The parent element.
 */
Gdn_Quotes.prototype.format = function (elem) {
    // Handle quote folding.
    var QuoteFoldingLevel = parseInt(gdn.getMeta('QuotesFolding', 1)) + 1,
        Quotes = this,
        MaxFoldingLevel = 6;

    $('.Discussion .Message, .Comment .Message', elem).each(function () {
        // Find the closest child quote
        var Message = $(this),
            PetQuote = Message.children('.Quote, .UserQuote');

        if (Message.data('QuoteFolding') || !PetQuote.length) {
            return;
        }
        Message.data('QuoteFolding', '1');

        Quotes.ExploreFold(PetQuote, 1, MaxFoldingLevel, QuoteFoldingLevel);
    });
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
                    '<div class="QuoteFoldingWrapper"><a href="" class="QuoteFolding">' +
                    gdn.definition('show previous quotes') +
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

(function(window) {
    window.GdnQuotes = new Gdn_Quotes();
    window.GdnQuotes.Prepare();
})(window);

$(document).on('contentLoad', function (e) {
    GdnQuotes.format(e.target);
});
