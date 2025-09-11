/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Heading from "@library/layout/Heading";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import classNames from "classnames";
import { uniqueIDFromPrefix, useUniqueID } from "@library/utility/idUtils";

interface IDropDownSectionProps {
    title: string;
    className?: string;
    level?: 2 | 3;
    children: React.ReactNode;
    noSeparator?: boolean;
}

/**
 * Implements DropDownSection component. It add a heading to a group of elements in a DropDown menu
 */
export default function DropDownSection(props: IDropDownSectionProps) {
    const classes = dropDownClasses.useAsHook();
    const sectionNavTitle = uniqueIDFromPrefix("sectionNavTitle");
    return (
        <>
            {!props.noSeparator && <DropDownItemSeparator />}
            <li className={classNames("dropDown-section", classes.section, props.className)}>
                <nav aria-describedby={sectionNavTitle}>
                    <Heading
                        id={sectionNavTitle}
                        title={props.title}
                        className={classNames("dropDown-sectionHeading", classes.sectionHeading)}
                    />
                    <ul className={classNames("dropDown-sectionContents", classes.sectionContents)}>
                        {props.children}
                    </ul>
                </nav>
            </li>
        </>
    );
}
