/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useState } from "react";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { t } from "@vanilla/i18n";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { discussionListClasses } from "@library/features/discussions/DiscussionList.classes";

export type DiscussionsCategoryFollowFilter = "all" | "followed";
interface IProps {
    filter: DiscussionsCategoryFollowFilter;
    onFilterChange: (newFilter: boolean) => void;
    className?: string;
}

/**
 * Component for displaying an discussion category follow filters
 */
export default function DiscussionListAssetCategoryFilter(props: IProps) {
    const [followFilter, setFollowFilter] = useState<DiscussionsCategoryFollowFilter>(props.filter);

    const options: ISelectBoxItem[] = [
        { name: t("All"), value: "all" },
        { name: t("Following"), value: "followed" },
    ];

    const activeOption = options.find((option) => option.value === followFilter);
    const id = uniqueIDFromPrefix("discussionCategoryFilter");

    return (
        <div className={discussionListClasses().categoryFilterContainer}>
            <span id={id} className={discussionListClasses().categoryFilterLabel}>
                {t("Categories")}:
            </span>
            <SelectBox
                className={discussionListClasses().categoryFilterDropdown}
                buttonType={ButtonTypes.TEXT_PRIMARY}
                options={options}
                describedBy={id}
                value={activeOption}
                renderLeft={false}
                horizontalOffset={true}
                offsetPadding={true}
                onChange={(value) => {
                    setFollowFilter(value.value as DiscussionsCategoryFollowFilter);
                    props.onFilterChange(value.value === "followed" ? true : false);
                }}
            />
        </div>
    );
}
