/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, cx } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import * as diff from "diff";
import { useMemo } from "react";

interface IProps {
    oldText: string;
    newText: string;
    className?: string;
}

export function PlainTextDiff(props: IProps) {
    const changes = useMemo(() => {
        return diff.diffWordsWithSpace(props.oldText, props.newText);
    }, [props.oldText, props.newText]);

    return (
        <div className={cx(classes.root, props.className)}>
            {changes.map((change, index) => {
                const key = `${change.value}-${index}`;
                if (change.added) {
                    return <ins key={key}>{change.value}</ins>;
                } else if (change.removed) {
                    return <del key={key}>{change.value}</del>;
                } else {
                    return <span key={key}>{change.value}</span>;
                }
            })}
        </div>
    );
}

const classes = {
    root: css({
        "& ins": {
            backgroundColor: "#6aa253",
            textDecoration: "none",
        },
        "& del": {
            backgroundColor: "#b1534e",
            textDecoration: "none",
        },
    }),
};
