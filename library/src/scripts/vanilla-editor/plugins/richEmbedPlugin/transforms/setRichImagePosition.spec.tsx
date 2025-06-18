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
import { createVanillaEditor } from "@library/vanilla-editor/VanillaEditor.loadable";
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

describe("setRichImagePosition", () => {
    it("can convert a standard image to inline", () => {
        const editor = createVanillaEditor();

        editor.insertNode(imageNode(ELEMENT_RICH_EMBED_CARD));

        setRichImagePosition(editor, "inline");

        expect(editor.children).toStrictEqual([paragraphWithInlineImage]);
    });

    it("can convert an inline image back to a standard card", () => {
        const editor = createVanillaEditor();

        editor.insertNode(paragraphWithInlineImage);

        editor.select([0, 1]);

        setRichImagePosition(editor, "small");
        expect(editor.children).toStrictEqual([imageNode(ELEMENT_RICH_EMBED_CARD), spaceTextNodeParagraph]);
    });
});
