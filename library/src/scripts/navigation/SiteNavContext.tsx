/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { logWarning } from "@vanilla/utils";

type RecordToggle = (recordType: string, recordID: number) => void;

interface ISiteNavCtx {
    categoryRecordType: string;
    toggleItem: RecordToggle;
    openItem: RecordToggle;
    closeItem: RecordToggle;
    openRecords: {
        [recordType: string]: Set<number>;
    };
}

const noop = () => {
    logWarning("It looks like you forgot to initialize your SiteNavContext. Be sure to use `<SiteNavProvider />`");
};

const defaultContext: ISiteNavCtx = {
    categoryRecordType: "item",
    toggleItem: noop,
    openItem: noop,
    closeItem: noop,
    openRecords: {},
};

export const SiteNavContext = React.createContext<ISiteNavCtx>(defaultContext);

interface IProps {
    children: React.ReactNode;
    categoryRecordType: string;
}

interface IState {
    openRecords: {
        [recordType: string]: Set<number>;
    };
}

/**
 * Context provider that tracks the open/closed states of site nav menus.
 * This wraps `SiteNavContext.Provider` with some nice defaults.
 *
 * This helps to keep nav toggles consistent across page navigations.
 */
export default class SiteNavProvider extends React.Component<IProps, IState> {
    public state: IState = {
        openRecords: {},
    };

    /**
     * @inheritdoc
     */
    public render() {
        return (
            <SiteNavContext.Provider
                value={{
                    categoryRecordType: this.props.categoryRecordType,
                    openItem: this.openItem,
                    closeItem: this.closeItem,
                    toggleItem: this.toggleItem,
                    openRecords: this.state.openRecords,
                }}
            >
                {this.props.children}
            </SiteNavContext.Provider>
        );
    }

    /**
     * Open an item in the nav.
     */
    private openItem = (recordType: string, recordID: number) => {
        const records = this.state.openRecords[recordType] || new Set();
        records.add(recordID);
        this.setState({
            openRecords: {
                ...this.state.openRecords,
                [recordType]: records,
            },
        });
    };

    /**
     * Close an item in the nav.
     */
    private closeItem = (recordType: string, recordID: number) => {
        const records = this.state.openRecords[recordType];
        if (!records) {
            return;
        }
        if (records.has(recordID)) {
            records.delete(recordID);
        }
        this.setState({
            openRecords: {
                ...this.state.openRecords,
                [recordType]: records,
            },
        });
    };

    /**
     * Toggle an item in the nav.
     */
    private toggleItem = (recordType: string, recordID: number) => {
        const records = this.state.openRecords[recordType];
        if (records && records.has(recordID)) {
            this.closeItem(recordType, recordID);
        } else {
            this.openItem(recordType, recordID);
        }
    };
}
