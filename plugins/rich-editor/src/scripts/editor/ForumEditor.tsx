/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import getStore from "@library/redux/getStore";
import { Editor } from "@rich-editor/editor/Editor";
import EditorContent from "@rich-editor/editor/EditorContent";
import { EditorInlineMenus } from "@rich-editor/editor/EditorInlineMenus";
import { EditorParagraphMenu } from "@rich-editor/editor/EditorParagraphMenu";
import { EditorEmbedBar } from "@rich-editor/editor/EditorEmbedBar";
import { richEditorClasses } from "@rich-editor/editor/richEditorStyles";
import classNames from "classnames";
import { Provider } from "react-redux";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { useUniqueID } from "@library/utility/idUtils";
import { hasPermission } from "@library/features/users/Permission";
import EditorDescriptions from "@rich-editor/editor/pieces/EditorDescriptions";

interface IProps {
    legacyTextArea: HTMLInputElement;
    descriptionID?: string;
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
    return (
        <Provider store={store}>
            <DeviceProvider>
                <Editor
                    isPrimaryEditor={true}
                    legacyMode={true}
                    allowUpload={hasPermission("uploads.add")}
                    isLoading={false}
                    onFocus={setHasFocus}
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
                        <EditorContent legacyTextArea={props.legacyTextArea} />
                        <EditorParagraphMenu />
                        <EditorInlineMenus />
                        <EditorEmbedBar />
                    </div>
                </Editor>
            </DeviceProvider>
        </Provider>
    );
}
