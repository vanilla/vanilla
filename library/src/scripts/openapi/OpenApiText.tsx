/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, cx } from "@emotion/css";
import { userContentClasses } from "@library/content/UserContent.styles";
import ReactMarkdown from "react-markdown";

export function OpenApiText(props: { content: string | undefined; className?: string }) {
    if (!props.content) {
        return <></>;
    }
    return (
        <ReactMarkdown className={cx(userContentClasses().root, classes.content, props.className)}>
            {props.content}
        </ReactMarkdown>
    );
}

const classes = {
    content: css({
        fontSize: 14,
    }),
};
