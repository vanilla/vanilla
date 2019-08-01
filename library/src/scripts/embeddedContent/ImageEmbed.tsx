/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { embedMenuClasses } from "@rich-editor/editor/pieces/embedMenuStyles";
import { ImageEmbedMenu } from "@rich-editor/editor/pieces/ImageEmbedMenu";
import classNames from "classnames";
import React, { useRef } from "react";

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
    const divRef = useRef<HTMLDivElement>(null);

    return (
        <DeviceProvider>
            <div className={classNames("embedExternal-content", embedMenuClasses().imageContainer)}>
                <EmbedContent type="Image" className="u-excludeFromPointerEvents" inEditor={props.inEditor}>
                    <div ref={divRef} className="embedImage-link">
                        <img className="embedImage-img" src={props.url} alt={props.name} />
                    </div>
                </EmbedContent>
                {props.inEditor && (
                    <ImageEmbedMenu
                        onSave={newValue => {
                            props.syncBackEmbedValue &&
                                props.syncBackEmbedValue({
                                    name: newValue.alt,
                                });
                        }}
                        alt={props.name}
                    />
                )}
            </div>
        </DeviceProvider>
    );
}
