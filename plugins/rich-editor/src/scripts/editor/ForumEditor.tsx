/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { hasPermission } from "@library/features/users/permissionUtils";
import getStore from "@library/redux/getStore";
import { Editor } from "@rich-editor/editor/Editor";
import EditorContent from "@rich-editor/editor/EditorContent";
import { EditorInlineMenus } from "@rich-editor/editor/EditorInlineMenus";
import { EditorParagraphMenu } from "@rich-editor/editor/EditorParagraphMenu";
import { EditorEmbedBar } from "@rich-editor/editor/EditorEmbedBar";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import classNames from "classnames";
import { Provider } from "react-redux";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { useUniqueID } from "@library/utility/idUtils";
import { RichEditorEmbedOptions } from "@library/embeddedContent/menus/richEditorEmbedOptions";

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

    const embedOptionsID = useUniqueID("embedOptions");

    return (
        <Provider store={store}>
            <DeviceProvider>
                <RichEditorEmbedOptions id={embedOptionsID} visible={true} />
                <Editor
                    isPrimaryEditor={true}
                    legacyMode={true}
                    allowUpload={hasPermission("uploads.add")}
                    isLoading={false}
                >
                    <div className={classNames("richEditor-frame", "InputBox", classes.legacyFrame, classes.root)}>
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
