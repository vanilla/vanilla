/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import { ILoadable, LoadStatus } from "@library/@types/api";
import FullPageLoader from "@library/components/FullPageLoader";

export default class PageLoading extends React.PureComponent<ILoadable<any>, {}> {
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
                return this.props.error ? <div className="error">{this.props.error}</div> : null;
        }
    }
}
