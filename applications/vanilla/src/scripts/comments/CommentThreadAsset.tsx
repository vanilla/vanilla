/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { CommentThreadAssetFlat } from "@vanilla/addon-vanilla/comments/CommentThreadAsset.flat";
import { CommentThreadAssetNested } from "@vanilla/addon-vanilla/comments/CommentThreadAsset.nested";

type IProps =
    | (React.ComponentProps<typeof CommentThreadAssetNested> & { threadStyle: "nested" })
    | (React.ComponentProps<typeof CommentThreadAssetFlat> & { threadStyle: "flat" });

export default function CommentThreadAsset(props: IProps) {
    const { threadStyle, ...rest } = props;

    return (
        <div className={classes.root}>
            {threadStyle === "flat" && <CommentThreadAssetFlat {...(rest as any)} />}
            {threadStyle === "nested" && <CommentThreadAssetNested {...(rest as any)} />}
        </div>
    );
}

const classes = {
    root: css({
        marginTop: 18,
    }),
};
