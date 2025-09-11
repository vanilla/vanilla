/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { EmbedMenu } from "@library/editor/pieces/EmbedMenu";
import { queryRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/queries/queryRichLink";
import { useFloatingQuoteEdit } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/useFloatingQuoteEdit";
import Floating, { defaultFloatingOptions } from "@library/vanilla-editor/toolbars/Floating";
import { useMyEditorState } from "@library/vanilla-editor/getMyEditor";
import { removeNodes } from "@udecode/plate-common";
import { Icon } from "@vanilla/icons";
import React, { useRef } from "react";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";

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
                <Button
                    buttonType={ButtonTypes.ICON}
                    onClick={() => {
                        removeNodes(editor, {
                            at: entry.path,
                        });
                    }}
                >
                    <Icon icon={"delete"} />
                </Button>
            </EmbedMenu>
        </Floating>
    );
}
