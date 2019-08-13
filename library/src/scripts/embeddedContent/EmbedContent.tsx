/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React, { RefObject, useState } from "react";
import { FOCUS_CLASS } from "@library/embeddedContent/embedService";
import classNames from "classnames";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@library/utility/appUtils";
import { embedContentClasses } from "@library/embeddedContent/embedStyles";

interface IProps {
    className?: string;
    type: string;
    description?: string;
    children?: React.ReactNode;
    inEditor?: boolean;
    noBaseClass?: boolean;
    isSmall?: boolean;
    setContentRef?: (element: HTMLElement | null) => void;
}

export function EmbedContent(props: IProps) {
    const id = useUniqueID("richEditor-embed-description");
    const defaultDesc = t("richEditor.externalEmbed.description");
    const classes = embedContentClasses();

    return (
        <div
            aria-describedby={id}
            aria-label={"External embed content - " + props.type}
            className={classNames(props.className, classes.root, !props.noBaseClass && "embedExternal-content", {
                [FOCUS_CLASS]: props.inEditor,
                [classes.small]: props.isSmall,
            })}
            tabIndex={props.inEditor ? -1 : undefined} // Should only as a whole when inside the editor.
            ref={props.setContentRef}
        >
            <span className="sr-only">{props.description || defaultDesc}</span>
            {props.children}
        </div>
    );
}
