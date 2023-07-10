/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc
 * @license Proprietary
 */

import React, { useMemo } from "react";
import startCase from "lodash/startCase";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { discussionListClasses } from "@library/features/discussions/DiscussionList.classes";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { DiscussionListSortOptions } from "@dashboard/@types/api/discussion";
import { Icon } from "@vanilla/icons";

export interface IDiscussionListSortProps {
    currentSort?: DiscussionListSortOptions;
    selectSort: (newValue: DiscussionListSortOptions) => void;
    isMobile?: boolean;
}

export function DiscussionListSort(props: IDiscussionListSortProps) {
    const { currentSort = DiscussionListSortOptions.RECENTLY_COMMENTED, selectSort, isMobile } = props;
    const classes = discussionListClasses();

    const id = useMemo<string>(() => {
        return uniqueIDFromPrefix("discussionSortBy");
    }, []);

    const options = useMemo<ISelectBoxItem[]>(() => {
        return Object.entries(DiscussionListSortOptions).map(([key, value]) => {
            const name = t(startCase(key.toLowerCase()));
            return { name, value } as ISelectBoxItem;
        });
    }, []);

    const value = useMemo<ISelectBoxItem>(() => {
        const newValue = options.find(({ value }) => value === currentSort) as ISelectBoxItem;

        return newValue;
    }, [currentSort, options]);

    const handleOnChange = (newValue: ISelectBoxItem) => {
        selectSort(newValue.value as DiscussionListSortOptions);
    };

    const iconButton = isMobile ? <Icon icon="data-sort-dropdown" /> : null;

    return (
        <div className={classes.filterAndSortingContainer}>
            {!isMobile && (
                <span className={classes.filterAndSortingLabel} id={id}>
                    {t("Sort by")}:
                </span>
            )}
            <SelectBox
                className={classes.filterAndSortingDropdown}
                buttonType={ButtonTypes.TEXT_PRIMARY}
                options={options}
                value={value}
                onChange={handleOnChange}
                describedBy={id}
                renderLeft={false}
                horizontalOffset={true}
                offsetPadding={true}
                overwriteButtonContents={iconButton}
            />
        </div>
    );
}

export default DiscussionListSort;
