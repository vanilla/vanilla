/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forum Inc.
 * @license Proprietary
 */

import React, { useMemo } from "react";
import { numberedPagerClasses } from "@library/features/numberedPager/NumberedPager.styles";
import { numberedPagerVariables } from "@library/features/numberedPager/NumberedPager.variables";
import Button, { IButtonProps } from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ToolTip } from "@library/toolTip/ToolTip";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { RightChevronSmallIcon, LeftChevronSmallIcon } from "@library/icons/common";

interface IProps extends IButtonProps {
    direction: "next" | "prev";
    tooltip: string;
}

export function NextPrevButton(props: IProps) {
    const { disabled = false, direction, tooltip, ...btnProps } = props;
    const vars = numberedPagerVariables();
    const classes = numberedPagerClasses();

    const Icon = useMemo(() => (direction === "prev" ? LeftChevronSmallIcon : RightChevronSmallIcon), [direction]);

    return (
        <ConditionalWrap condition={!!disabled} component={ToolTip} componentProps={{ label: tooltip }}>
            <span>
                <Button
                    {...btnProps}
                    ariaLabel={tooltip}
                    className={classes.iconButton}
                    disabled={disabled}
                    buttonType={vars.buttons.iconButton.name as ButtonTypes}
                >
                    <Icon />
                </Button>
            </span>
        </ConditionalWrap>
    );
}

export default NextPrevButton;
