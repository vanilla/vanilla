/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import * as React from "react";
import { useState } from "react";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import LocationBreadcrumbs from "@library/navigation/LocationBreadcrumbs";
import { CategoryIcon, PlusCircleIcon } from "@library/icons/common";
import { pageLocationClasses } from "@library/navigation/pageLocationStyles";
import { locationPickerClasses } from "@library/navigation/locationPickerStyles";
import { categoryPickerClasses } from "./CategoryPicker.classes";
import classNames from "classnames";
import CategoryInfo from "@library/forms/select/CategoryInfo";

interface ICategoryItem {
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
    items: ICategoryItem[];
}

/**
 * New category dropdown with visually hidden select element, to be used when posting discussions
 */
export function CategoryPicker(props: IProps) {
    const { selectAttributes, items, defaultItem, initialValue, categoryInfoOnly } = props;
    const classes = categoryPickerClasses();
    const initialSelectedItem = initialValue ? items.find((item) => item.value == initialValue) : defaultItem;
    const [selectedItem, setSelectedItem] = useState<ICategoryItem | typeof defaultItem | null>(initialSelectedItem!);
    const [selectedValue, setSelectedValue] = useState<string | undefined>(initialValue || defaultItem?.value);

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

export default CategoryPicker;
