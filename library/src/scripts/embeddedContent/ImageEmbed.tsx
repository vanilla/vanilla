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

    useFocusWatcher(contentRef, newFocusState => {
        setIsFocused(newFocusState);
        window.console.log("new focus value", newFocusState);
    });

    return (
        <EmbedContent type="Image" inEditor={props.inEditor} setContentRef={setContentRef}>
            <div className="embedImage-link u-excludeFromPointerEvents">
                <img className="embedImage-img" src={props.url} alt={props.name} />
                {props.inEditor && isFocused && (
                    <ImageEmbedMenu
                        saveImageMeta={props.saveImageMeta}
                        elementToFocusOnClose={extraProps.imageEmbedRef}
                        isFocused={isFocused}
                        device={props.device}
                    />
                )}
            </div>
        </EmbedContent>
    );
}

export default withDevice(ImageEmbed);
