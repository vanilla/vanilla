/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { dateTimeClasses } from "@library/content/dateTimeStyles";
import { LoadingRectangle, LoadingSpacer } from "@library/loaders/LoadingRectangle";
import classNames from "classnames";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { t } from "@vanilla/i18n/src";

interface IProps {
    size?: number;
    className?: string;
}

export function DateTimeCompactPlaceholder(props: IProps) {
    const classes = dateTimeClasses();
    const size = props.size ?? 38;
    return (
        <>
            <ScreenReaderContent>{t("Loading")}</ScreenReaderContent>
            <LoadingRectangle
                className={classNames(classes.compactRoot, props.className, props.className)}
                height={size}
                width={size}
            />
        </>
    );
}
