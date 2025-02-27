/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import PostMetaAsset, { ProfileFieldProp } from "@vanilla/addon-vanilla/posts/PostMetaAsset";

interface IProps {
    postFields: ProfileFieldProp[];
}

export function LegacyPostMetaAsset(props: IProps) {
    const homeWidgetOverride = css({
        "& > div > div": {
            padding: "16px!important",
        },
    });
    return (
        <div className={homeWidgetOverride}>
            <PostMetaAsset postFields={props.postFields} />
        </div>
    );
}
