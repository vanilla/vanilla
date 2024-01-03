/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { getMeta } from "@library/utility/appUtils";
import { setRichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/setRichLinkAppearance";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { IVanillaLinkElement } from "@library/vanilla-editor/typescript";
import { findNodePath, focusEditor, getNodeString, PlateRenderElementProps } from "@udecode/plate-common";
import React, { useEffect } from "react";

interface IProps extends PlateRenderElementProps<any, IVanillaLinkElement> {}

/**
 * A full content editable link element.
 *
 * - If the text content and the url are the same we will automatically convert this into a rich link.
 * - The skip this if `forceBasicLink` is passed (like when you convert an embed to a link with the menu).
 */
export function RichLinkElement(props: IProps) {
    const { attributes, children, nodeProps, element, editor } = props;

    const { forceBasicLink } = element;
    const textContent = getNodeString(element);
    const { url } = element;

    const ownPath = findNodePath(editor, element);
    useEffect(() => {
        if (forceBasicLink) {
            // Don't do anything.
            return;
        }

        if (textContent === url) {
            // If we didn't set specific text automatically linkify it.
            const defaultAppearance = getMeta("disableUrlEmbeds") ? RichLinkAppearance.LINK : RichLinkAppearance.INLINE;
            setRichLinkAppearance(editor, defaultAppearance, ownPath);
            focusEditor(editor);
        }
    }, [url, forceBasicLink, editor, ownPath, textContent]);

    return (
        <a {...attributes} {...nodeProps} href={url}>
            {children}
        </a>
    );
}
