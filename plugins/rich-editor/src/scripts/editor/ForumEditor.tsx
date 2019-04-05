/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { hasPermission } from "@library/features/users/permissionUtils";
import getStore from "@library/redux/getStore";
import { Editor } from "@rich-editor/editor/context";
import EditorContent from "@rich-editor/editor/EditorContent";
import { EditorInlineMenus } from "@rich-editor/editor/EditorInlineMenus";
import { EditorParagraphMenu } from "@rich-editor/editor/EditorParagraphMenu";
import { EditorEmbedBar } from "@rich-editor/editor/EditorEmbedBar";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import classNames from "classnames";
import React from "react";
import { Provider } from "react-redux";

interface IProps {
    legacyTextArea: HTMLInputElement;
}

/**
 * The full editor UI for the forum.
 *
 * Brings along it's own Redux Context.
 */
export function ForumEditor(props: IProps) {
    const store = getStore();
    const classes = richEditorClasses(true);
    return (
        <Provider store={store}>
            <Editor isPrimaryEditor={true} legacyMode={true} allowUpload={hasPermission("uploads.add")}>
                <div className={classNames("richEditor-frame", "InputBox", classes.legacyFrame, classes.root)}>
                    <EditorContent legacyTextArea={props.legacyTextArea} />
                    <EditorParagraphMenu />
                    <EditorInlineMenus />
                    <EditorEmbedBar />
                </div>
            </Editor>
        </Provider>
    );
}
