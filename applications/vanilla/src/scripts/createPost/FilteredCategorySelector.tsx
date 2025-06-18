/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PostType } from "@dashboard/postTypes/postType.types";
import { NestedSelect, useNestedOptions } from "@library/forms/nestedSelect";
import {
    getDefaultNestedOptions,
    getFilteredNestedOptions,
} from "@library/forms/nestedSelect/presets/CategoryDropdown";
import { Select } from "@library/json-schema-forms";
import { ComponentProps, useEffect, useMemo, useState } from "react";
import { getSiteSection } from "@library/utility/appUtils";

interface IProps extends ComponentProps<typeof NestedSelect> {
    postTypeID?: PostType["postTypeID"];
    limit?: number;
    filterByCurrentSiteSection?: boolean;
}

/**
 * This bespoke flavour of the nested select component is decidedly opinionated for
 * use in thr new posting form, filterable by post type.
 */
export function FilteredCategorySelector(props: IProps) {
    const [ownInputValue, setOwnInputValue] = useState<string | undefined>(props.inputValue);
    const siteSection = getSiteSection();
    const siteSectionID = siteSection?.sectionID ?? "0";

    const optionalSiteSectionParams = props.filterByCurrentSiteSection
        ? `siteSectionID=${siteSectionID}&includeParentCategory=1`
        : "";

    const optionalPostTypeIDParams = props.postTypeID ? `postTypeID=${props.postTypeID}&` : "";

    const optionsLookup: Select.LookupApi = {
        searchUrl: `/categories/search?query=%s&filterDiscussionsAdd=true&${optionalSiteSectionParams}`,
        singleUrl: "/categories/%s",
        defaultListUrl: `/categories?filterDiscussionsAdd=true&outputFormat=flat&limit=100&${optionalPostTypeIDParams}&${optionalSiteSectionParams}`,
        labelKey: "name",
        valueKey: "categoryID",
        processOptions: ownInputValue ? getFilteredNestedOptions : getDefaultNestedOptions,
    };

    const [cachedOptions, setCachedOptions] = useState<Select.Option[]>([]);

    const optionsState = useNestedOptions({
        optionsLookup,
        searchQuery: ownInputValue,
        initialValues: props.initialValues,
    });

    const filteredOptions = useMemo(() => {
        if (cachedOptions.length > 0) {
            let dedupedOptions = cachedOptions.filter(
                (option1, index, array) => array.findIndex((option2) => option2.value === option1.value) === index,
            );
            if (props.postTypeID) {
                return dedupedOptions.filter((option) => option?.data?.allowedPostTypeIDs?.includes(props.postTypeID));
            }
        }
        return cachedOptions;
    }, [cachedOptions, props.postTypeID]);

    useEffect(() => {
        setCachedOptions(optionsState.options);
    }, [optionsState]);

    return (
        <>
            <NestedSelect
                {...props}
                onInputValueChange={(value) => setOwnInputValue(value)}
                options={filteredOptions}
                isClearable
                required
            />
        </>
    );
}
