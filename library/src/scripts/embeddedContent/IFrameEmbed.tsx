/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { PropsWithChildren } from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { EmbedContainer } from "@library/embeddedContent/components/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/components/EmbedContent";
import { EmbedContainerSize } from "@library/embeddedContent/components/EmbedContainerSize";
import { css } from "@emotion/css";
import ConditionalWrap from "@library/layout/ConditionalWrap";

interface IProps extends IBaseEmbedProps {
    height?: HTMLIFrameElement["style"]["height"];
    width?: HTMLIFrameElement["style"]["width"];
}

let _supportFrames = false;

export function supportsFrames(newValue?: boolean): boolean {
    if (newValue != null) {
        _supportFrames = newValue;
    }
    return _supportFrames;
}

function ensureValidSize(val: string): string {
    let value = `${val}`;

    if (value.indexOf("px") >= 0 || value.indexOf("100%") >= 0) {
        return value;
    } else {
        return `${parseInt(value)}px`;
    }
}

function FixedWidthBlockContainer({ children, width }: PropsWithChildren<{ width: string }>) {
    return <div style={{ display: "block", width: `${width}`, maxWidth: "100%" }}>{children}</div>;
}
/**
 * A class for rendering iframe embeds
 */
export function IFrameEmbed(props: IProps): JSX.Element {
    const width = props.width ? ensureValidSize(props.width) : "100%";
    //assuming we don't need to maintain some aspect ratio
    const style: React.CSSProperties = {
        width,
        maxWidth: "100%",
        height: props.height ? ensureValidSize(props.height) : "auto",
        maxHeight: "80vh",
        //if we allow iframe to capture the pointer events, we cannot click to select the embed
        pointerEvents: props.inEditor && !props.isSelected ? "none" : undefined,
    };

    return (
        <ConditionalWrap
            condition={width.indexOf("px") >= 0}
            component={FixedWidthBlockContainer}
            componentProps={{ width }}
        >
            <EmbedContainer data-testid="iframe-embed" size={EmbedContainerSize.FULL_WIDTH} withShadow={false}>
                <EmbedContent
                    type={props.embedType}
                    embedActions={<></>} //empty fragment is passed just to make the delete button show up
                >
                    <iframe
                        // We're excluding the following capabiltiies.
                        // allow-popups - Don't allow popups (window.open(), showModalDialog(), target=”_blank”, etc.).
                        // allow-pointer-lock - Don't allow the frame to lock the pointer.
                        // allow-top-navigation - Don't allow the document to break out of the frame by navigating the top-level window.
                        sandbox="allow-same-origin allow-scripts allow-forms"
                        src={props.url}
                        frameBorder={0}
                        style={style}
                        className={css({
                            //fighting ".embedExternal-content > *" rule
                            margin: "0 !important",
                        })}
                    />
                </EmbedContent>
            </EmbedContainer>
        </ConditionalWrap>
    );
}
