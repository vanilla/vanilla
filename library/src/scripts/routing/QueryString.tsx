/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import qs from "qs";
import { withRouter, RouteComponentProps } from "react-router";
import isEqual from "lodash/isEqual";
import throttle from "lodash/throttle";

interface IStringMap {
    [key: string]: any;
}

interface IProps extends RouteComponentProps<any> {
    value: IStringMap;
    defaults?: IStringMap;
    syncOnFirstMount?: boolean;
}

/**
 * Component for automatically peristing it's props into the window's querystring.
 */
class QueryString extends React.Component<IProps> {
    public render(): React.ReactNode {
        return null;
    }

    public componentDidMount() {
        if (this.props.syncOnFirstMount) {
            this.updateQueryString();
        }
    }

    public componentDidUpdate(prevProps: IProps) {
        if (
            !isEqual(
                this.getFilteredValue(prevProps.value, prevProps.defaults || {}),
                this.getFilteredValue(this.props.value, this.props.defaults || {}),
            )
        ) {
            this.updateQueryString();
        }
    }

    /**
     * Get a version of the query string object with only keys that have values.
     */
    private getFilteredValue(inputValue: IStringMap, defaults: IStringMap): IStringMap | null {
        let filteredValue: IStringMap | null = null;

        for (const [key, value] of Object.entries(inputValue)) {
            if (value === null || value === undefined || value === "") {
                continue;
            }

            if (defaults[key] === value) {
                continue;
            }

            if (filteredValue === null) {
                filteredValue = {};
            }

            filteredValue[key] = value;
        }

        return filteredValue;
    }

    /**
     * Update the query string of the window.
     *
     * This is throttle and put in request animation frame so that it does not take priority over the UI.
     */
    private updateQueryString = throttle(() => {
        const query = qs.stringify(this.getFilteredValue(this.props.value, this.props.defaults || {}));
        this.props.history.replace({
            ...this.props.location,
            search: query,
        });
    }, 100);
}

export default withRouter(QueryString);
