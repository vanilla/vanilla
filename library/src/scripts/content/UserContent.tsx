/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useHashScrolling } from "@library/content/hashScrolling";
import { userContentClasses } from "@library/content/userContentStyles";
import className from "classnames";
import React from "react";

interface IProps {
    className?: string;
    content: string;
    ignoreHashScrolling?: boolean;
}

/**
 * A component for placing rendered user content.
 *
 * This will ensure that all embeds/etc are initialized.
 */
export default function UserContent(props: IProps) {
    const classes = userContentClasses();

    useHashScrolling(props.ignoreHashScrolling);

    return (
        <div
            className={className("userContent", props.className, classes.root)}
            dangerouslySetInnerHTML={{ __html: props.content }}
        />
    );
}
