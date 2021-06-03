/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactElement } from "react";
import { t } from "@library/utility/appUtils";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";

interface IProps {
    disabled: boolean;
    accessibilityLabel: string;
    arrowType: ReactElement;
    direction: string;
    arrowHandler: (e: React.MouseEvent<HTMLButtonElement>) => void;
}

export function CarouselArrowNav(props: IProps) {
    const { arrowType, arrowHandler, direction, accessibilityLabel, disabled } = props;
    return (
        <Button buttonType={ButtonTypes.ICON} disabled={disabled} data-direction={direction} onClick={arrowHandler}>
            {arrowType}
            <ScreenReaderContent>
                <span>{t(accessibilityLabel)}</span>
            </ScreenReaderContent>
        </Button>
    );
}
