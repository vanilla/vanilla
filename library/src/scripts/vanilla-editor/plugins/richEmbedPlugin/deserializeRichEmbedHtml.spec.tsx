/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { deserializeHtml } from "@library/vanilla-editor/VanillaEditor";

// rendered image embed html
const MOCK_IMAGE_EMBED_HTML = `<div class="embedExternal embedImage display-small float-left" data-embedjson="{&quot;url&quot;:&quot;https://dev.vanilla.localhost/uploads/U2X2FLFYL7F3/ai-generated-7702851-340.png&quot;,&quot;name&quot;:&quot;ai-generated-7702851__340.png&quot;,&quot;type&quot;:&quot;image/png&quot;,&quot;size&quot;:137995,&quot;width&quot;:340,&quot;height&quot;:340,&quot;displaySize&quot;:&quot;small&quot;,&quot;float&quot;:&quot;left&quot;,&quot;embedType&quot;:&quot;image&quot;}">
<div class="embedExternal-content">
    <a class="embedImage-link" href="https://dev.vanilla.localhost/uploads/U2X2FLFYL7F3/ai-generated-7702851-340.png" rel="nofollow noopener ugc" target="_blank">
        <img class="embedImage-img" src="https://dev.vanilla.localhost/uploads/U2X2FLFYL7F3/ai-generated-7702851-340.png" alt="ai-generated-7702851__340.png" height="340" width="340" loading="lazy" data-display-size="small" data-float="left" data-type="image/png" data-embed-type="image"></a>
</div>
</div>`;

const MOCK_IMAGE_EMBED_RICH2 = [
    {
        type: "rich_embed_card",
        embedData: {
            url: "https://dev.vanilla.localhost/uploads/U2X2FLFYL7F3/ai-generated-7702851-340.png",
            name: "ai-generated-7702851__340.png",
            type: "image/png",
            size: 137995,
            width: 340,
            height: 340,
            displaySize: "small",
            float: "left",
            embedType: "image",
        },
        children: [
            {
                text: "",
            },
        ],
    },
];

// rendered youtube embed mocks
const MOCK_YOUTUBE_EMBED_HTML = `<div class="js-embed embedResponsive" data-embedjson="{&quot;height&quot;:113,&quot;width&quot;:200,&quot;photoUrl&quot;:&quot;https://i.ytimg.com/vi/Ex84AZjgkDU/hqdefault.jpg&quot;,&quot;videoID&quot;:&quot;Ex84AZjgkDU&quot;,&quot;showRelated&quot;:false,&quot;url&quot;:&quot;https://youtu.be/Ex84AZjgkDU&quot;,&quot;embedType&quot;:&quot;youtube&quot;,&quot;name&quot;:&quot;Crazy Frog - Axel F in 2x 4x 8x 16x... 100x speed&quot;,&quot;embedStyle&quot;:&quot;rich_embed_card&quot;,&quot;frameSrc&quot;:&quot;https://www.youtube.com/embed/Ex84AZjgkDU?feature=oembed&amp;autoplay=1&quot;}">
<a href="https://dev.vanilla.localhost/home/leaving?allowTrusted=1&amp;target=https%3A%2F%2Fyoutu.be%2FEx84AZjgkDU" rel="nofollow noopener ugc">
    https://youtu.be/Ex84AZjgkDU
</a>
</div>`;

const MOCK_YOUTUBE_EMBED_RICH2 = [
    {
        type: "rich_embed_card",
        children: [{ text: "https://youtu.be/Ex84AZjgkDU" }],
        embedData: {
            height: 113,
            width: 200,
            photoUrl: "https://i.ytimg.com/vi/Ex84AZjgkDU/hqdefault.jpg",
            videoID: "Ex84AZjgkDU",
            showRelated: false,
            url: "https://youtu.be/Ex84AZjgkDU",
            embedType: "youtube",
            name: "Crazy Frog - Axel F in 2x 4x 8x 16x... 100x speed",
            embedStyle: "rich_embed_card",
            frameSrc: "https://www.youtube.com/embed/Ex84AZjgkDU?feature=oembed&autoplay=1",
        },
    },
];

// rendered file embed mocks
const MOCK_FILE_EMBED_HTML = `<div class="js-embed embedResponsive" data-embedjson="{&quot;url&quot;:&quot;https://dev.vanilla.localhost/uploads/Y176L4IVCRGE/ctoweeklyupdate0105.pptx&quot;,&quot;name&quot;:&quot;CTOWeeklyUpdate0105.pptx&quot;,&quot;type&quot;:&quot;application/vnd.openxmlformats-officedocument.presentationml.presentation&quot;,&quot;size&quot;:220267,&quot;displaySize&quot;:&quot;large&quot;,&quot;float&quot;:&quot;none&quot;,&quot;mediaID&quot;:41,&quot;dateInserted&quot;:&quot;2023-04-13T12:25:46+00:00&quot;,&quot;insertUserID&quot;:7,&quot;foreignType&quot;:&quot;embed&quot;,&quot;foreignID&quot;:7,&quot;embedType&quot;:&quot;file&quot;}"><div class="embedExternal css-meyup4-embed-small"><div aria-describedby="embed-description-0" aria-label="External embed content - Microsoft PowerPoint Presentation" class="css-iripcp-embedContent-root embedExternal-content"><a aria-current="false" href="https://dev.vanilla.localhost/uploads/Y176L4IVCRGE/ctoweeklyupdate0105.pptx" to="https://dev.vanilla.localhost/uploads/Y176L4IVCRGE/ctoweeklyupdate0105.pptx" class="css-19sbzcu-attachment-link css-1hzbsk7-attachment-box" type="application/vnd.openxmlformats-officedocument.presentationml.presentation" download="CTOWeeklyUpdate0105.pptx" tabindex="0"><div class="css-lnjvz3-attachment-format"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" class="attachmentIcon-filePowerPoint attachmentIcon css-12ko03w-icon-fileType css-10kj21h-attachmentIcon" role="img" aria-label="An image file"><title><abbr title="Microsoft PowerPoint Presentation">Adobe Portable Document Format (PDF)</abbr></title><rect width="16" height="16" rx="1" style="fill: rgb(238, 106, 1);"></rect><path d="M8,4V7.5h3.55A3.5,3.5,0,1,1,8,4Z" style="fill: rgb(251, 225, 204);"></path><path d="M9,3h.05a3.5,3.5,0,0,1,3.5,3.5H9Z" style="fill: rgb(251, 225, 204);"></path><rect x="3" y="12" width="10" height="1" style="fill: rgb(251, 225, 204);"></rect></svg></div><div class="css-1hvdwd7-attachment-main"><div class="css-1mewpc0-attachment-title">CTOWeeklyUpdate0105.pptx</div><div class="css-19gnhii-attachment-metas css-jn3jxf-Metas-styles-root"><span class="css-1z0xh3a-Metas-styles-meta">Uploaded <time datetime="2023-04-13T12:25:46+00:00" title="Thursday, April 13, 2023 at 8:25 AM">Apr 13, 2023</time></span><span class="css-1z0xh3a-Metas-styles-meta">215.1<abbr title="Kilobyte"> KB</abbr></span></div></div></a></div></div><div><div role="log" aria-live="assertive" style="border: 0px; clip: rect(0px, 0px, 0px, 0px); height: 1px; margin: -1px; overflow: hidden; white-space: nowrap; padding: 0px; width: 1px; position: absolute;"></div><div role="log" aria-live="assertive" style="border: 0px; clip: rect(0px, 0px, 0px, 0px); height: 1px; margin: -1px; overflow: hidden; white-space: nowrap; padding: 0px; width: 1px; position: absolute;"></div><div role="log" aria-live="polite" style="border: 0px; clip: rect(0px, 0px, 0px, 0px); height: 1px; margin: -1px; overflow: hidden; white-space: nowrap; padding: 0px; width: 1px; position: absolute;"></div><div role="log" aria-live="polite" style="border: 0px; clip: rect(0px, 0px, 0px, 0px); height: 1px; margin: -1px; overflow: hidden; white-space: nowrap; padding: 0px; width: 1px; position: absolute;"></div></div></div>`;

const MOCK_FILE_EMBED_RICH2 = [
    {
        type: "rich_embed_card",
        embedData: {
            url: "https://dev.vanilla.localhost/uploads/Y176L4IVCRGE/ctoweeklyupdate0105.pptx",
            name: "CTOWeeklyUpdate0105.pptx",
            type: "application/vnd.openxmlformats-officedocument.presentationml.presentation",
            size: 220267,
            displaySize: "large",
            float: "none",
            mediaID: 41,
            dateInserted: "2023-04-13T12:25:46+00:00",
            insertUserID: 7,
            foreignType: "embed",
            foreignID: 7,
            embedType: "file",
        },
        children: [
            {
                text: "https://dev.vanilla.localhost/uploads/Y176L4IVCRGE/ctoweeklyupdate0105.pptx",
            },
        ],
    },
];

// rendered iframe embed mocks
const MOCK_IFRAME_EMBED_HTML = `<div class="js-embed embedResponsive" data-embedjson="{&quot;height&quot;:&quot;315&quot;,&quot;width&quot;:&quot;560&quot;,&quot;url&quot;:&quot;https://www.youtube.com/embed/Aiqa9l1vFNI&quot;,&quot;embedType&quot;:&quot;iframe&quot;,&quot;embedStyle&quot;:&quot;rich_embed_card&quot;}">
<a href="https://dev.vanilla.localhost/home/leaving?allowTrusted=1&amp;target=https%3A%2F%2Fwww.youtube.com%2Fembed%2FAiqa9l1vFNI" rel="nofollow noopener ugc">
    https://www.youtube.com/embed/Aiqa9l1vFNI
</a>
</div>`;

const MOCK_IFRAME_EMBED_RICH2 = [
    {
        type: "rich_embed_card",
        embedData: {
            height: "315",
            width: "560",
            url: "https://www.youtube.com/embed/Aiqa9l1vFNI",
            embedType: "iframe",
            embedStyle: "rich_embed_card",
        },
        children: [
            {
                text: "https://www.youtube.com/embed/Aiqa9l1vFNI",
            },
        ],
        frameAttributes: {
            height: "315",
            width: "560",
        },
    },
];

// rendered rich link card embed mocks
const MOCK_LINK_CARD_HTML = `<div class="js-embed embedResponsive" data-embedjson="{&quot;body&quot;:&quot;Search the world's information, including webpages, images, videos and more. Google has many special features to help you find exactly what you're looking for.&quot;,&quot;url&quot;:&quot;https://dev.vanilla.localhost/home/leaving?allowTrusted=1&amp;target=https%3A%2F%2Fwww.google.com%2F&quot;,&quot;embedType&quot;:&quot;link&quot;,&quot;name&quot;:&quot;Google&quot;,&quot;embedStyle&quot;:&quot;rich_embed_card&quot;}">
<a href="https://dev.vanilla.localhost/home/leaving?allowTrusted=1&amp;target=https%3A%2F%2Fwww.google.com%2F" rel="nofollow noopener ugc">
    https://www.google.com/
</a>
</div>`;

const MOCK_LINK_CARD_RICH2 = [
    {
        children: [
            {
                text: "https://dev.vanilla.localhost/home/leaving?allowTrusted=1&target=https%3A%2F%2Fwww.google.com%2F",
            },
        ],
        embedData: {
            body: "Search the world's information, including webpages, images, videos and more. Google has many special features to help you find exactly what you're looking for.",
            embedType: "link",
            name: "Google",
            url: "https://dev.vanilla.localhost/home/leaving?allowTrusted=1&target=https%3A%2F%2Fwww.google.com%2F",
            embedStyle: "rich_embed_card",
        },
        type: "rich_embed_card",
    },
];

// rendered inline rich link embed mocks
const MOCK_LINK_INLINE_CARD_HTML = `<p><span class="js-embed embedResponsive inlineEmbed" data-embedjson="{&quot;body&quot;:&quot;Need to engage customers, members or employees? Our SaaS online community platform &amp; email campaign software helps you reach your goals.&quot;,&quot;photoUrl&quot;:&quot;https://www.higherlogic.com/wp-content/uploads/2021/03/Homepage.png&quot;,&quot;url&quot;:&quot;https://dev.vanilla.localhost/home/leaving?allowTrusted=1&amp;target=https%3A%2F%2Fwww.higherlogic.com%2F&quot;,&quot;embedType&quot;:&quot;link&quot;,&quot;name&quot;:&quot;Member and Customer Engagement Platform - Higher Logic&quot;,&quot;faviconUrl&quot;:&quot;https://sp-ao.shortpixel.ai/client/to_auto,q_lossy,ret_img,w_32,h_32/https://www.higherlogic.com/wp-content/uploads/2020/05/higherlogic_favicon.png&quot;,&quot;embedStyle&quot;:&quot;rich_embed_inline&quot;}">
    <a href="https://dev.vanilla.localhost/home/leaving?allowTrusted=1&amp;target=https%3A%2F%2Fwww.higherlogic.com%2F" rel="nofollow noopener ugc">
        https://www.higherlogic.com/
    </a>
</span>
</p>`;

const MOCK_LINK_INLINE_CARD_RICH2 = [
    {
        children: [
            {
                children: [
                    {
                        text: "https://dev.vanilla.localhost/home/leaving?allowTrusted=1&target=https%3A%2F%2Fwww.higherlogic.com%2F",
                    },
                ],
                embedData: {
                    body: "Need to engage customers, members or employees? Our SaaS online community platform & email campaign software helps you reach your goals.",
                    embedStyle: "rich_embed_inline",
                    embedType: "link",
                    faviconUrl:
                        "https://sp-ao.shortpixel.ai/client/to_auto,q_lossy,ret_img,w_32,h_32/https://www.higherlogic.com/wp-content/uploads/2020/05/higherlogic_favicon.png",
                    name: "Member and Customer Engagement Platform - Higher Logic",
                    photoUrl: "https://www.higherlogic.com/wp-content/uploads/2021/03/Homepage.png",
                    url: "https://dev.vanilla.localhost/home/leaving?allowTrusted=1&target=https%3A%2F%2Fwww.higherlogic.com%2F",
                },
                type: "rich_embed_inline",
            },
        ],
        type: "p",
    },
];

// basic iframe mocks
const MOCK_IFRAME_HTML = `<iframe width="560" height="315" src="https://www.youtube.com/embed/Aiqa9l1vFNI" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>`;

const MOCK_IFRAME_RICH2 = [
    {
        type: "rich_embed_card",
        url: "https://www.youtube.com/embed/Aiqa9l1vFNI",
        children: [{ text: "" }],
        dataSourceType: "iframe",
        embedData: {
            embedType: "iframe",
            height: "315",
            name: "YouTube video player",
            url: "https://www.youtube.com/embed/Aiqa9l1vFNI",
            width: "560",
        },
        frameAttributes: {
            width: "560",
            height: "315",
        },
    },
];

// basic image mocks
const MOCK_IMAGE_HTML = `<img src="https://picsum.photos/200/150" alt="Mock Image" width="200" height="150" />`;

const MOCK_IMAGE_RICH2 = [
    {
        type: "rich_embed_card",
        url: "https://picsum.photos/200/150",
        dataSourceType: "image",
        children: [{ text: "" }],
        embedData: {
            embedType: "image",
            name: "Mock Image",
            float: "none",
            url: "https://picsum.photos/200/150",
            width: 200,
            height: 150,
        },
    },
];

// legacy emoji mocks
const MOCK_LEGACY_EMOJI_HTML = `<img src="https://picsum.photos/20" alt=":)" class="emoji" />`;

const MOCK_LEGACY_EMOJI_RICH2 = [
    {
        type: "legacy_emoji_image",
        attributes: {
            src: "https://picsum.photos/20",
            alt: ":)",
            title: "",
            width: 16,
            height: 16,
        },
        children: [{ text: "" }],
    },
];

describe("Deserialize Rich Embed HTML", () => {
    it("Converts legacy emoji image to properly sized image in Rich2", () => {
        const actual = deserializeHtml(MOCK_LEGACY_EMOJI_HTML);
        expect(actual).toStrictEqual(MOCK_LEGACY_EMOJI_RICH2);
    });

    it("Converts image HTML to Rich2 format", () => {
        const actual = deserializeHtml(MOCK_IMAGE_HTML);
        expect(actual).toStrictEqual(MOCK_IMAGE_RICH2);
    });

    it("Converts iFrame HTML to Rich2 format", () => {
        const actual = deserializeHtml(MOCK_IFRAME_HTML);
        expect(actual).toStrictEqual(MOCK_IFRAME_RICH2);
    });

    it("Converts embedded image HTML to Rich2 format", () => {
        const actual = deserializeHtml(MOCK_IMAGE_EMBED_HTML);
        expect(actual).toStrictEqual(MOCK_IMAGE_EMBED_RICH2);
    });

    it("Converts embedded YouTube HTML to Rich2 format", () => {
        const actual = deserializeHtml(MOCK_YOUTUBE_EMBED_HTML);
        expect(actual).toStrictEqual(MOCK_YOUTUBE_EMBED_RICH2);
    });

    it("Converts embedded file HTML to Rich2 format", () => {
        const actual = deserializeHtml(MOCK_FILE_EMBED_HTML);
        expect(actual).toStrictEqual(MOCK_FILE_EMBED_RICH2);
    });

    it("Converts embedded iFrame HTML to Rich2 format", () => {
        const actual = deserializeHtml(MOCK_IFRAME_EMBED_HTML);
        expect(actual).toStrictEqual(MOCK_IFRAME_EMBED_RICH2);
    });

    it("Converts embedded rich link card HTML to Rich2 format", () => {
        const actual = deserializeHtml(MOCK_LINK_CARD_HTML);
        expect(actual).toStrictEqual(MOCK_LINK_CARD_RICH2);
    });

    it("Converts embedded rich link inline card HTML to Rich2 format", () => {
        const actual = deserializeHtml(MOCK_LINK_INLINE_CARD_HTML);
        expect(actual).toStrictEqual(MOCK_LINK_INLINE_CARD_RICH2);
    });
});
