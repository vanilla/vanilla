/*
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import * as React from "react";

import { CategoryIcon, PlusCircleIcon } from "@library/icons/common";
import { getMeta, onReady, setMeta, t } from "@library/utility/appUtils";
import { useEffect, useState } from "react";

import CategoryInfo from "@library/forms/select/CategoryInfo";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import LocationBreadcrumbs from "@library/navigation/LocationBreadcrumbs";
import { categoryPickerClasses } from "./CategoryPicker.classes";
import classNames from "classnames";
import { locationPickerClasses } from "@library/navigation/locationPickerStyles";
import { pageLocationClasses } from "@library/navigation/pageLocationStyles";

interface ICategoryItemForSelect {
    value: string;
    label: string;
    depth: number;
    breadcrumbs?: ICrumb[];
    description?: string;
    disabled?: boolean;
}
interface IProps {
    categoryInfoOnly?: boolean;
    selectAttributes?: {
        id: string;
        name: string;
    };
    initialValue?: string;
    defaultItem?: {
        label: string;
        value: string;
        breadcrumbs?: null;
        description?: undefined;
    };
    items: ICategoryItemForSelect[];
}

/**
 * New category dropdown with visually hidden select element, to be used when posting discussions
 */
export function CategoryPicker(props: IProps) {
    const { selectAttributes, items, defaultItem, initialValue, categoryInfoOnly } = props;
    const classes = categoryPickerClasses();
    const initialSelectedItem = initialValue ? items.find((item) => item.value == initialValue) : defaultItem;
    const [selectedItem, setSelectedItem] = useState<ICategoryItemForSelect | typeof defaultItem | null>(
        initialSelectedItem!,
    );
    const [selectedValue, setSelectedValue] = useState<string | undefined>(initialValue || defaultItem?.value);
    useMentionContextConnector({ selectedValue });

    const handleChange = (e) => {
        const selectedItem = items.find((item) => item.value == e.target.value)!,
            newSelelectedItem = selectedItem ?? defaultItem;

        setSelectedItem({
            ...newSelelectedItem,
        });

        setSelectedValue(newSelelectedItem.value);
    };

    const isBreadCrumb = selectedItem?.breadcrumbs ?? null;

    const pickerContents = isBreadCrumb ? (
        <LocationBreadcrumbs
            locationData={selectedItem!.breadcrumbs!}
            icon={<CategoryIcon className={"pageLocation-icon"} />}
        />
    ) : (
        <>
            <span className={locationPickerClasses().iconWrapper}>
                <PlusCircleIcon className={"pageLocation-icon"} />
            </span>
            <span className={locationPickerClasses().initialText}>{defaultItem?.label}</span>
        </>
    );

    const id = selectAttributes?.id.split('"')[1];
    const name = selectAttributes?.name.split('"')[1];

    const picker = (
        <div className={classes.pickerWrapper}>
            <label className="sr-only" htmlFor={id}>
                {t("Select a category")}
            </label>

            <select value={selectedValue} id={id} name={name} onChange={handleChange} className={classes.select}>
                {defaultItem?.label && <option value={defaultItem.value}>{defaultItem.label}</option>}
                {items.map((item, i) => {
                    let itemDepth = item.depth > 0 ? item.depth : 1;
                    return (
                        <option key={i} value={item.value} disabled={item.disabled}>
                            {String.fromCharCode(160).repeat(4 * (itemDepth - 1)) + item.label}
                        </option>
                    );
                })}
            </select>
            <span className={classNames(pageLocationClasses().picker, classes.pickerButton)}>{pickerContents}</span>
        </div>
    );

    return (
        <div>
            {!categoryInfoOnly && picker}
            {selectedItem && (
                <CategoryInfo {...{ label: selectedItem?.label, description: selectedItem?.description }} />
            )}
        </div>
    );
}

/**
 * This helper hook will propagate posting context
 * to the MentionsProvider to filter mentions
 *
 * Only for use on legacy post pages
 */
function useMentionContextConnector(props: { selectedValue?: string }) {
    // AIDEV-NOTE: Checks form action URL for groupid parameter to set group mention context
    const [isInitialized, setIsInitialized] = useState(false);
    const [contextData, setContextData] = useState<{ recordType: string; recordID?: string }>({
        recordType: "category",
        recordID: props.selectedValue,
    });

    // Shared polling function to dispatch events when handler is ready
    const dispatchWhenReady = React.useCallback((detail: { recordType: string; recordID?: string }) => {
        const interval = setInterval(() => {
            if (getMeta("mentions.handlerAdded")) {
                window.dispatchEvent(new CustomEvent("mentions.data", { detail }));
                clearInterval(interval);
            }
        }, 100);
        return interval;
    }, []);

    // Initialize context data once on mount
    useEffect(() => {
        // Find the form on the page
        const form = document.querySelector("form");
        let recordType = "category"; // default
        let recordID = props.selectedValue;

        if (form && form.action) {
            try {
                const url = new URL(form.action, window.location.origin);
                const groupId = url.searchParams.get("groupid");

                if (groupId) {
                    recordType = "group";
                    recordID = groupId;
                }
            } catch (error) {
                // If URL parsing fails, stick with defaults
                console.warn("Error parsing form action URL:", error);
            }
        }

        // Set the meta values
        setMeta("mentions.recordType", recordType);
        setMeta("mentions.recordID", recordID);

        // Update context data
        setContextData({ recordType, recordID });
        setIsInitialized(true);

        // Dispatch initial event
        const interval = dispatchWhenReady({ recordType, recordID });
        return () => clearInterval(interval);
    }, [dispatchWhenReady]);

    // Handle category selection changes (only for category context)
    useEffect(() => {
        if (!isInitialized || contextData.recordType !== "category") {
            return;
        }

        const updatedData = { recordType: "category", recordID: props.selectedValue };
        setMeta("mentions.recordID", props.selectedValue);

        const interval = dispatchWhenReady(updatedData);
        return () => clearInterval(interval);
    }, [props.selectedValue, isInitialized, contextData.recordType, dispatchWhenReady]);

    return null;
}

export default CategoryPicker;
