/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { richEditorClasses } from "@library/editor/richEditorStyles";
import { FormatConversionNotice } from "@rich-editor/editor/FormatConversionNotice";
import React, { useEffect, useRef, useState } from "react";

interface IProps {
    showConversionNotice: boolean;
}

export function ConversionNotice(props: IProps) {
    const [showConversionNotice, setShowConversionNotice] = useState(props.showConversionNotice);
    const messageRef = useRef<HTMLDivElement | null>(null);
    const classes = richEditorClasses(true);

    useEffect(() => {
        setShowConversionNotice(props.showConversionNotice);
    }, [props]);

    function cancelForm() {
        const form = messageRef.current?.closest("form");
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const cancelButton = form.querySelector(".Button.Cancel");
        if (cancelButton instanceof HTMLElement) {
            cancelButton.click();
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
