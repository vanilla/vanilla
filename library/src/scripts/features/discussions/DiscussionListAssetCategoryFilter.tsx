/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useMemo, useState } from "react";
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
    isMobile?: boolean;
}

/**
 * Component for displaying an discussion category follow filters
 */
export default function DiscussionListAssetCategoryFilter(props: IProps) {
    const { filter, onFilterChange, isMobile } = props;
    const [followFilter, setFollowFilter] = useState<DiscussionsCategoryFollowFilter>(props.filter);

    const options: ISelectBoxItem[] = [
        { name: t("All"), value: "all" },
        { name: t("Following"), value: "followed" },
    ];

    const activeOption = useMemo(() => {
        return options.find((option) => option.value === filter);
    }, [filter]);

    const id = uniqueIDFromPrefix("discussionCategoryFilter");

    return (
        <div className={discussionListClasses().filterAndSortingContainer}>
            {!isMobile && (
                <span id={id} className={discussionListClasses().filterAndSortingLabel}>
                    {t("Categories")}:
                </span>
            )}
            <SelectBox
                className={discussionListClasses().filterAndSortingDropdown}
                buttonType={ButtonTypes.TEXT_PRIMARY}
                options={options}
                describedBy={id}
                value={activeOption}
                renderLeft={false}
                horizontalOffset={true}
                offsetPadding={true}
                onChange={({ value }) => {
                    onFilterChange(value === "followed" ? true : false);
                }}
            />
        </div>
    );
}
