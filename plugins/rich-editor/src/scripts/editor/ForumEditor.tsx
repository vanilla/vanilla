/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { hasPermission } from "@library/features/users/Permission";
import { DeviceProvider } from "@library/layout/DeviceContext";
import getStore from "@library/redux/getStore";
import { IEditorProps } from "@rich-editor/editor/context";
import { Editor } from "@rich-editor/editor/Editor";
import EditorContent from "@rich-editor/editor/EditorContent";
import { EditorEmbedBar } from "@rich-editor/editor/EditorEmbedBar";
import { EditorInlineMenus } from "@rich-editor/editor/EditorInlineMenus";
import { EditorParagraphMenu } from "@rich-editor/editor/EditorParagraphMenu";
import { FormatConversionNotice } from "@rich-editor/editor/FormatConversionNotice";
import EditorDescriptions from "@rich-editor/editor/pieces/EditorDescriptions";
import { richEditorClasses } from "@library/editor/richEditorStyles";
import classNames from "classnames";
import React, { useLayoutEffect, useRef, useState } from "react";
import { Provider } from "react-redux";

interface IProps {
    legacyTextArea: HTMLInputElement;
    descriptionID?: string;
    uploadEnabled?: boolean;
    placeholder?: string;
    needsHtmlConversion?: boolean;
}

/**
 * The full editor UI for the forum.
 *
 * Brings along it's own Redux Context.
 */
export function ForumEditor(props: IProps) {
    const store = getStore();
    const classes = richEditorClasses(true);
    const [hasFocus, setHasFocus] = useState(false);
    const uploadEnabled = props.uploadEnabled ?? true;

    const [operationsQueue, setOperationsQueue] = useState<IEditorProps["operationsQueue"]>();
    const [showConversionNotice, setShowConversionNotice] = useState(false);
    const messageRef = useRef<HTMLDivElement | null>(null);

    // Only occurs on the first run.
    useLayoutEffect(() => {
        if (props.needsHtmlConversion) {
            setOperationsQueue([props.legacyTextArea.value]);
            props.legacyTextArea.value = "";
            setShowConversionNotice(true);
        }
    }, []);

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
        <Provider store={store}>
            <DeviceProvider>
                {showConversionNotice && (
                    <FormatConversionNotice
                        ref={messageRef}
                        className={classes.conversionNotice}
                        onCancel={cancelForm}
                        onConfirm={() => setShowConversionNotice(false)}
                    />
                )}
                <Editor
                    isPrimaryEditor={true}
                    legacyMode={true}
                    allowUpload={hasPermission("uploads.add")}
                    isLoading={false}
                    onFocus={setHasFocus}
                    operationsQueue={operationsQueue}
                >
                    <div
                        className={classNames(
                            "richEditor-frame",
                            "InputBox",
                            classes.legacyFrame,
                            classes.root,
                            hasFocus && "focus-visible",
                        )}
                    >
                        {props.descriptionID && <EditorDescriptions id={props.descriptionID} />}
                        <EditorContent placeholder={props.placeholder} legacyTextArea={props.legacyTextArea} />
                        <EditorParagraphMenu />
                        <EditorInlineMenus />
                        <EditorEmbedBar uploadEnabled={uploadEnabled} />
                    </div>
                </Editor>
            </DeviceProvider>
        </Provider>
    );
}
