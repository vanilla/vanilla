/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { MouseEvent, ChangeEvent } from "react";
import EmbedInsertionModule from "@rich-editor/quill/EmbedInsertionModule";
import { withEditor, IWithEditorProps } from "@rich-editor/components/context";
import { uploadImage } from "@library/apiv2";
import { isFileImage } from "@library/utility";
import { image } from "@library/components/icons/editor";

interface IProps extends IWithEditorProps {
    disabled?: boolean;
}

export class EditorUploadButton extends React.Component<IProps, {}> {
    private inputRef: React.RefObject<HTMLInputElement> = React.createRef();

    public render() {
        return (
            <button
                className="richEditor-button richEditor-embedButton richEditor-buttonUpload"
                type="button"
                aria-pressed="false"
                disabled={this.props.disabled}
                onClick={this.onFakeButtonClick}
            >
                {image()}
                <input
                    ref={this.inputRef}
                    onChange={this.onInputChange}
                    className="richEditor-upload"
                    type="file"
                    accept="image/gif, image/jpeg, image/jpg, image/png"
                />
            </button>
        );
    }

    private onFakeButtonClick = (event: MouseEvent<any>) => {
        if (this.inputRef && this.inputRef.current) {
            this.inputRef.current.click();
        }
    };

    private onInputChange = (event: ChangeEvent<any>) => {
        // Grab the first file.
        const file =
            this.inputRef && this.inputRef.current && this.inputRef.current.files && this.inputRef.current.files[0];
        const embedInsertion =
            this.props.quill && (this.props.quill.getModule("embed/insertion") as EmbedInsertionModule);

        if (file && isFileImage(file) && embedInsertion) {
            const imagePromise = uploadImage(file);
            embedInsertion.createEmbed({
                dataPromise: imagePromise,
                loaderData: {
                    type: "image",
                },
            });
        }
    };
}

export default withEditor<IProps>(EditorUploadButton);
