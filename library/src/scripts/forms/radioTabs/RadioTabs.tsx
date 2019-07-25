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

interface IProps {
    prefix: string;
    accessibleTitle: string; // Describe what these buttons represent. Hidden from view, for screen readers
    className?: string;
    setData: (data: any) => void;
    children: React.ReactNode;
    activeTab: string | number;
    childClass?: string;
}

/**
 * Implement what looks like tabs, but what is semantically radio buttons.
 */
export default class RadioTabs extends React.Component<IProps> {
    private groupID;

    public constructor(props) {
        super(props);
        this.groupID = uniqueIDFromPrefix(this.props.prefix);
    }

    public render() {
        const classes = radioTabClasses();
        return (
            <TabContext.Provider
                value={{
                    groupID: this.groupID,
                    setData: this.props.setData,
                    activeTab: this.props.activeTab,
                    childClass: this.props.childClass || "",
                }}
            >
                <fieldset
                    className={classNames(
                        "_searchBarAdvanced-searchIn",
                        "inputBlock",
                        classes.root,
                        this.props.className,
                    )}
                >
                    <ScreenReaderContent tag="legend">{this.props.accessibleTitle}</ScreenReaderContent>
                    <div className={classes.tabs}>{this.props.children}</div>
                </fieldset>
            </TabContext.Provider>
        );
    }
}
