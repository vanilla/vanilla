/* eslint-disable react/no-unknown-property */
/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { setRichImagePosition } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/setRichImagePosition";
import {
    ELEMENT_RICH_EMBED_CARD,
    ELEMENT_RICH_EMBED_INLINE,
} from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { createVanillaEditor } from "@library/vanilla-editor/createVanillaEditor";
import { jsx } from "@udecode/plate-test-utils";
jsx;

const emptyTextNode = {
    text: "",
};

const spaceTextNode = {
    text: " ",
};

const spaceTextNodeParagraph = {
    type: "p",
    children: [spaceTextNode],
};

const emptyTextNodeParagraph = {
    type: "p",
    children: [emptyTextNode],
};

function imageNode(type = ELEMENT_RICH_EMBED_CARD) {
    return {
        type: type,
        children: [emptyTextNode],
        dataSourceType: "image",
        url: "https://dev.vanilla.local/uploads/D6V0421085NU/planet.png",
        embedData: {
            url: "https://dev.vanilla.local/uploads/D6V0421085NU/planet.png",
            name: "planet.png",
            type: "image/png",
            size: 14087,
            width: 128,
            height: 128,
            displaySize: "small",
            float: "none",
            mediaID: 671,
            dateInserted: "2025-03-25T19:52:07+00:00",
            insertUserID: 9,
            foreignType: "embed",
            foreignID: "9",
            embedType: "image",
        },
    };
}

const paragraphWithInlineImage = {
    type: "p",
    children: [emptyTextNode, imageNode(ELEMENT_RICH_EMBED_INLINE), spaceTextNode],
};

const fileEmbedNode = {
    type: "rich_embed_card",
    children: [
        {
            text: "",
        },
    ],
    dataSourceType: "file",
    url: "http://files-api.vanilla.local/default-bucket/6G08JYNJBEO8/sundae-with-cherry.jpg",
    embedData: {
        url: "http://files-api.vanilla.local/default-bucket/6G08JYNJBEO8/sundae-with-cherry.jpg",
        name: "sundae with cherry.jpg",
        type: "image/jpeg",
        size: 299161,
        width: 1900,
        height: 1267,
        displaySize: "large",
        float: "none",
        mediaID: 891,
        dateInserted: "2025-08-07T13:39:15+00:00",
        insertUserID: 9,
        foreignType: "embed",
        foreignID: "9",
        embedType: "file",
    },
};

describe("setRichImagePosition", () => {
    it("can convert a standard image to inline", () => {
        const editor = createVanillaEditor();

        editor.insertNode(imageNode(ELEMENT_RICH_EMBED_CARD));

        setRichImagePosition(editor, "inline");

        expect(editor.children).toStrictEqual([paragraphWithInlineImage, emptyTextNodeParagraph]);
    });

    it("can convert an inline image back to a standard card", () => {
        const editor = createVanillaEditor();

        editor.insertNode(paragraphWithInlineImage);

        editor.select([0, 1]);

        setRichImagePosition(editor, "small");
        expect(editor.children).toStrictEqual([imageNode(ELEMENT_RICH_EMBED_CARD), spaceTextNodeParagraph]);
    });

    it("when the the previous element in the editor is an embed, converting an image to inline should cause a new paragraph element to be created with the inline image as its child", () => {
        const editor = createVanillaEditor();

        editor.insertNode(fileEmbedNode);

        editor.insertNode(imageNode(ELEMENT_RICH_EMBED_CARD));

        setRichImagePosition(editor, "inline");

        expect(editor.children).toStrictEqual([fileEmbedNode, paragraphWithInlineImage, emptyTextNodeParagraph]);
    });

    it("does not delete the first paragraph element when converting an image to inline at the start of the editor", () => {
        const editor = createVanillaEditor();

        editor.insertNode({
            type: "p",
            children: [
                {
                    text: "hello",
                },
            ],
        });

        editor.insertNodes([imageNode(ELEMENT_RICH_EMBED_CARD)], { at: [0] });

        editor.select([0]);

        setRichImagePosition(editor, "inline");

        expect(editor.children).toStrictEqual([
            paragraphWithInlineImage,
            {
                type: "p",
                children: [
                    {
                        text: "hello",
                    },
                ],
            },
        ]);
    });
});
