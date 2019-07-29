/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef, useState, useLayoutEffect, useCallback } from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { IImageMeta, ImageEmbedMenu } from "@rich-editor/editor/pieces/ImageEmbedMenu";
import { useFocusWatcher } from "@vanilla/react-utils";
import classNames from "classnames";
import { embedMenuClasses } from "@rich-editor/editor/pieces/embedMenuStyles";
import { DeviceProvider } from "@library/layout/DeviceContext";

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
        console.log(newFocusState, document.activeElement);
        setIsFocused(newFocusState || isOpen);
    });

    return (
        <DeviceProvider>
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
                    <ImageEmbedMenu saveImageMeta={props.saveImageMeta} setIsOpen={setIsOpen} />
                )}
            </div>
        </DeviceProvider>
    );
}
