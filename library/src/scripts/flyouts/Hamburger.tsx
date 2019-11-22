/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { t } from "@library/utility/appUtils";
import Frame from "@library/layout/frame/Frame";
import classNames from "classnames";
import FrameBody from "@library/layout/frame/FrameBody";
import { HamburgerIcon } from "@library/icons/common";

/**
 * Creates a drop down menu
 */
export default function Hamburger(props) {
    return (
        <DropDown
            name={t("Messages")}
            buttonClassName={props.buttonClassName}
            buttonContents={<HamburgerIcon />}
            flyoutType={FlyoutType.FRAME}
        >
            <Frame
                body={
                    <FrameBody className={classNames("isSelfPadded")}>
                        <div className={classNames()} dangerouslySetInnerHTML={{ __html: props.contents }} />
                    </FrameBody>
                }
            />
        </DropDown>
    );
}
