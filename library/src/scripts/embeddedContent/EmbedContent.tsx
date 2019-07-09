/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { FOCUS_CLASS } from "@library/embeddedContent/embedService";
import classNames from "classnames";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@library/utility/appUtils";

interface IProps {
    className?: string;
    type: string;
    description?: string;
    children?: React.ReactNode;
    inEditor?: boolean;
}

export function EmbedContent(props: IProps) {
    const id = useUniqueID("richEditor-embed-description-");
    const defaultDesc = t("richEditor.externalEmbed.description");

    return (
        <div
            aria-describedby={id}
            aria-label={"External embed content - " + props.type}
            className={classNames("embedExternal-content", props.className, { [FOCUS_CLASS]: props.inEditor })}
            tabIndex={props.inEditor ? -1 : undefined} // Should only as a whole when inside the editor.
        >
            <span className="sr-only">{props.description || defaultDesc}</span>
            {props.children}
        </div>
    );
}
