/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { LoadStatus } from "@library/@types/api";
import Loader from "@library/components/Loader";

interface IProps {
    children: React.ReactNode;
    status: LoadStatus;
}

/**
 * A class for handling an ILoadable and display error, loading, or children.
 */
export default class PageLoader extends React.PureComponent<IProps, {}> {
    public render(): React.ReactNode {
        switch (this.props.status) {
            case LoadStatus.SUCCESS:
                document.body.classList.remove("isLoading");
                return this.props.children;
            case LoadStatus.LOADING:
                document.body.classList.add("isLoading");
                return <Loader />;
            default:
                return null;
        }
    }
}
