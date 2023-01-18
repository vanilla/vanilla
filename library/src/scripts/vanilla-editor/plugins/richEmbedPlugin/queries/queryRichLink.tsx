/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IBaseEmbedData } from "@library/embeddedContent/embedService";
import {
    ELEMENT_RICH_EMBED_CARD,
    ELEMENT_RICH_EMBED_INLINE,
    RichLinkAppearance,
    IRichEmbedElement,
} from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { IVanillaLinkElement, MyEditor } from "@library/vanilla-editor/typescript";
import { ELEMENT_LINK, findNode } from "@udecode/plate-headless";
import { Location, Path } from "slate";

export function queryRichLink(
    editor: MyEditor,
    at?: Location,
): {
    appearance: RichLinkAppearance;
    embedData?: IBaseEmbedData;
    url: string;
    path: Path;
    element: IVanillaLinkElement | IRichEmbedElement;
} | null {
    at = at ?? editor.selection!;
    if (!at) {
        return null;
    }

    const simpleLink = findNode<IVanillaLinkElement>(editor, {
        at: at,
        match: {
            type: ELEMENT_LINK,
        },
    });

    if (simpleLink) {
        return {
            appearance: RichLinkAppearance.LINK,
            url: simpleLink[0].url,
            embedData: simpleLink[0].embedData,
            path: simpleLink[1],
            element: simpleLink[0],
        };
    }

    const embed = findNode<IRichEmbedElement>(editor, {
        at,
        match: {
            type: [ELEMENT_RICH_EMBED_INLINE, ELEMENT_RICH_EMBED_CARD],
        },
    });

    if (embed) {
        return {
            appearance:
                embed[0].type === ELEMENT_RICH_EMBED_INLINE ? RichLinkAppearance.INLINE : RichLinkAppearance.CARD,
            url: embed[0].url ?? "",
            embedData: embed[0].embedData,
            path: embed[1],
            element: embed[0],
        };
    }

    return null;
}
