/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { DiscussionListModule } from "@library/features/discussions/DiscussionListModule";
import { Widget } from "@library/layout/Widget";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";

interface IProps extends Omit<React.ComponentProps<typeof DiscussionListModule>, "discussions"> {}

export function DiscussionListModulePreview(props: IProps) {
    const discussions = useMemo(() => {
        return LayoutEditorPreviewData.discussions();
    }, []);
    const vars = discussionListVariables();
    return (
        <Widget className={vars.widget.preview.offset}>
            <DiscussionListModule
                {...props}
                discussions={discussions}
                apiParams={{ discussionID: discussions[0].discussionID }}
            />
        </Widget>
    );
}
