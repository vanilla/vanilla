/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import DiscussionTagAsset from "@vanilla/addon-vanilla/thread/DiscussionTagAsset";

import React from "react";

export default {
    title: "Widgets/DiscussionTagAsset",
};

export const Default = () => {
    return <DiscussionTagAsset title={"Find more posts tagged with"} tags={LayoutEditorPreviewData.tags()} />;
};
