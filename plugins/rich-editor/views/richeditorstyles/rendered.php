<?php

$inlineOperations = '[{"insert":"Quasar rich in mystery Apollonius of Perga concept of the number one rich in mystery! Apollonius of Perga, rogue, hearts of the stars, brain is the seed of intelligence dispassionate extraterrestrial observer finite but unbounded. Tingling of the spine kindling the energy hidden in matter gathered by gravity science Apollonius of Perga Euclid cosmic fugue gathered by gravity take root and flourish dream of the mind\'s eye descended from astronomers ship of the imagination vastness is bearable only through love with pretty stories for which there\'s little good evidence Orion\'s sword. Trillion a billion trillion Apollonius of Perga, not a sunrise but a galaxyrise the sky calls to us! Descended from astronomers?\n"},{"attributes":{"code-inline":true},"insert":"Code Inline"},{"insert":"\n"},{"attributes":{"bold":true},"insert":"Bold"},{"insert":"\n"},{"attributes":{"italic":true},"insert":"italic"},{"insert":"\n"},{"attributes":{"italic":true,"bold":true},"insert":"bold italic"},{"insert":"\n"},{"attributes":{"strike":true,"italic":true,"bold":true},"insert":"bold italic strike"},{"insert":"\n"},{"attributes":{"strike":true,"italic":true,"bold":true,"link":"http://test.com"},"insert":"bold italic strike link"}]';

$blockOperations = '[
    { "insert": "Block operations H1 Title here. Code Block next." },
    { "attributes": { "header": 1 }, "insert": "\n" },
    { "insert": "/** " },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": " *adds locale data to the view, and adds a respond button to the discussion page." },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": " */" },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": "class MyThemeNameThemeHooks extends Gdn_Plugin {" },
    { "attributes": { "code-block": true }, "insert": "\n\n" },
    { "insert": "    /**" },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": "     * Fetches the current locale and sets the data for the theme view." },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": "     * Render the locale in a smarty template using {$locale}" },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": "     *" },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": "     * @param  Controller $sender The sending controller object." },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": "     */" },
    { "attributes": { "code-block": true }, "insert": "\n" },
    {
        "insert":
            "    public function base_render_beforebase_render_beforebase_render_beforebase_render_beforebase_render_before($sender) {"
    },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": "        // Bail out if we\'re in the dashboard" },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": "        if (inSection(\'Dashboard\')) {" },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": "            return;" },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": "        }" },
    { "attributes": { "code-block": true }, "insert": "\n\n" },
    { "insert": "        // Fetch the currently enabled locale (en by default)" },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": "        $locale = Gdn::locale()->current();" },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": "        $sender->setData(\'locale\', $locale);" },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": "    }" },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": "}" },
    { "attributes": { "code-block": true }, "insert": "\n" },
    { "insert": "\nH2 Here. Spoiler next" },
    { "attributes": { "header": 2 }, "insert": "\n" },
    { "insert": "Some Spoiler content with formatting " },
    { "attributes": { "bold": true }, "insert": "bold" },
    { "insert": " " },
    { "attributes": { "italic": true }, "insert": "italic " },
    { "attributes": { "strike": true }, "insert": "strike" },
    { "attributes": { "spoiler-line": true }, "insert": "\n\n\n" },
    { "insert": "Newlines above " },
    { "attributes": { "link": "test link" }, "insert": "Link" },
    { "attributes": { "spoiler-line": true }, "insert": "\n" },
    { "insert": "Another line" },
    { "attributes": { "spoiler-line": true }, "insert": "\n" },
    { "insert": "\nA blockquote will be next.\n\nSome Block quote content" },
    { "attributes": { "bold": true }, "insert": "bold" },
    { "insert": " " },
    { "attributes": { "italic": true }, "insert": "italic " },
    { "attributes": { "strike": true }, "insert": "strike" },
    { "attributes": { "blockquote-line": true }, "insert": "\n" },
    { "attributes": { "strike": true }, "insert": "More blockquote content" },
    { "attributes": { "blockquote-line": true }, "insert": "\n" },
    { "insert": "\n\n" }
]'
;

$embedOperations = '[{"insert":{"embed-image":{"url":"https://images.pexels.com/photos/31459/pexels-photo.jpg?w=1260&h=750&dpr=2&auto=compress&cs=tinysrgb","alt":"Some Alt Text"}}},{"insert":"Video Embed"},{"attributes":{"header":2},"insert":"\n"},{"insert":{"embed-video":{"photoUrl":"https://i.ytimg.com/vi/wupToqz1e2g/hqdefault.jpg","url":"https://www.youtube.com/embed/wupToqz1e2g","name":"Video Title","width":1858,"height":1276,"simplifiedRatio":{"numerator":638,"denominator":929,"shorthand":"929:638"}}}},{"insert":"Internal Link Embed"},{"attributes":{"header":2},"insert":"\n"},{"insert":{"embed-link":{"url":"https://www.google.ca/","userPhoto":"https://secure.gravatar.com/avatar/b0420af06d6fecc16fc88a88cbea8218/","name":"Hulk attacks New York, kills 17, injures 23 in deadliest attack in 5 years   Hulk attacks New York, kills 17, injures 23 in deadliest attack in 5 years","userName":"steve_captain_rogers","timestamp":"2017-02-17 11:13","humanTime":"Feb 17, 2017 11:13 AM","excerpt":"The Battle of New York, locally known as \"The Incident\", was a major battle between the Avengers and Loki with his borrowed Chitauri army in Manhattan, New York City. It was, according to Loki\'s plan, the first battle in Loki\'s war to subjugate Earth, but the actions of the Avengers neutralized the threat of the Chitauri before they could continue the invasion."}}},{"insert":"External Link Embed With Image"},{"attributes":{"header":2},"insert":"\n"},{"insert":{"embed-link":{"url":"https://www.google.ca/","name":"Hulk attacks New York, kills 17, injures 23 in deadliest attack in 5 years   Hulk attacks New York, kills 17, injures 23 in deadliest attack in 5 years","source":"nytimes.com","linkImage":"https://cdn.mdn.mozilla.net/static/img/opengraph-logo.72382e605ce3.png","excerpt":"The Battle of New York, locally known as \"The Incident\", was a major battle between the Avengers and Loki with his borrowed Chitauri army in Manhattan, New York City. It was, according to Loki\'s plan, the first battle in Loki\'s war to subjugate Earth, but the actions of the Avengers neutralized the threat of the Chitauri before they could continue the invasion."}}},{"insert":"External Link Embed "},{"attributes":{"header":2},"insert":"\n"},{"insert":{"embed-link":{"url":"https://www.google.ca/","name":"Hulk attacks New York, kills 17, injures 23 in deadliest attack in 5 years   Hulk attacks New York, kills 17, injures 23 in deadliest attack in 5 years","source":"nytimes.com","excerpt":"The Battle of New York, locally known as \"The Incident\", was a major battle between the Avengers and Loki with his borrowed Chitauri army in Manhattan, New York City. It was, according to Loki\'s plan, the first battle in Loki\'s war to subjugate Earth, but the actions of the Avengers neutralized the threat of the Chitauri before they could continue the invasion."}}},{"insert":"\n\n"}]';

echo "<div class='Item-Body'><div class='Message userContent'>";
echo "<h1>Inline operations</h1>";
echo Gdn_Format::rich($inlineOperations);
echo "<hr>";
echo "<h1>Block operations</h1>";
echo Gdn_Format::rich($blockOperations);
echo "<hr>";
echo "<h1>Embed operations</h1>";
echo Gdn_Format::rich($embedOperations);
echo "</div></div>";
