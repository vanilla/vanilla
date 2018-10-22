/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { ILoadable, LoadStatus, IApiError } from "@library/@types/api";
import FullPageLoader from "@library/components/FullPageLoader";
import FullPageError from "@library/components/FullPageError";

interface IProps {
    status: LoadStatus;
    error?: Error | IApiError;
}

/**
 * A class for handling an ILoadable and display error, loading, or children.
 */
export default class PageLoader extends React.PureComponent<IProps, {}> {
    public static defaultProps: Partial<ILoadable<any>> = {
        status: LoadStatus.PENDING,
    };

    public render(): React.ReactNode {
        switch (this.props.status) {
            case LoadStatus.PENDING:
                return null;
            case LoadStatus.SUCCESS:
                document.body.classList.remove("isLoading");
                return this.props.children;
            case LoadStatus.LOADING:
                document.body.classList.add("isLoading");
                return <FullPageLoader />;
            case LoadStatus.ERROR:
                document.body.classList.add("isError");
                return this.props.error ? <FullPageError>{this.props.error.message}</FullPageError> : null;
        }
    }
}
