/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";

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
    return (
        <EmbedContainer>
            <EmbedContent type="Image" inEditor={props.inEditor}>
                <div className="embedImage-link">
                    <img className="embedImage-img" src={props.url} alt={props.name} />
                </div>
            </EmbedContent>
        </EmbedContainer>
    );
}
