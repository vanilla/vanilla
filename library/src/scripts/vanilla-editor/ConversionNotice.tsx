/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { richEditorClasses } from "@library/editor/richEditorStyles";
import { FormatConversionNotice } from "@rich-editor/editor/FormatConversionNotice";
import React, { useLayoutEffect, useMemo, useRef, useState } from "react";

interface IProps {
    showConversionNotice: boolean;
}

export function ConversionNotice(props: IProps) {
    const [showConversionNotice, setShowConversionNotice] = useState(props.showConversionNotice);
    const messageRef = useRef<HTMLDivElement | null>(null);
    const classes = richEditorClasses(true);

    function selectCancelButton(): HTMLButtonElement | undefined {
        const form = messageRef.current?.closest("form");
        if (form instanceof HTMLFormElement) {
            let cancelButton = form.querySelector<HTMLButtonElement>(".Button.Cancel");

            //try to find another way
            if (!cancelButton) {
                const possibleCancelButton = form.querySelector<HTMLButtonElement>(".Button.Primary");
                if (possibleCancelButton && possibleCancelButton.textContent === "Cancel") {
                    cancelButton = possibleCancelButton;
                }
            }
            return cancelButton ?? undefined;
        }
    }

    const [hasCancelButton, setHasCancelButton] = React.useState(false);

    useLayoutEffect(() => {
        const cancelButton = selectCancelButton();
        if (cancelButton) {
            setHasCancelButton(true);
        }
    }, []);

    const cancelForm = useMemo<React.ComponentProps<typeof FormatConversionNotice>["onCancel"] | undefined>(() => {
        if (hasCancelButton) {
            const cancelButton = selectCancelButton();
            return () => {
                cancelButton!.click();
            };
        }
        return undefined;
    }, [hasCancelButton]);

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
