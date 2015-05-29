(function() {
    (function($) {
        var Wysihtml5SizeMatters;

        Wysihtml5SizeMatters = (function() {
            function Wysihtml5SizeMatters(iframe) {
                this.$iframe = $(iframe);
                this.$body = this.findBody();

                // In 0.4.0pre the body inside the iframe has 100% height, while the
                // previous versions do not. This causes the editor autogrow to break.
                this.$body.css({height: "auto"}).closest('html').css({height: "auto"});

                this.addBodyStyles();
                this.setupEvents();
                this.adjustHeight();
            }

            Wysihtml5SizeMatters.prototype.addBodyStyles = function() {
                this.$body.css('overflow', 'hidden');
                return this.$body.css('min-height', 0);
            };

            Wysihtml5SizeMatters.prototype.setupEvents = function() {
                var _this = this;

                return this.$body.on('keyup keydown keypress paste change focus focusin blur hover mouseover mouseenter mouseout select', function() {
                    return _this.adjustHeight();
                });
            };

            Wysihtml5SizeMatters.prototype.adjustHeight = function() {
                var height = this.$body.outerHeight() + this.extraBottomSpacing();

                if (this.$iframe.css('box-sizing') == 'border-box') {
                    height += parseInt(this.$iframe.css('padding-top')) + parseInt(this.$iframe.css('padding-bottom'));
                }

                // Problem with autogrow, when padding set to iframe instead of body.
                // The content within composer eventually creeps out of view, so
                // instead of having the padding on the iframe, set it to body.
                var textareaTemplate = this.$iframe.closest('form').find('textarea')[0];
                var padding = wysihtml5.dom.getStyle("padding-top").from(textareaTemplate)
                this.$iframe.css('padding', '0px');
                this.$body.css('padding', padding);

                return this.$iframe.css('min-height', height);
            };

            Wysihtml5SizeMatters.prototype.extraBottomSpacing = function() {
                return parseInt(this.$body.css('line-height')) || this.estimateLineHeight();
            };

            Wysihtml5SizeMatters.prototype.estimateLineHeight = function() {
                return parseInt(this.$body.css('font-size')) * 1.14;
            };

            Wysihtml5SizeMatters.prototype.findBody = function() {
                return this.$iframe.contents().find('body');
            };

            return Wysihtml5SizeMatters;

        })();
        return $.fn.wysihtml5_size_matters = function() {
            return this.each(function() {
                var wysihtml5_size_matters;

                wysihtml5_size_matters = $.data(this, 'wysihtml5_size_matters');
                if (!wysihtml5_size_matters) {
                    return wysihtml5_size_matters = $.data(this, 'wysihtml5_size_matters', new Wysihtml5SizeMatters(this));
                }
            });
        };
    })($);

}).call(this);
