/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { useHistory } from "react-router";
import { t } from "@library/utility/appUtils";
import BackLink from "@library/routing/links/BackLink";
import { cx } from "@emotion/css";

interface IProps {
    headingTag?: "h1" | "h2" | "h3" | "h4" | "h5" | "h6";
    showBackLink?: boolean;
    title: string;
    actionButtons?: React.ReactNode;
    /** @deprecated use useFallbackBackUrl("/action"); instead */
    onBackClick?: () => void;
    className?: string;
}

export function DashboardHeaderBlock(props: IProps) {
    const HeadingTag = props.headingTag ?? "h1";
    const history = useHistory();
    return (
        <header className={cx("header-block", props.className)}>
            <div className="title-block">
                {props.showBackLink && history && <BackLink aria-label={t("Return")} onClick={props.onBackClick} />}
                <HeadingTag>{props.title}</HeadingTag>
            </div>
            {props.actionButtons}
        </header>
    );
}
