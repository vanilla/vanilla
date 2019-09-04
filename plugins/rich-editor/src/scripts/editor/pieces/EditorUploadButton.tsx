/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import EmbedInsertionModule from "@rich-editor/quill/EmbedInsertionModule";
import { IWithEditorProps } from "@rich-editor/editor/context";
import { withEditor } from "@rich-editor/editor/withEditor";
import { isFileImage } from "@vanilla/utils";
import { richEditorClasses } from "@rich-editor/editor/richEditorStyles";
import { IconForButtonWrap } from "@rich-editor/editor/pieces/IconForButtonWrap";
import { AttachmentIcon, ImageIcon } from "@library/icons/editorIcons";

interface IProps extends IWithEditorProps {
    disabled?: boolean;
    type: "file" | "image";
    allowedMimeTypes: string[];
    legacyMode: boolean;
}

export class EditorUploadButton extends React.Component<IProps, {}> {
    private inputRef: React.RefObject<HTMLInputElement> = React.createRef();

    public render() {
        const classesRichEditor = richEditorClasses(this.props.legacyMode);
        return (
            <button
                className={classNames(
                    "richEditor-button",
                    "richEditor-embedButton",
                    "richEditor-buttonUpload",
                    classesRichEditor.button,
                )}
                type="button"
                aria-pressed="false"
                disabled={this.props.disabled}
                onClick={this.onFakeButtonClick}
            >
                <IconForButtonWrap icon={this.icon} />
                <input
                    ref={this.inputRef}
                    onChange={this.onInputChange}
                    className={classNames("richEditor-upload", classesRichEditor.upload)}
                    type="file"
                    accept={this.inputAccepts}
                />
            </button>
        );
    }

    /**
     * Determine if a particular mime type is an image mimeType.
     */
    private isMimeTypeImage = (mimeType: string) => mimeType.startsWith("image/");

    /**
     * Get the icon to display for the input.
     */
    private get icon(): JSX.Element {
        switch (this.props.type) {
            case "file":
                return <AttachmentIcon />;
            case "image":
                return <ImageIcon />;
        }
    }

    /**
     * Get an "accepts" mimeTypes string for the file upload input.
     */
    private get inputAccepts(): string {
        switch (this.props.type) {
            case "file": {
                const types = this.props.allowedMimeTypes.filter(type => !this.isMimeTypeImage(type));
                return types.join(", ");
            }
            case "image": {
                const types = this.props.allowedMimeTypes.filter(this.isMimeTypeImage);
                return types.join(",");
            }
        }
    }

    /**
     * Pass through our fake button to be a click on the file upload (which can't be styled).
     */
    private onFakeButtonClick = (event: React.MouseEvent<any>) => {
        if (this.inputRef && this.inputRef.current) {
            this.inputRef.current.click();
        }
    };

    /**
     * Handle the change of the file upload input.
     */
    private onInputChange = () => {
        // Grab the first file.
        const file =
            this.inputRef && this.inputRef.current && this.inputRef.current.files && this.inputRef.current.files[0];
        const embedInsertion =
            this.props.quill && (this.props.quill.getModule("embed/insertion") as EmbedInsertionModule);

        if (file && embedInsertion) {
            if (this.props.type === "image" && isFileImage(file)) {
                embedInsertion.createImageEmbed(file);
            } else {
                embedInsertion.createFileEmbed(file);
            }
        }
    };
}

export default withEditor<IProps>(EditorUploadButton);
