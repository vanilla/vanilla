import * as React from "react";
import { ILoadable } from "@dashboard/state/IState";
import { LoadStatus } from "@dashboard/state/IState";
import { t } from "@dashboard/application";

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
