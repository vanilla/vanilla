import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { getMeta } from "@library/utility/appUtils";
import previewData from "@library/widget-fragments/BannerFragment.previewData";
import { PostItemFragmentContext } from "@library/widget-fragments/PostItemFragment.context";
import type PostItem from "@vanilla/injectables/PostItemFragment";
import { useState } from "react";

export default function PostItemFragmentPreview(props: { previewData: PostItem.Props; children?: React.ReactNode }) {
    const { hasPermission } = usePermissionsContext();
    return (
        <PostItemFragmentContext.Provider
            value={{
                ...props.previewData,
                isChecked: props.previewData.isChecked ?? false,
                isCheckDisabled: false,
                showCheckbox:
                    (hasPermission("discussions.manage") && getMeta("ui.useAdminCheckboxes", false)) ||
                    props.previewData.isChecked,
                onCheckboxChange: () => {},
            }}
        >
            {props.children}
            {props.children}
            {props.children}
        </PostItemFragmentContext.Provider>
    );
}
