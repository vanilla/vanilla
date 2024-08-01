/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import { buttonLoaderClasses } from "@library/forms/Button.styles";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { LoaderIcon } from "@library/icons/common";
import { cx } from "@emotion/css";

interface IProps {
    className?: string;
    buttonType?: ButtonTypes;
    alignLeft?: boolean;
}

/**
 * A smart loading component. Takes up the full page and only displays in certain scenarios.
 */
export default function ButtonLoader(props: IProps) {
    const classes = buttonLoaderClasses();
    return (
        <span
            className={cx(
                classes.root(props.alignLeft ? "left" : "center"),
                props.buttonType?.startsWith("dashboard") && classes.reducedPadding,
                props.className,
            )}
        >
            <LoaderIcon className={classes.svg} />
            <ScreenReaderContent>{t("Loading")}</ScreenReaderContent>
        </span>
    );
}
