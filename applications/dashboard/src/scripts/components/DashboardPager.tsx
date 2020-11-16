import React from "react";
import { sprintf } from "sprintf-js";
import { t } from "@vanilla/i18n/src";
import classNames from "classnames";
import { LeftChevronIcon, RightChevronIcon } from "@library/icons/common";

interface IProps {
    page: number;
    pageCount?: number;
    hasNext?: boolean;
    onClick?: (page: number) => void;
    disabled?: boolean;
}

export function DashboardPager(props: IProps) {
    const { page } = props;
    const pageLabel = sprintf(props.pageCount ? t("Page %s of %s") : t("Page %s"), page, props.pageCount);
    const hasNext = props.hasNext || (props.pageCount && page < props.pageCount);

    const handleClick = (e, page: number) => {
        e.preventDefault();
        if (props.onClick) {
            props.onClick(page);
        }
    };

    return (
        <div className="pager pager-react">
            <div className="pager-count">{pageLabel}</div>
            <nav className="btn-group">
                <a
                    href="#"
                    className={classNames("pager-previous btn btn-icon-border", {
                        disabled: page === 1 || props.disabled,
                    })}
                    role="button"
                    onClick={(e) => handleClick(e, page - 1)}
                >
                    <LeftChevronIcon />
                </a>
                <a
                    href="#"
                    className={classNames("pager-next btn btn-icon-border", { disabled: !hasNext || props.disabled })}
                    role="button"
                    onClick={(e) => handleClick(e, page + 1)}
                >
                    <RightChevronIcon />
                </a>
            </nav>
        </div>
    );
}
