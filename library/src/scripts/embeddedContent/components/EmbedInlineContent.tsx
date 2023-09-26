/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EmbedContent } from "@library/embeddedContent/components/EmbedContent";
import { embedContentClasses } from "@library/embeddedContent/components/embedStyles";
import { EMBED_FOCUS_CLASS } from "@library/embeddedContent/embedConstants";
import { useEmbedContext } from "@library/embeddedContent/IEmbedContext";
import { cx } from "@library/styles/styleShim";
import React from "react";

interface IProps extends React.ComponentProps<typeof EmbedContent> {}

export const EmbedInlineContent = React.forwardRef<HTMLSpanElement, IProps>(function EmbedInlineContent(
    props: IProps,
    ref,
) {
    const { inEditor, isNewEditor, descriptionID } = useEmbedContext();
    const classes = embedContentClasses();

    return (
        <span
            aria-describedby={descriptionID}
            aria-label={"External embed content - " + props.type}
            className={cx(props.className, !props.noBaseClass && !inEditor && "embedExternal-content", {
                [EMBED_FOCUS_CLASS]: inEditor && !isNewEditor,
                [classes.small]: props.isSmall,
            })}
            tabIndex={inEditor && !isNewEditor ? -1 : undefined} // Should only as a whole when inside the editor.
            ref={ref}
        >
            {props.children}
        </span>
    );
});
