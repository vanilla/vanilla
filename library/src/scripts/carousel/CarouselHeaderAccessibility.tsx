/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { t } from "@library/utility/appUtils";

interface IProps {
    title: string;
}

export function CarouselHeaderAccessibility(props: IProps) {
    return (
        <ScreenReaderContent>
            <h3 id="carousel-Title">{t(props.title)}</h3>
        </ScreenReaderContent>
    );
}
