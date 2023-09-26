/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { richEditorClasses } from "@library/editor/richEditorStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { AttachmentIcon, ImageIcon } from "@library/icons/editorIcons";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { getMeta } from "@library/utility/appUtils";
import { t } from "@vanilla/i18n/src";
import classNames from "classnames";
import React from "react";

interface IProps {
    disabled?: boolean;
    type: "file" | "image";
    legacyMode: boolean;
    onUpload: (files: File[]) => void;
}

export default class EditorUploadButton extends React.Component<IProps, { uploadCount: number }> {
    public state = {
        uploadCount: 0,
    };
    private inputRef: React.RefObject<HTMLInputElement> = React.createRef();

    public render() {
        const classesRichEditor = richEditorClasses(this.props.legacyMode);

        const text = this.props.type === "image" ? t("Upload Image") : t("Upload File");

        return (
            <Button
                buttonType={ButtonTypes.ICON_MENUBAR}
                type="button"
                disabled={this.props.disabled}
                onClick={this.onFakeButtonClick}
                title={text}
            >
                <ScreenReaderContent>{text}</ScreenReaderContent>
                {this.icon}
                <input
                    key={this.state.uploadCount}
                    ref={this.inputRef}
                    onChange={this.onInputChange}
                    className={classNames("richEditor-upload", classesRichEditor.upload)}
                    multiple
                    type="file"
                    accept={this.inputAccepts}
                />
            </Button>
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
    private get inputAccepts(): string | undefined {
        const allowedMimeTypes: string[] = getMeta("upload.allowedExtensions", []);
        if (allowedMimeTypes.length < 1) {
            return undefined;
        }

        switch (this.props.type) {
            case "file": {
                const types = allowedMimeTypes.filter((type) => !this.isMimeTypeImage(type));
                return types.join(", ");
            }
            case "image": {
                const types = allowedMimeTypes.filter(this.isMimeTypeImage);
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
        const files =
            this.inputRef && this.inputRef.current && this.inputRef.current.files && this.inputRef.current.files;

        if (files) {
            this.props.onUpload(Array.from(files));
            this.setState({ uploadCount: this.state.uploadCount + 1 });
        }
    };
}
