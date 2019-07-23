/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { RefObject, useEffect, useRef, useState, useLayoutEffect, useCallback } from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { IImageMeta, ImageEmbedMenu } from "@rich-editor/editor/pieces/ImageEmbedMenu";
import { debuglog } from "util";
import { IDeviceProps, withDevice } from "@library/layout/DeviceContext";
import { useFocusWatcher } from "@vanilla/react-utils";
import classNames from "classnames";
import { embedMenuClasses } from "@rich-editor/editor/pieces/embedMenuStyles";
import { unit } from "@library/styles/styleHelpers";
import { embedContainerVariables } from "@library/embeddedContent/embedStyles";

interface IProps extends IBaseEmbedProps {
    type: string; // Mime type.
    size: number;
    dateInserted: string;
    name: string;
    width?: number;
    height?: number;
    saveImageMeta?: () => IImageMeta;
}

/**
 * An embed class for quoted user content on the same site.
 */
export function ImageEmbed(props: IProps) {
    const { inEditor, type, size, dateInserted, name, width, height, saveImageMeta } = props;
    const extraProps: any = {};

    const [contentRef, setContentRef] = useState<HTMLElement | null>(null);
    const [isFocused, setIsFocused] = useState(false);
    const [isOpen, setIsOpen] = useState(false); // For focus inside the modal/popup. It closes.

    const divRef = useRef<HTMLDivElement>(null);

    useFocusWatcher(contentRef, newFocusState => {
        setIsFocused(newFocusState || isOpen);
    });

    return (
        <div className="embedExternal-content" style={{ position: "relative" }} ref={setContentRef}>
            <EmbedContent type="Image" noBaseClass inEditor={props.inEditor}>
                <div
                    ref={divRef}
                    className={classNames(
                        "embedImage-link",
                        "u-excludeFromPointerEvents",
                        embedMenuClasses().imageContainer,
                    )}
                >
                    <img className="embedImage-img" src={props.url} alt={props.name} />
                </div>
            </EmbedContent>
            {props.inEditor && (isFocused || isOpen) && (
                <ImageEmbedMenu
                    saveImageMeta={props.saveImageMeta}
                    elementToFocusOnClose={extraProps.imageEmbedRef}
                    setIsOpen={setIsOpen}
                />
            )}
        </div>
    );
}
