/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { LeftChevronCompactIcon } from "@library/icons/common";
import { withRouter, RouteComponentProps } from "react-router";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";

interface IProps extends RouteComponentProps<{}> {
    showBackLink?: boolean;
    title: string;
    actionButtons?: React.ReactNode;
}

function DashboardHeaderBlock(props: IProps) {
    return (
        <header className="header-block">
            <div className="title-block">
                {props.showBackLink && (
                    <Button
                        baseClass={ButtonTypes.ICON}
                        // className="btn btn-icon btn-return"
                        aria-label="Return"
                        onClick={props.history.goBack}
                    >
                        <LeftChevronCompactIcon />
                    </Button>
                )}
                <h1>{props.title}</h1>
            </div>
            {props.actionButtons}
        </header>
    );
}

export default withRouter(DashboardHeaderBlock);
