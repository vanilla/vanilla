/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Button from "@library/forms/Button";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { getMeta } from "@library/utility/appUtils";
import { useRef, useState } from "react";

type IProps = Omit<React.ComponentProps<typeof Button>, "onClick"> & {
    accessibleTitle: string;
    acceptedMimeTypes?: "files" | "images" | React.InputHTMLAttributes<any>["accept"];
} & (
        | {
              multiple: true;
              onUpload: (files: File[]) => void;
          }
        | {
              multiple?: false;
              onUpload: (file: File) => void;
          }
    );

export function UploadButton(props: IProps) {
    const { accessibleTitle, multiple, onUpload, acceptedMimeTypes, children, ...buttonProps } = props;
    const [uploadCount, setUploadCount] = useState(0);
    const inputRef = useRef<HTMLInputElement | null>(null);

    return (
        <Button {...buttonProps} onClick={() => inputRef.current?.click()}>
            <ScreenReaderContent>{props.accessibleTitle}</ScreenReaderContent>
            {children}
            <ScreenReaderContent>
                <input
                    onChange={() => {
                        const files = inputRef && inputRef.current && inputRef.current.files && inputRef.current.files;
                        if (files) {
                            const filesArray = Array.from(files);
                            if (multiple) {
                                onUpload(filesArray);
                            } else {
                                const file = filesArray[0];
                                if (file) {
                                    onUpload(file);
                                }
                            }
                            setUploadCount((prev) => prev + 1);
                        }
                    }}
                    accept={resolveAccept(acceptedMimeTypes)}
                    ref={inputRef}
                    // A key is necessary to reset the input.
                    key={uploadCount}
                    type="file"
                />
            </ScreenReaderContent>
        </Button>
    );
}

function resolveAccept(value: IProps["acceptedMimeTypes"]): React.InputHTMLAttributes<any>["accept"] {
    const allowedExtensions: string[] = getMeta("upload.allowedExtensions", []);

    switch (value) {
        case "files": {
            const types = allowedExtensions.filter((type) => !isExtensionImage(type)).map((type) => `.${type}`);
            return types.join(", ");
        }
        case "images":
        case "image": {
            const types = allowedExtensions.filter(isExtensionImage).map((type) => `.${type}`);
            return types.join(",");
        }
        default:
            return allowedExtensions.join(", ");
    }
}

const classes = {};

/**
 * Determine if a particular mime type is an image mimeType.
 */
const isExtensionImage = (fileExtension: string) =>
    ["jpg", "jpeg", "png", "gif", "bmp", "svg", "webp", "heic", "heif", "tiff", "tif", "ico"].includes(fileExtension);
