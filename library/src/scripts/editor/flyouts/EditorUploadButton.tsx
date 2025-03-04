/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ButtonTypes } from "@library/forms/buttonTypes";
import { UploadButton } from "@library/forms/UploadButton";
import { ImageIcon, AttachmentIcon } from "@library/icons/editorIcons";
import { t } from "@vanilla/i18n";

interface IProps {
    disabled?: boolean;
    type: "file" | "image";
    onUpload: (files: File[]) => void;
}

export default function EditorUploadButton(props: IProps) {
    const text = props.type === "image" ? t("Upload Image") : t("Upload File");

    return (
        <UploadButton
            acceptedMimeTypes={props.type}
            disabled={props.disabled}
            multiple={true}
            buttonType={ButtonTypes.ICON_MENUBAR}
            accessibleTitle={text}
            onUpload={props.onUpload}
        >
            {props.type === "image" ? <ImageIcon /> : <AttachmentIcon />}
        </UploadButton>
    );
}
