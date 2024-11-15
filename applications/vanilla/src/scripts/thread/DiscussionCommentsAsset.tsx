/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import DiscussionCommentsAssetFlat from "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset.flat";
import { DiscussionCommentsAssetNested } from "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset.nested";

type IProps =
    | React.ComponentProps<typeof DiscussionCommentsAssetNested>
    | React.ComponentProps<typeof DiscussionCommentsAssetFlat>;

export default function DiscussionCommentsAsset(props: IProps) {
    const threadStyle = props.threadStyle;

    return (
        <>
            {threadStyle === "flat" && <DiscussionCommentsAssetFlat {...(props as any)} threadStyle={"flat"} />}
            {threadStyle === "nested" && <DiscussionCommentsAssetNested {...(props as any)} threadStyle={"nested"} />}
        </>
    );
}
