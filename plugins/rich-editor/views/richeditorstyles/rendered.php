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
]'
;

$embedOperations = '[
    { "insert": "Imgur:" },
    { "attributes": { "header": 3 }, "insert": "\n" },
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
    { "insert": "Image:" },
    { "attributes": { "header": 3 }, "insert": "\n" },
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
    { "insert": "Twitter:" },
    { "attributes": { "header": 3 }, "insert": "\n" },
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
    { "insert": "Getty:" },
    { "attributes": { "header": 3 }, "insert": "\n" },
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
                        "id": "VPkxdgtCQFx-rEo96WtR_g",
                        "sig": "Mb27fqjaYbaPPFANi1BffcYTEvCcNHg0My7qzCNDSHo=",
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
    { "insert": "Vimeo:" },
    { "attributes": { "header": 3 }, "insert": "\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://vimeo.com/264197456",
                    "type": "vimeo",
                    "name": "Vimeo",
                    "body": null,
                    "photoUrl": "https://i.vimeocdn.com/video/694532899_640.jpg",
                    "height": 272,
                    "width": 640,
                    "attributes": {
                        "thumbnail_width": 640,
                        "thumbnail_height": 272,
                        "videoID": "264197456",
                        "embedUrl": "https://player.vimeo.com/video/264197456?autoplay=1"
                    }
                },
                "loaderData": {
                    "type": "link",
                    "link": "https://vimeo.com/264197456",
                    "loaded": true
                }
            }
        }
    },
    { "insert": "Youtube:" },
    { "attributes": { "header": 3 }, "insert": "\n" },
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
    { "insert": "Instagram:" },
    { "attributes": { "header": 3 }, "insert": "\n" },
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
    { "insert": "Soundcloud:" },
    { "attributes": { "header": 3 }, "insert": "\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://soundcloud.com/uiceheidd/sets/juicewrld-the-mixtape",
                    "type": "soundcloud",
                    "name": null,
                    "body": null,
                    "photoUrl": null,
                    "height": 450,
                    "width": null,
                    "attributes": {
                        "visual": "true",
                        "showArtwork": "true",
                        "postID": "330864225",
                        "embedUrl": "https://w.soundcloud.com/player/?url=https://api.soundcloud.com/playlists/"
                    }
                },
                "loaderData": {
                    "type": "link",
                    "link": "https://soundcloud.com/uiceheidd/sets/juicewrld-the-mixtape",
                    "loaded": true
                }
            }
        }
    },
    { "insert": "Giphy:" },
    { "attributes": { "header": 3 }, "insert": "\n" },
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
    { "insert": "Twitch:" },
    { "attributes": { "header": 3 }, "insert": "\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "http://clips.twitch.tv/KnottyOddFishShazBotstix",
                    "type": "twitch",
                    "name": "Lights! Camera! Action!",
                    "body": null,
                    "photoUrl": "https://clips-media-assets2.twitch.tv/AT-cm%7C267415465-preview.jpg",
                    "height": 351,
                    "width": 620,
                    "attributes": {
                        "videoID": "KnottyOddFishShazBotstix",
                        "embedUrl": "https://clips.twitch.tv/embed?clip=KnottyOddFishShazBotstix"
                    }
                },
                "loaderData": {
                    "type": "link",
                    "link": "http://clips.twitch.tv/KnottyOddFishShazBotstix"
                }
            }
        }
    },
    { "insert": "External No Image" },
    { "attributes": { "header": 3 }, "insert": "\n" },
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
    { "insert": "Exernal With Image" },
    { "attributes": { "header": 3 }, "insert": "\n" },
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
    { "insert": "Wistia:" },
    { "attributes": { "header": 3 }, "insert": "\n" },
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
    { "insert": "" },
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
    { "insert": "CodePen:" },
    { "attributes": { "header": 3 }, "insert": "\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://codepen.io/hiroshi_m/pen/YoKYVv",
                    "type": "codepen",
                    "name": null,
                    "body": null,
                    "photoUrl": null,
                    "height": 300,
                    "width": null,
                    "attributes": {
                        "id": "cp_embed_YoKYVv",
                        "embedUrl": "https://codepen.io/hiroshi_m/embed/preview/YoKYVv?theme-id=0",
                        "style": { "width": " 100%", "overflow": "hidden" }
                    }
                },
                "loaderData": { "type": "link", "link": "https://codepen.io/hiroshi_m/pen/YoKYVv" }
            }
        }
    },
    { "insert": "File Upload" },
    { "attributes": { "header": 3 }, "insert": "\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://dev.vanilla.localhost/uploads/150/LKE0S2FWLFUP.zip",
                    "type": "file",
                    "attributes": {
                        "mediaID": 62,
                        "name": "___img_onload_prompt(1)_ (2).zip",
                        "path": "150/LKE0S2FWLFUP.zip",
                        "type": "application/zip",
                        "size": 41,
                        "active": 1,
                        "insertUserID": 4,
                        "dateInserted": "2019-06-14 14:09:38",
                        "foreignID": 4,
                        "foreignTable": "embed",
                        "imageWidth": null,
                        "imageHeight": null,
                        "thumbWidth": null,
                        "thumbHeight": null,
                        "thumbPath": null,
                        "foreignType": "embed",
                        "url": "https://dev.vanilla.localhost/uploads/150/LKE0S2FWLFUP.zip"
                    }
                },
                "loaderData": {
                    "type": "file",
                    "file": [],
                    "progressEventEmitter": {
                        "listeners": [
                            null
                        ]
                    }
                }
            }
        }
    }
]';

$quoteEmbeds = '
[
    { "insert": "Discussion Quote" },
    { "attributes": { "header": 3 }, "insert": "\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://dev.vanilla.localhost/discussion/8/test-file-upload",
                    "type": "quote",
                    "name": null,
                    "body": null,
                    "photoUrl": null,
                    "height": null,
                    "width": null,
                    "attributes": {
                        "discussionID": 8,
                        "name": "test file upload",
                        "bodyRaw": [
                            {
                                "insert": {
                                    "embed-external": {
                                        "data": {
                                            "url": "https://dev.vanilla.localhost/uploads/150/LKE0S2FWLFUP.zip",
                                            "type": "file",
                                            "attributes": {
                                                "mediaID": 62,
                                                "name": "___img_onload_prompt(1)_ (2).zip",
                                                "path": "150/LKE0S2FWLFUP.zip",
                                                "type": "application/zip",
                                                "size": 41,
                                                "active": 1,
                                                "insertUserID": 4,
                                                "dateInserted": "2019-06-14 14:09:38",
                                                "foreignID": 4,
                                                "foreignTable": "embed",
                                                "imageWidth": null,
                                                "imageHeight": null,
                                                "thumbWidth": null,
                                                "thumbHeight": null,
                                                "thumbPath": null,
                                                "foreignType": "embed",
                                                "url": "https://dev.vanilla.localhost/uploads/150/LKE0S2FWLFUP.zip"
                                            }
                                        },
                                        "loaderData": {
                                            "type": "file",
                                            "file": [],
                                            "progressEventEmitter": {
                                                "listeners": [null]
                                            }
                                        }
                                    }
                                }
                            },
                            { "insert": "Aliquam egestas nulla ipsum, tempor pellentesque urna finibus id. Cras lacinia posuere quam vitae congue. Phasellus eget odio tincidunt, posuere dui at, ultrices ante. Praesent pharetra rutrum faucibus. Donec in lobortis urna, et lobortis enim. Interdum et malesuada fames ac ante ipsum primis in faucibus. Donec consequat justo id condimentum venenatis. Vestibulum mattis blandit leo, nec viverra ante molestie at. Suspendisse vel erat et nisi scelerisque volutpat ut eget urna. Morbi pulvinar posuere nisl.\nSed maximus in nisl lacinia scelerisque. Aliquam gravida, ligula ut varius feugiat, purus tellus faucibus nibh, ut scelerisque dolor velit gravida justo. Pellentesque accumsan velit sed rutrum imperdiet. Fusce vulputate enim sed felis ornare, et feugiat risus varius. Nam nibh massa, sodales sed lorem eu, rhoncus laoreet nibh. Nullam eu urna erat. Curabitur consectetur interdum libero, ut facilisis tellus vulputate id. Ut mollis dolor id rutrum aliquam. Aliquam id auctor velit, a efficitur nunc. Curabitur mollis dui non efficitur volutpat. Vestibulum laoreet iaculis congue. Duis laoreet quam eu justo ullamcorper finibus.\n" }
                        ],
                        "dateInserted": "2019-06-14T14:09:45+00:00",
                        "dateUpdated": null,
                        "insertUser": {
                            "userID": 4,
                            "name": "Karen A. Thomas",
                            "photoUrl": "https://images.v-cdn.net/stubcontent/avatar_01.png",
                            "dateLastActive": "2019-06-14T18:32:27+00:00"
                        },
                        "url": "https://dev.vanilla.localhost/discussion/8/test-file-upload",
                        "format": "Rich"
                    }
                },
                "loaderData": {
                    "type": "link",
                    "link": "https://dev.vanilla.localhost/discussion/8/test-file-upload"
                }
            }
        }
    },
    { "insert": "Comment Quote" },
    { "attributes": { "header": 3 }, "insert": "\n" },
    {
        "insert": {
            "embed-external": {
                "data": {
                    "url": "https://dev.vanilla.localhost/discussion/comment/5#Comment_5",
                    "type": "quote",
                    "name": null,
                    "body": null,
                    "photoUrl": null,
                    "height": null,
                    "width": null,
                    "attributes": {
                        "commentID": 5,
                        "bodyRaw": [{ "insert": "Testtes test\n" }],
                        "dateInserted": "2019-06-17T18:52:20+00:00",
                        "dateUpdated": null,
                        "insertUser": {
                            "userID": 2,
                            "name": "admin",
                            "photoUrl": "https://dev.vanilla.localhost/uploads/userpics/022/nWZ7BPS4F5HHQ.png",
                            "dateLastActive": "2019-06-17T15:09:52+00:00"
                        },
                        "url": "https://dev.vanilla.localhost/discussion/comment/5#Comment_5",
                        "format": "Rich"
                    }
                },
                "loaderData": { "type": "link", "link": "https://dev.vanilla.localhost/discussion/comment/5#Comment_5" }
            }
        }
    }
]';

echo "<div class='Item-Body'><div class='Message userContent'>";
echo "<h2>Inline operations</h2>";
echo Gdn::formatService()->renderHTML($inlineOperations, RichFormat::FORMAT_KEY);
echo "<hr>";
echo "<h2>Block operations</h2>";
echo Gdn::formatService()->renderHTML($blockOperations, RichFormat::FORMAT_KEY);
echo "<hr>";
echo "<h2>Embed operations</h2>";
echo Gdn::formatService()->renderHTML($embedOperations, RichFormat::FORMAT_KEY);
echo "<h2>Quotes</h2>";
echo Gdn::formatService()->renderHTML($quoteEmbeds, RichFormat::FORMAT_KEY);
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
