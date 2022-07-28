/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import LeaderboardWidget from "@library/leaderboardWidget/LeaderboardWidget";

interface IProps extends Omit<React.ComponentProps<typeof LeaderboardWidget>, "leaders"> {}

export function LeaderboardWidgetPreview(props: IProps) {
    return <LeaderboardWidget {...props} leaders={LayoutEditorPreviewData.leaders()} />;
}
