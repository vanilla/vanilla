<?php

$inlineOperations = '[
    {
        "insert":
            "Quasar rich in mystery Apollonius of Perga concept of the number one rich in mystery! Apollonius of Perga, rogue, hearts of the stars, brain is the seed of intelligence dispassionate extraterrestrial observer finite but unbounded. Tingling of the spine kindling the energy hidden in matter gathered by gravity science Apollonius of Perga Euclid cosmic fugue gathered by gravity take root and flourish dream of the mind\'s eye descended from astronomers ship of the imagination vastness is bearable only through love with pretty stories for which there\'s little good evidence Orion\'s sword. Trillion a billion trillion Apollonius of Perga, not a sunrise but a galaxyrise the sky calls to us! Descended from astronomers?\n"
    },
    { "attributes": { "code-inline": true }, "insert": "Code Inline" },
    { "insert": "\n" },
    { "attributes": { "bold": true }, "insert": "Bold" },
    { "insert": "\n" },
    { "attributes": { "italic": true }, "insert": "italic" },
    { "insert": "\n" },
    { "attributes": { "italic": true, "bold": true }, "insert": "bold italic" },
    { "insert": "\n" },
    { "attributes": { "strike": true, "italic": true, "bold": true }, "insert": "bold italic strike" },
    { "insert": "\n" },
    {
        "attributes": { "strike": true, "italic": true, "bold": true, "link": "http://test.com" },
        "insert": "bold italic strike link"
    }
]
';

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

$embedOperations = '[
    { "insert": "### Imgur: \n\n\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://imgur.com/gallery/arP2Otg",
                    "type": "imgur",
                    "name": null,
                    "body": null,
                    "photoUrl": null,
                    "height": null,
                    "width": null,
                    "attributes": { "postID": "arP2Otg", "isAlbum": false }
                },
                "loaderData": { "type": "link", "link": "https://imgur.com/gallery/arP2Otg", "loaded": true }
            }
        }
    },
    { "insert": "\n\n### Image:\n\n\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url":
                        "http://www.worldoceansday.org/_assets/css/images/events/8075118_2_IMG_1262CoastOceanSky.jpg",
                    "type": "image",
                    "name": null,
                    "body": null,
                    "photoUrl":
                        "http://www.worldoceansday.org/_assets/css/images/events/8075118_2_IMG_1262CoastOceanSky.jpg",
                    "height": 3787,
                    "width": 5809,
                    "attributes": []
                },
                "loaderData": {
                    "type": "link",
                    "link":
                        "http://www.worldoceansday.org/_assets/css/images/events/8075118_2_IMG_1262CoastOceanSky.jpg",
                    "loaded": true
                }
            }
        }
    },
    { "insert": "\n\n### Twitter:\n\n\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://twitter.com/hootsuite/status/1009883861617135617",
                    "type": "twitter",
                    "name": null,
                    "body": null,
                    "photoUrl": null,
                    "height": null,
                    "width": null,
                    "attributes": { "statusID": "1009883861617135617" }
                },
                "loaderData": {
                    "type": "link",
                    "link": "https://twitter.com/hootsuite/status/1009883861617135617",
                    "loaded": true
                }
            }
        }
    },
    { "insert": "\n\n### Getty:\n\n\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url":
                        "https://www.gettyimages.ca/detail/photo/explosion-of-a-cloud-of-powder-of-particles-of-royalty-free-image/810147408",
                    "type": "getty",
                    "name": null,
                    "body": null,
                    "photoUrl": null,
                    "height": 345,
                    "width": 498,
                    "attributes": {
                        "id": "yWNq9kdoT4JKdflMolsxPA",
                        "sig": "yJxnZRDVdZ5UfTK-mE8Qlk-DDPpI-SklyztO21KQSpk=",
                        "items": "810147408",
                        "isCaptioned": "false",
                        "is360": "false",
                        "tld": "com",
                        "postID": "810147408"
                    }
                },
                "loaderData": {
                    "type": "link",
                    "link":
                        "https://www.gettyimages.ca/detail/photo/explosion-of-a-cloud-of-powder-of-particles-of-royalty-free-image/810147408",
                    "loaded": true
                }
            }
        }
    },
    { "insert": "\n\n### Vimeo:\n\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://vimeo.com/ondemand/nature365/113009024",
                    "type": "vimeo",
                    "name": "Nature 365 - Monthly collections",
                    "body": null,
                    "photoUrl": "https://i.vimeocdn.com/video/498185229_295x166.jpg",
                    "height": 270,
                    "width": 480,
                    "attributes": { "thumbnail_width": 295, "thumbnail_height": 166 }
                },
                "loaderData": {
                    "type": "link",
                    "link": "https://vimeo.com/ondemand/nature365/113009024",
                    "loaded": true
                }
            }
        }
    },
    { "insert": "\n\n### Youtube:\n\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://www.youtube.com/watch?v=fy0fTFpqT48&t=2s",
                    "type": "youtube",
                    "name": "Attack of the Killer Tomatoes - Trailer",
                    "body": null,
                    "photoUrl": "https://i.ytimg.com/vi/fy0fTFpqT48/hqdefault.jpg",
                    "height": 344,
                    "width": 459,
                    "attributes": {
                        "thumbnail_width": 480,
                        "thumbnail_height": 360,
                        "videoID": "fy0fTFpqT48",
                        "start": 2,
                        "embedUrl": "https://www.youtube.com/embed/fy0fTFpqT48?feature=oembed&autoplay=1&start=2"
                    }
                },
                "loaderData": {
                    "type": "link",
                    "link": "https://www.youtube.com/watch?v=fy0fTFpqT48&t=2s",
                    "loaded": true
                }
            }
        }
    },
    { "insert": "\n\n### Instagram:\n\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://www.instagram.com/p/BTjnolqg4po/?taken-by=vanillaforums",
                    "type": "instagram",
                    "name": null,
                    "body": null,
                    "photoUrl": null,
                    "height": null,
                    "width": null,
                    "attributes": {
                        "permaLink": "https://www.instagram.com/p/BTjnolqg4po",
                        "isCaptioned": true,
                        "versionNumber": "8"
                    }
                },
                "loaderData": {
                    "type": "link",
                    "link": "https://www.instagram.com/p/BTjnolqg4po/?taken-by=vanillaforums",
                    "loaded": true
                }
            }
        }
    },
    { "insert": "\n\n### Soundcloud:\n\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://soundcloud.com/nymano/solitude",
                    "type": "soundcloud",
                    "name": null,
                    "body": null,
                    "photoUrl": null,
                    "height": 400,
                    "width": null,
                    "attributes": { "visual": "true", "showArtwork": "true", "track": "227042825" }
                },
                "loaderData": { "type": "link", "link": "https://soundcloud.com/nymano/solitude", "loaded": true }
            }
        }
    },
    { "insert": "\n\n### Giphy:\n\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://media.giphy.com/media/JIX9t2j0ZTN9S/giphy.gif",
                    "type": "giphy",
                    "name": "Funny Cat GIF - Find & Share on GIPHY",
                    "body": null,
                    "photoUrl": null,
                    "height": 720,
                    "width": 720,
                    "attributes": { "postID": "JIX9t2j0ZTN9S" }
                },
                "loaderData": {
                    "type": "link",
                    "link": "https://media.giphy.com/media/JIX9t2j0ZTN9S/giphy.gif",
                    "loaded": true
                }
            }
        }
    },
    { "insert": "\n\n### Twitch:\n\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://www.twitch.tv/videos/277077149",
                    "type": "twitch",
                    "name": "SamedWii Zeldaérobic",
                    "body": null,
                    "photoUrl":
                        "https://static-cdn.jtvnw.net/s3_vods/9e05228597e840e180f3_hoopyjv_29218011904_894795907/thumb/thumb0-640x360.jpg",
                    "height": 281,
                    "width": 500,
                    "attributes": { "videoID": "277077149", "embedUrl": "https://player.twitch.tv/?video=v277077149" }
                },
                "loaderData": { "type": "link", "link": "https://www.twitch.tv/videos/277077149", "loaded": true }
            }
        }
    },
    { "insert": "\n\n### Regular Image Embed:\n\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://www.google.ca/search?q=typing+google+in+google",
                    "type": "link",
                    "name": "typing google in google - Google Search",
                    "body": "typing google into google meme",
                    "photoUrl": null,
                    "height": null,
                    "width": null,
                    "attributes": []
                },
                "loaderData": {
                    "type": "link",
                    "link": "https://www.google.ca/search?q=typing+google+in+google",
                    "loaded": true
                }
            }
        }
    },
    { "insert": "\n ( no image)\n\n\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://vanillaforums.com/en/",
                    "type": "link",
                    "name": "Online Community Software and Customer Forum Software by Vanilla Forums",
                    "body":
                        "Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.",
                    "photoUrl": "https://vanillaforums.com/images/metaIcons/vanillaForums.png",
                    "height": null,
                    "width": null,
                    "attributes": []
                },
                "loaderData": { "type": "link", "link": "https://vanillaforums.com/en/", "loaded": true }
            }
        }
    },
    { "insert": "\n \n(with image)\n\n### Wistia:\n\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://dave.wistia.com/medias/0k5h1g1chs",
                    "type": "wistia",
                    "name": "Lenny Delivers a Video - oEmbed glitch",
                    "body": null,
                    "photoUrl":
                        "https://embed-ssl.wistia.com/deliveries/99f3aefb8d55eef2d16583886f610ebedd1c6734.jpg?image_crop_resized=960x540",
                    "height": 540,
                    "width": 960,
                    "attributes": {
                        "thumbnail_width": 960,
                        "thumbnail_height": 540,
                        "postID": "0k5h1g1chs",
                        "embedUrl": "https://fast.wistia.net/embed/iframe/0k5h1g1chs"
                    }
                },
                "loaderData": { "type": "link", "link": "https://dave.wistia.com/medias/0k5h1g1chs", "loaded": true }
            }
        }
    },
    { "insert": "\n\n\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://vanillaforums-1.wistia.com/medias/vjidqnyg0a",
                    "type": "wistia",
                    "name": "Borrowed video: Welcome to Wistia!",
                    "body": null,
                    "photoUrl":
                        "https://embed-ssl.wistia.com/deliveries/1e7b480521adb0d8cc29dbd388faa14eb7c99d21.jpg?image_crop_resized=960x540",
                    "height": 540,
                    "width": 960,
                    "attributes": {
                        "thumbnail_width": 960,
                        "thumbnail_height": 540,
                        "postID": "vjidqnyg0a",
                        "embedUrl": "https://fast.wistia.net/embed/iframe/vjidqnyg0a"
                    }
                },
                "loaderData": {
                    "type": "link",
                    "link": "https://vanillaforums-1.wistia.com/medias/vjidqnyg0a",
                    "loaded": true
                }
            }
        }
    },
    { "insert": "\n\n\n\n\n\n\n\n\n\n\n" }
]';

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
