<?php

use Vanilla\Formatting\Formats\RichFormat;

$inlineOperations = '[
    {
        "insert": "Quasar rich in mystery Apollonius of Perga concept of the number one rich in mystery! Apollonius of Perga, rogue, hearts of the stars, brain is the seed of intelligence dispassionate extraterrestrial observer finite but unbounded. Tingling of the spine kindling the energy hidden in matter gathered by gravity science Apollonius of Perga Euclid cosmic fugue gathered by gravity take root and flourish dream of the mind\'s eye descended from astronomers ship of the imagination vastness is bearable only through love with pretty stories for which there\'s little good evidence Orion\'s sword. Trillion a billion trillion Apollonius of Perga, not a sunrise but a galaxyrise the sky calls to us! Descended from astronomers?\n"
    },
    {
        "attributes": {
            "codeInline": true
        },
        "insert": "Code Inline"
    },
    {
        "insert": "\n"
    },
    {
        "attributes": {
            "bold": true
        },
        "insert": "Bold"
    },
    {
        "insert": "\n"
    },
    {
        "attributes": {
            "italic": true
        },
        "insert": "italic"
    },
    {
        "insert": "\n"
    },
    {
        "attributes": {
            "italic": true,
            "bold": true
        },
        "insert": "bold italic"
    },
    {
        "insert": "\n"
    },
    {
        "attributes": {
            "strike": true,
            "italic": true,
            "bold": true
        },
        "insert": "bold italic strike"
    },
    {
        "insert": "\n"
    },
    {
        "attributes": {
            "strike": true,
            "italic": true,
            "bold": true,
            "link": "http://test.com"
        },
        "insert": "bold italic strike link"
    },
    {
        "insert": "\nSome text with a mention in it "
    },
    {
        "insert": {
            "mention": {
                "name": "Alex Other Name",
                "userID": 23
            }
        }
    },
    {
        "insert": " Another mention "
    },
    {
        "insert": {
            "mention": {
                "name": "System",
                "userID": 1
            }
        }
    },
    {
        "insert": ".\nSome text with emojis"
    },
    {
        "insert": {
            "emoji": {
                "emojiChar": "🤗"
            }
        }
    },
    {
        "insert": {
            "emoji": {
                "emojiChar": "🤔"
            }
        }
    },
    {
        "insert": {
            "emoji": {
                "emojiChar": "🤣"
            }
        }
    },
    {
        "insert": ".\n"
    }
]
';

$blockOperations = '[
    { "insert": "Block operations H1 Title here. Code Block next." },
    { "attributes": { "header": 1 }, "insert": "\n" },
    { "insert": "/** " },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": " *adds locale data to the view, and adds a respond button to the discussion page." },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": " */" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "class MyThemeNameThemeHooks extends Gdn_Plugin {" },
    { "attributes": { "codeBlock": true }, "insert": "\n\n" },
    { "insert": "    /**" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "     * Fetches the current locale and sets the data for the theme view." },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "     * Render the locale in a smarty template using {$locale}" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "     *" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "     * @param  Controller $sender The sending controller object." },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "     */" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    {
        "insert":
            "    public function base_render_beforebase_render_beforebase_render_beforebase_render_beforebase_render_before($sender) {"
    },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "        // Bail out if we\'re in the dashboard" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "        if (inSection(\'Dashboard\')) {" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "            return;" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "        }" },
    { "attributes": { "codeBlock": true }, "insert": "\n\n" },
    { "insert": "        // Fetch the currently enabled locale (en by default)" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "        $locale = Gdn::locale()->current();" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "        $sender->setData(\'locale\', $locale);" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "    }" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "}" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "\nH2 Here. Spoiler next" },
    { "attributes": { "header": 3 }, "insert": "\n" },
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
    { "insert": "\n\n" },
    { "insert": "Unordered List\nLine 1" },
    { "attributes": { "list": "bullet" }, "insert": "\n" },
    { "insert": "Line 2 (2 empty list items after this)" },
    { "attributes": { "list": "bullet" }, "insert": "\n\n\n" },
    { "insert": "Line 5 item with " },
    { "attributes": { "bold": true }, "insert": "bold and a " },
    { "attributes": { "bold": true, "link": "https://vanillaforums.com" }, "insert": "link" },
    { "attributes": { "bold": true }, "insert": "." },
    { "attributes": { "list": "bullet" }, "insert": "\n" },
    { "insert": "Line 6 item with an emoji" },
    { "insert": { "emoji": { "emojiChar": "😉" } } },
    { "insert": "." },
    { "attributes": { "list": "bullet" }, "insert": "\n" },
    { "insert": "Ordered List\nNumber 1" },
    { "attributes": { "list": "ordered" }, "insert": "\n" },
    { "insert": "Number 2" },
    { "attributes": { "list": "ordered" }, "insert": "\n" },
    { "insert": "Number 3 (Empty line below)" },
    { "attributes": { "list": "ordered" }, "insert": "\n\n" },
    { "insert": "Number 5 with " },
    { "attributes": { "bold": true }, "insert": "bold and a " },
    { "attributes": { "bold": true, "link": "https://vanillaforums.com/" }, "insert": "link" },
    { "attributes": { "bold": true }, "insert": "." },
    { "attributes": { "list": "ordered" }, "insert": "\n" },
    { "insert": "\n" }
]';

echo "<div class='Item-Body'><div class='Message userContent'>";
echo "<h2>Inline operations</h2>";
echo Gdn::formatService()->renderHTML($inlineOperations, RichFormat::FORMAT_KEY);
echo "<hr>";
echo "<h2>Block operations</h2>";
echo Gdn::formatService()->renderHTML($blockOperations, RichFormat::FORMAT_KEY);
echo "
<h2>Spacer</h2>
<p>
<strong>This text is here to add some space for testing scroll position</strong>
</p>
<p>
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent nec risus a erat fermentum posuere quis ut mi. Donec luctus lacinia augue fringilla sodales. Nullam pharetra auctor tellus tincidunt luctus. Mauris sed maximus eros. Donec dictum, ante ac convallis consectetur, metus tortor accumsan lorem, in sagittis augue ligula et sapien. Donec vulputate euismod elit sit amet ultricies. Nullam sit amet rhoncus mauris, ac sodales velit.
</p>
<h2 id='scroll'>Scrollable heading. Go to <a href='#scroll'>#scroll</a></h2>
<p>
Aliquam egestas nulla ipsum, tempor pellentesque urna finibus id. Cras lacinia posuere quam vitae congue. Phasellus eget odio tincidunt, posuere dui at, ultrices ante. Praesent pharetra rutrum faucibus. Donec in lobortis urna, et lobortis enim. Interdum et malesuada fames ac ante ipsum primis in faucibus. Donec consequat justo id condimentum venenatis. Vestibulum mattis blandit leo, nec viverra ante molestie at. Suspendisse vel erat et nisi scelerisque volutpat ut eget urna. Morbi pulvinar posuere nisl.
</p>
<p>
Sed maximus in nisl lacinia scelerisque. Aliquam gravida, ligula ut varius feugiat, purus tellus faucibus nibh, ut scelerisque dolor velit gravida justo. Pellentesque accumsan velit sed rutrum imperdiet. Fusce vulputate enim sed felis ornare, et feugiat risus varius. Nam nibh massa, sodales sed lorem eu, rhoncus laoreet nibh. Nullam eu urna erat. Curabitur consectetur interdum libero, ut facilisis tellus vulputate id. Ut mollis dolor id rutrum aliquam. Aliquam id auctor velit, a efficitur nunc. Curabitur mollis dui non efficitur volutpat. Vestibulum laoreet iaculis congue. Duis laoreet quam eu justo ullamcorper finibus.
</p>
<p>
Sed feugiat varius vehicula. Integer dignissim at eros non fermentum. Vestibulum venenatis, purus a rhoncus suscipit, libero est euismod orci, vitae suscipit ligula felis non quam. Pellentesque vel interdum odio. Aenean vel est mattis, consectetur neque et, vestibulum nisi. Maecenas at imperdiet est. Sed fermentum ipsum condimentum ex lacinia, vitae accumsan massa sagittis. Aenean vel tortor leo. Suspendisse ut augue justo. Nullam arcu nunc, varius et porttitor in, pulvinar sed ex. Integer tristique vehicula nunc, vitae dapibus tellus interdum ut. Pellentesque auctor ex a molestie ultrices. Nulla sed diam purus. Aenean eu purus pellentesque, consequat mauris eget, rutrum sapien. In sed magna magna.
</p>
<p>
Aenean fringilla tortor tellus, in elementum ligula ornare quis. Nam maximus vitae nibh at gravida. Vivamus eget magna leo. Integer rhoncus in tortor eget commodo. Quisque a magna in lectus malesuada dapibus ut quis quam. Praesent accumsan, justo et ornare ultricies, massa ex tincidunt arcu, sed volutpat orci nibh vel tortor. Vestibulum id sodales magna, at iaculis metus. Ut vel mauris enim. Sed molestie metus a molestie fermentum.
</p>
</div>
";
