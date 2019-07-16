/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { RefObject } from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { IImageMeta, ImageEmbedMenu } from "@library/embeddedContent/menus/ImageEmbedMenu";

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
    const imageEmbedRef: RefObject<HTMLDivElement> = React.createRef();
    return (
        <EmbedContent type="Image" inEditor={props.inEditor} ref={imageEmbedRef}>
            <div className="embedImage-link">
                {props.inEditor && (
                    <ImageEmbedMenu saveImageMeta={props.saveImageMeta} elementToFocusOnClose={imageEmbedRef} />
                )}
                <img className="embedImage-img" src={props.url} alt={props.name} />
            </div>
        </EmbedContent>
    );
}
