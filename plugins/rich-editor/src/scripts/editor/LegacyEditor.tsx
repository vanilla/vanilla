/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { EditorParagraphMenu } from "@rich-editor/editor/EditorParagraphMenu";
import { Editor } from "@rich-editor/editor/context";
import { hasPermission } from "@library/features/users/permissionUtils";
import classNames from "classnames";
import { EditorInlineMenus } from "@rich-editor/editor/EditorInlineMenus";
import { EditorEmbedBar } from "@rich-editor/editor/pieces/EmbedBar";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";
import EditorContent from "@rich-editor/editor/EditorContent";
import { richEditorFormClasses } from "@rich-editor/editor/richEditorFormClasses";

interface IProps {
    legacyTextArea: HTMLInputElement;
}

export function LegacyEditor(props: IProps) {
    const store = getStore();
    const classesRichEditorForm = richEditorFormClasses();
    return (
        <Provider store={store}>
            <Editor isPrimaryEditor={true} legacyMode={true} allowUpload={hasPermission("uploads.add")}>
                <div className={classNames("richEditor-frame", "InputBox", classesRichEditorForm.scrollFrame)}>
                    <EditorContent legacyTextArea={props.legacyTextArea} />
                    <EditorParagraphMenu />
                    <EditorInlineMenus />
                    <EditorEmbedBar />
                </div>
            </Editor>
        </Provider>
    );
}
