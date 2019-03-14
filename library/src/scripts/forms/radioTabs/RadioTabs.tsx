/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { uniqueIDFromPrefix } from "../utility/idUtils";
import classNames from "classnames";
import TabContext from "../contexts/TabContext";
import { radioTabCss } from "@library/forms/radioTabs/radioTabStyles";

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
        radioTabCss();

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
                        "inputBlock radioButtonsAsTabs _searchBarAdvanced-searchIn",
                        this.props.className,
                    )}
                >
                    <legend className="sr-only">{this.props.accessibleTitle}</legend>
                    <div className="radioButtonsAsTabs-tabs">{this.props.children}</div>
                </fieldset>
            </TabContext.Provider>
        );
    }
}
