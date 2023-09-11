/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { richEditorClasses } from "@library/editor/richEditorStyles";
import { FormatConversionNotice } from "@rich-editor/editor/FormatConversionNotice";
import React, { useRef, useState } from "react";

interface IProps {
    showConversionNotice: boolean;
}

export function ConversionNotice(props: IProps) {
    const [showConversionNotice, setShowConversionNotice] = useState(props.showConversionNotice);
    const messageRef = useRef<HTMLDivElement | null>(null);
    const classes = richEditorClasses(true);

    function cancelForm() {
        const form = messageRef.current?.closest("form");
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        let cancelButton = form.querySelector(".Button.Cancel");

        //try to find another way
        if (!cancelButton) {
            const possibleCancelButton = form.querySelector(".Button.Primary");
            if (possibleCancelButton && possibleCancelButton.textContent === "Cancel") {
                cancelButton = possibleCancelButton;
            }
        }
        if (cancelButton instanceof HTMLElement) {
            cancelButton.click();
        } else {
            //let's just go back if no cancel button found
            window.history.back();
        }
    }

    return (
        <>
            {showConversionNotice && (
                <FormatConversionNotice
                    ref={messageRef}
                    className={classes.conversionNotice}
                    onCancel={cancelForm}
                    onConfirm={() => setShowConversionNotice(false)}
                />
            )}
        </>
    );
}
