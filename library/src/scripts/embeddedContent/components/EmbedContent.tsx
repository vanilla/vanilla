/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { EmbedButton } from "@library/embeddedContent/components/EmbedButton";
import { embedContentClasses } from "@library/embeddedContent/components/embedStyles";
import { EMBED_FOCUS_CLASS } from "@library/embeddedContent/embedConstants";
import { useEmbedContext } from "@library/embeddedContent/IEmbedContext";
import { DeleteIcon } from "@library/icons/common";
import { EmbedMenu } from "@rich-editor/editor/pieces/EmbedMenu";
import classNames from "classnames";
import React from "react";

interface IProps {
    className?: string;
    type: string;
    description?: string;
    children?: React.ReactNode;
    noBaseClass?: boolean;
    isSmall?: boolean;
    embedActions?: React.ReactNode;
}

export const EmbedContent = React.forwardRef<HTMLDivElement, IProps>(function EmbedContent(props: IProps, ref) {
    const { inEditor, isSelected, deleteSelf, descriptionID } = useEmbedContext();
    const classes = embedContentClasses();

    return (
        <div
            aria-describedby={descriptionID}
            aria-label={"External embed content - " + props.type}
            className={classNames(props.className, classes.root, !props.noBaseClass && "embedExternal-content", {
                [EMBED_FOCUS_CLASS]: inEditor,
                [classes.small]: props.isSmall,
            })}
            tabIndex={inEditor ? -1 : undefined} // Should only as a whole when inside the editor.
            ref={ref}
        >
            {props.children}
            {inEditor && isSelected && (
                <EmbedMenu>
                    {props.embedActions}
                    <EmbedButton onClick={deleteSelf}>
                        <DeleteIcon />
                    </EmbedButton>
                </EmbedMenu>
            )}
        </div>
    );
});
