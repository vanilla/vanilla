/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import { ILoadable, LoadStatus } from "@library/@types/api";

export default class PageLoading extends React.PureComponent<ILoadable<any>, {}> {
    public static defaultProps: Partial<ILoadable<any>> = {
        status: LoadStatus.PENDING,
    };

    public render(): JSX.Element | null {
        switch (this.props.status) {
            case LoadStatus.PENDING:
            case LoadStatus.SUCCESS:
                return null;
            case LoadStatus.LOADING:
                return <div>{t("Loading...")}</div>;
            case LoadStatus.ERROR:
                return this.props.error ? <div>{this.props.error}</div> : null;
        }
    }
}
