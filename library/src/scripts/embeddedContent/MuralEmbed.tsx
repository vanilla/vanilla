/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { EmbedContainer } from "@library/embeddedContent/components/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/components/EmbedContent";

interface IProps extends IBaseEmbedProps {}

/**
 * A class for rendering Mural embeds.
 */
export function MuralEmbed(props: IProps): JSX.Element {
    return (
        <EmbedContainer className="embedIFrame">
            <EmbedContent type={props.embedType}>
                <iframe
                    width="100%"
                    height="480px"
                    style={{
                        minHeight: 480,
                        backgroundColor: "#f4f4f4",
                    }}
                    sandbox="allow-same-origin allow-scripts allow-modals allow-popups allow-popups-to-escape-sandbox"
                    src={props.url}
                    // className="embedIFrame-iframe"
                    frameBorder={0}
                />
            </EmbedContent>
        </EmbedContainer>
    );
}
