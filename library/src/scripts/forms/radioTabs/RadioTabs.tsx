/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import TabContext from "@library/contexts/TabContext";
import classNames from "classnames";
import { radioTabClasses } from "@library/forms/radioTabs/radioTabStyles";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";

export interface IRadioTabsProps {
    accessibleTitle: string; // Describe what these buttons represent. Hidden from view, for screen readers
    setData: (data: any) => void;
    activeTab?: string | number;
    children: React.ReactNode;
    groupName?: string;
    className?: string;
    childClass?: string;
    classes?: IRadioTabClasses;
}

export interface IRadioTabClasses {
    root?: string;
    tabs?: string;
    tab?: string;
    label?: string;
    input?: string;
    leftTab?: string;
    rightTab?: string;
}

/**
 * Implement what looks like tabs (or other inputs with the classes prop), but what is semantically radio buttons.
 */
export default class RadioTabs extends React.Component<IRadioTabsProps> {
    private groupID;

    public constructor(props) {
        super(props);
        this.groupID = uniqueIDFromPrefix(this.props.groupName || "radioTabsGroup");
    }

    public render() {
        const classes = this.props.classes ?? radioTabClasses();

        const classesInputBlock = inputBlockClasses();
        return (
            <TabContext.Provider
                value={{
                    groupID: this.groupID,
                    setData: this.props.setData,
                    activeTab: this.props.activeTab || 0,
                    childClass: this.props.childClass || "",
                }}
            >
                <fieldset className={classNames(classesInputBlock.root, classes.root, this.props.className)}>
                    <ScreenReaderContent tag="legend">{this.props.accessibleTitle}</ScreenReaderContent>
                    <div className={classes.tabs}>{this.props.children}</div>
                </fieldset>
            </TabContext.Provider>
        );
    }
}
