/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { FOCUS_CLASS, useEmbedContext } from "@library/embeddedContent/embedService";
import { embedContentClasses } from "@library/embeddedContent/embedStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { DeleteIcon } from "@library/icons/common";
import { t } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import { EmbedMenu } from "@rich-editor/editor/pieces/EmbedMenu";
import classNames from "classnames";
import React, { useEffect, useRef } from "react";

interface IProps {
    className?: string;
    type: string;
    description?: string;
    children?: React.ReactNode;
    noBaseClass?: boolean;
    isSmall?: boolean;
    setContentRef?: (element: HTMLElement | null) => void;
}

export function EmbedContent(props: IProps) {
    const { inEditor, isSelected, deleteSelf } = useEmbedContext();
    const id = useUniqueID("richEditor-embed-description");
    const defaultDesc = t("richEditor.externalEmbed.description");
    const classes = embedContentClasses();

    return (
        <div
            aria-describedby={id}
            aria-label={"External embed content - " + props.type}
            className={classNames(props.className, classes.root, !props.noBaseClass && "embedExternal-content", {
                [FOCUS_CLASS]: inEditor,
                [classes.small]: props.isSmall,
            })}
            tabIndex={inEditor ? -1 : undefined} // Should only as a whole when inside the editor.
            ref={props.setContentRef}
        >
            <span className="sr-only">{props.description || defaultDesc}</span>
            {props.children}
            {inEditor && isSelected && (
                <EmbedMenu>
                    <Button baseClass={ButtonTypes.ICON} onClick={deleteSelf}>
                        <DeleteIcon />
                    </Button>
                </EmbedMenu>
            )}
        </div>
    );
}
