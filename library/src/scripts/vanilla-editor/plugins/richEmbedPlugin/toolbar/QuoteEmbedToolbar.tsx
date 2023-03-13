/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useRef } from "react";
import { useMyEditorState } from "@library/vanilla-editor/typescript";
import { queryRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/queries/queryRichLink";
import { removeNodes } from "@udecode/plate-headless";
import Floating, { defaultFloatingOptions } from "@library/vanilla-editor/toolbars/Floating";
import { Icon } from "@vanilla/icons";
import { EmbedMenu } from "@library/editor/pieces/EmbedMenu";
import { EmbedButton } from "@library/embeddedContent/components/EmbedButton";
import { useFloatingQuoteEdit } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/useFloatingQuoteEdit";

export default function QuoteEmbedToolbar() {
    const editor = useMyEditorState();
    const entry = queryRichLink(editor);

    const arrowRef = useRef<HTMLDivElement | null>(null);

    const floatingResult = useFloatingQuoteEdit({
        floatingOptions: {
            ...defaultFloatingOptions,
            placement: "top",
        },
    });

    if (!entry || entry.element?.embedData?.embedType !== "quote") {
        return null;
    }

    return (
        <Floating ref={arrowRef} {...floatingResult}>
            <EmbedMenu>
                <EmbedButton
                    onClick={() => {
                        removeNodes(editor, {
                            at: entry.path,
                        });
                    }}
                >
                    <Icon icon={"data-trash"} />
                </EmbedButton>
            </EmbedMenu>
        </Floating>
    );
}
