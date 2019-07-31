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
import { EditorEventWall } from "@rich-editor/editor/pieces/EditorEventWall";

interface IProps extends IBaseEmbedProps {
    type: string; // Mime type.
    size: number;
    dateInserted: string;
    name: string;
    width?: number;
    height?: number;
}

/**
 * An embed class for quoted user content on the same site.
 */
export function ImageEmbed(props: IProps) {
    const [contentRef, setContentRef] = useState<HTMLElement | null>(null);
    const [isFocused, setIsFocused] = useState(false);
    const [isOpen, setIsOpen] = useState(false); // For focus inside the modal/popup. It closes.

    const divRef = useRef<HTMLDivElement>(null);

    useFocusWatcher(contentRef, newFocusState => {
        setIsFocused(newFocusState || isOpen);
    });

    const showEmbedMenu = props.inEditor;

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
                {showEmbedMenu && (
                    <ImageEmbedMenu
                        onSave={newValue => {
                            props.syncBackEmbedValue &&
                                props.syncBackEmbedValue({
                                    name: newValue.alt,
                                });
                        }}
                        onToggleOpen={setIsOpen}
                        alt={props.name}
                    />
                )}
            </div>
        </DeviceProvider>
    );
}
