/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { ELEMENT_LEGACY_EMOJI, ELEMENT_RICH_EMBED_CARD } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { DeserializeHtml, Nullable } from "@udecode/plate-headless";

export const deserializeRichEmbedHtml: Nullable<DeserializeHtml> | null | undefined = {
    isElement: true,
    rules: [
        { validClassName: "embedExternal" },
        { validClassName: "js-embed" },
        { validNodeName: "IMG" },
        { validNodeName: "IFRAME" },
        { validNodeName: "A" },
    ],
    getNode,
};

function getNode(el) {
    const { className, tagName, src, alt, title, height, width, dataset } = el;

    // element is a rich embed from another format or pasted html
    if (dataset.embedjson) {
        const embedData = JSON.parse(dataset.embedjson);

        return {
            type: embedData.embedStyle ?? ELEMENT_RICH_EMBED_CARD,
            embedData,
            children: [
                {
                    text: embedData.embedType === "image" ? "" : embedData.url,
                },
            ],
            ...(embedData.embedType === "iframe" && {
                frameAttributes: {
                    width: embedData.width,
                    height: embedData.height,
                },
            }),
        };
    }

    // element is a legacy emoji image
    if (className.includes("emoji")) {
        return {
            type: ELEMENT_LEGACY_EMOJI,
            children: [{ text: "" }],
            attributes: {
                src: src,
                alt: alt,
                title: title,
                ...(el.srcset && { srcset: el.srcset }),
                width: globalVariables().fonts.size.large,
                height: globalVariables().fonts.size.large,
            },
        };
    }

    // element is an iframe
    if (tagName === "IFRAME") {
        return {
            type: ELEMENT_RICH_EMBED_CARD,
            children: [{ text: "" }],
            dataSourceType: "iframe",
            url: src,
            frameAttributes: {
                width,
                height,
            },
            embedData: {
                url: src,
                embedType: "iframe",
                name: alt ?? title,
                width,
                height,
            },
        };
    }

    // element is a link with an image
    if (tagName === "A" && !el.className.includes("embedLink-link")) {
        const [image] = el.getElementsByTagName("IMG");
        if (image) {
            return {
                type: ELEMENT_RICH_EMBED_CARD,
                children: [{ text: "" }],
                dataSourceType: "image",
                url: image.src,
                embedData: {
                    url: image.src,
                    name: image.alt ?? image.title,
                    embedType: "image",
                    float: image.style.float !== "" ? image.style.float : "none",
                    ...(image.height && { height: image.height }),
                    ...(image.width && { width: image.width }),
                    ...image.dataset,
                },
            };
        }
    }

    // element is an image
    if (tagName === "IMG" && (!el.className.includes("embedImage-img") || el.className.includes("importedEmbed-img"))) {
        return {
            type: ELEMENT_RICH_EMBED_CARD,
            children: [{ text: "" }],
            dataSourceType: "image",
            url: src,
            embedData: {
                url: src,
                name: alt ?? title,
                embedType: "image",
                float: el.style.float !== "" ? el.style.float : "none",
                ...(height && { height }),
                ...(width && { width }),
                ...dataset,
            },
        };
    }
}
