/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { HTMLAttributes } from "react";
import { embedContainerClasses } from "@library/embeddedContent/embedStyles";
import classNames from "classnames";

export function EmbedTitle(props: HTMLAttributes<HTMLHeadingElement>) {
    const classes = embedContainerClasses();
    return <h3 {...props} className={classNames(classes.title, props.className)} />;
}
