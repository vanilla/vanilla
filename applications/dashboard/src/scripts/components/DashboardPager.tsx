import React from "react";
import { sprintf } from "sprintf-js";
import { t } from "@vanilla/i18n/src";
import classNames from "classnames";
import { LeftChevronIcon, RightChevronIcon } from "@library/icons/common";
import { cx } from "@emotion/css";

interface IProps {
    page: number;
    pageCount?: number;
    hasNext?: boolean;
    onClick?: (page: number) => void;
    disabled?: boolean;
    className?: string;
}

// We should re-look the accessibility & styling in this component
// https://a11y-style-guide.com/style-guide/section-navigation.html

export function DashboardPager(props: IProps) {
    const { page, className } = props;
    const pageLabel = sprintf(props.pageCount ? t("Page %s of %s") : t("Page %s"), page, props.pageCount);
    const hasNext = props.hasNext || (props.pageCount && page < props.pageCount);

    const handleClick = (e, page: number) => {
        e.preventDefault();
        if (props.onClick) {
            props.onClick(page);
        }
    };

    return (
        <div className={cx("pager", "pager-react", className)}>
            <div className="pager-count" aria-current="page">
                {pageLabel}
            </div>
            <nav className="btn-group" aria-label="pagination">
                <a
                    href="#"
                    className={classNames("pager-previous btn btn-icon-border", {
                        disabled: page === 1 || props.disabled,
                    })}
                    role="button"
                    onClick={(e) => handleClick(e, page - 1)}
                    aria-disabled={page === 1 || props.disabled}
                >
                    <LeftChevronIcon />
                </a>
                <a
                    href="#"
                    className={classNames("pager-next btn btn-icon-border", { disabled: !hasNext || props.disabled })}
                    role="button"
                    onClick={(e) => handleClick(e, page + 1)}
                    aria-disabled={!hasNext || props.disabled}
                >
                    <RightChevronIcon />
                </a>
            </nav>
        </div>
    );
}
