/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import NewPostMenu from "@library/newPostMenu/NewPostMenu";
import React from "react";

interface IProps extends Omit<React.ComponentProps<typeof NewPostMenu>, "items"> {}

export function NewPostMenuPreview(props: IProps) {
    return (
        <NewPostMenu
            {...props}
            items={LayoutEditorPreviewData.getPostTypes({ ...props })}
            postableDiscussionTypes={["discussion", "question", "poll"]}
            disableDropdownItemsClick
        />
    );
}
