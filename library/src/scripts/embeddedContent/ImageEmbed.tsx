/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IBaseEmbedProps, FOCUS_CLASS } from "@library/embeddedContent/embedService";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { embedMenuClasses } from "@rich-editor/editor/pieces/embedMenuStyles";
import { ImageEmbedMenu } from "@rich-editor/editor/pieces/ImageEmbedMenu";
import classNames from "classnames";
import React, { useRef, useState } from "react";

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
    const contentRef = useRef<HTMLDivElement>(null);
    const [isOpen, setIsOpen] = useState(false);

    return (
        <DeviceProvider>
            <div ref={contentRef} className={classNames("embedImage", embedMenuClasses().imageContainer)}>
                <div className="embedImage-link">
                    <img
                        className={classNames("embedImage-img", FOCUS_CLASS)}
                        src={props.url}
                        alt={props.name}
                        tabIndex={props.inEditor ? -1 : undefined}
                    />
                </div>
                {props.inEditor && props.isSelected && (
                    <ImageEmbedMenu
                        onVisibilityChange={setIsOpen}
                        onSave={newValue => {
                            props.syncBackEmbedValue &&
                                props.syncBackEmbedValue({
                                    name: newValue.alt,
                                });
                            props.selectSelf && props.selectSelf();
                        }}
                        initialAlt={props.name}
                    />
                )}
            </div>
        </DeviceProvider>
    );
}
