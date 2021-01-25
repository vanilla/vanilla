/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { LeftChevronCompactIcon } from "@library/icons/common";
import { RouteComponentProps, useHistory } from "react-router";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@library/utility/appUtils";
import BackLink from "@library/routing/links/BackLink";

interface IProps {
    showBackLink?: boolean;
    title: string;
    actionButtons?: React.ReactNode;
    /** @deprecated use useFallbackBackUrl("/action"); instead */
    onBackClick?: () => void;
}

export function DashboardHeaderBlock(props: IProps) {
    const history = useHistory();
    return (
        <header className="header-block">
            <div className="title-block">
                {props.showBackLink && history && <BackLink aria-label={t("Return")} onClick={props.onBackClick} />}
                <h1>{props.title}</h1>
            </div>
            {props.actionButtons}
        </header>
    );
}
