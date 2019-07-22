/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useCallback, useEffect, useState } from "react";
import className from "classnames";
import { initAllUserContent } from "@library/content";
import { userContentClasses } from "@library/content/userContentStyles";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import { forceRenderStyles } from "typestyle";
import { useHashScrolling } from "@library/content/hashScrolling";

interface IProps {
    className?: string;
    content: string;
}

/**
 * A component for placing rendered user content.
 *
 * This will ensure that all embeds/etc are initialized.
 */
export default function UserContent(props: IProps) {
    const classes = userContentClasses();

    useHashScrolling();

    return (
        <div
            className={className("userContent", props.className, classes.root)}
            dangerouslySetInnerHTML={{ __html: props.content }}
        />
    );
}
