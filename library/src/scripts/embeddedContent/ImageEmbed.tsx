/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { RefObject, useEffect, useRef, useState, useLayoutEffect } from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { IImageMeta, ImageEmbedMenu } from "@library/embeddedContent/menus/ImageEmbedMenu";
import { debuglog } from "util";
import { IDeviceProps, withDevice } from "@library/layout/DeviceContext";
import { useFocusWatcher } from "@vanilla/react-utils";
import { EmbedMenu } from "@library/embeddedContent/EmbedMenu";
import classNames from "classnames";
import { embedMenuClasses } from "@library/embeddedContent/menus/embedMenuStyles";

interface IProps extends IBaseEmbedProps, IDeviceProps {
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
    const [pastFocus, setPastFocus] = useState(false);
    const [isOpen, setIsOpen] = useState(false);

    const divRef = useRef<HTMLDivElement>(null);

    useFocusWatcher(contentRef, newFocusState => {
        if (pastFocus !== newFocusState) {
            setIsFocused(newFocusState);
            setPastFocus(newFocusState);
            window.console.log("new focus value", newFocusState);
        }
    });

    return (
        <EmbedContent type="Image" inEditor={props.inEditor} setContentRef={setContentRef}>
            <div
                ref={divRef}
                className={classNames(
                    "embedImage-link",
                    "u-excludeFromPointerEvents",
                    embedMenuClasses().imageContainer,
                )}
            >
                <EmbedMenu />
                <img className="embedImage-img" src={props.url} alt={props.name} />
                {props.inEditor && (isFocused || isOpen) && (
                    <ImageEmbedMenu
                        saveImageMeta={props.saveImageMeta}
                        elementToFocusOnClose={extraProps.imageEmbedRef}
                        isFocused={isFocused}
                        device={props.device}
                        setIsOpen={setIsOpen}
                        isOpen={isOpen}
                    />
                )}
            </div>
        </EmbedContent>
    );
}

export default withDevice(ImageEmbed);
