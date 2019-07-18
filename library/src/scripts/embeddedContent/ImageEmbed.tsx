/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { RefObject, useEffect, useRef, useState } from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { IImageMeta, ImageEmbedMenu } from "@library/embeddedContent/menus/ImageEmbedMenu";
import { useFocusWatcher } from "@library/dom/FocusWatcher";
import { debuglog } from "util";

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

    const contentRef = useRef<HTMLDivElement | null>(null);
    const [isFocused, setIsFocused] = useState(false);

    useFocusWatcher(contentRef.current, newFocusState => {
        setIsFocused(newFocusState);
        debuglog("is focussed");
    });

    return (
        <EmbedContent type="Image" inEditor={props.inEditor} contentRef={contentRef}>
            <div className="embedImage-link u-excludeFromPointerEvents">
                <img className="embedImage-img" src={props.url} alt={props.name} />
                {props.inEditor && isFocused && (
                    <ImageEmbedMenu
                        saveImageMeta={props.saveImageMeta}
                        elementToFocusOnClose={extraProps.imageEmbedRef}
                    />
                )}
            </div>
        </EmbedContent>
    );
}
