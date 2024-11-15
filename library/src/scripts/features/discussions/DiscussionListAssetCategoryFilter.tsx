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
import { useCurrentUser } from "@library/features/users/userHooks";
import { getMeta } from "@library/utility/appUtils";

export type DiscussionsCategoryFollowFilter = "all" | "followed" | "suggested";
interface IProps {
    filter: DiscussionsCategoryFollowFilter;
    onFilterChange: (newFilter: string) => void;
    className?: string;
    isMobile?: boolean;
}

/**
 * Component for displaying an discussion category follow filters
 */
export default function DiscussionListAssetCategoryFilter(props: IProps) {
    const { filter, onFilterChange, isMobile } = props;

    const currentUser = useCurrentUser();
    const suggestedContentEnabled = getMeta("suggestedContentEnabled", false);

    let options: ISelectBoxItem[] = [{ name: t("All"), value: "all" }];

    if (currentUser?.userID ?? 0 > 0) {
        options.push({ name: t("Followed Categories"), value: "followed" });
        if (suggestedContentEnabled) {
            options.push({ name: t("Suggested Content"), value: "suggested" });
        }
    }

    const activeOption = useMemo(() => {
        return options.find((option) => option.value === filter);
    }, [filter]);

    const id = uniqueIDFromPrefix("discussionCategoryFilter");

    return (
        <>
            {options.length > 1 ? (
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
                        name={t("Categories")}
                        describedBy={id}
                        value={activeOption}
                        renderLeft={false}
                        horizontalOffset={true}
                        offsetPadding={true}
                        onChange={({ value }) => {
                            onFilterChange(value);
                        }}
                    />
                </div>
            ) : null}
        </>
    );
}
