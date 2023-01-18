/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { EditorEmbedMenu } from "@library/editor/pieces/EmbedMenu";
import { EmbedButton } from "@library/embeddedContent/components/EmbedButton";
import { embedContentClasses } from "@library/embeddedContent/components/embedStyles";
import { EMBED_FOCUS_CLASS } from "@library/embeddedContent/embedConstants";
import { useEmbedContext } from "@library/embeddedContent/IEmbedContext";
import { DeleteIcon } from "@library/icons/common";
import { MenuBar } from "@library/MenuBar/MenuBar";
import { MenuBarItem } from "@library/MenuBar/MenuBarItem";
import { cx } from "@library/styles/styleShim";
import { t } from "@vanilla/i18n";
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
    const { inEditor, isSelected, isNewEditor, deleteSelf, descriptionID } = useEmbedContext();
    const classes = embedContentClasses();

    return (
        <div
            aria-describedby={descriptionID}
            aria-label={"External embed content - " + props.type}
            className={cx(props.className, classes.root, !props.noBaseClass && "embedExternal-content", {
                [EMBED_FOCUS_CLASS]: inEditor && !isNewEditor,
                [classes.small]: props.isSmall,
            })}
            tabIndex={inEditor && !isNewEditor ? -1 : undefined} // Should only as a whole when inside the editor.
            ref={ref}
        >
            {props.children}
            {(!isNewEditor || props.embedActions) && inEditor && isSelected && (
                <MenuBar className={classes.menuBar}>
                    {props.embedActions as any}
                    <MenuBarItem icon={<DeleteIcon />} accessibleLabel={t("Delete item")} onActivate={deleteSelf} />
                </MenuBar>
            )}
        </div>
    );
});
