/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import FlyoutToggle from "@library/flyouts/FlyoutToggle";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Icon } from "@vanilla/icons";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { t } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import React, { useState } from "react";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { GridSelector, GridSelectorLayout } from "@library/forms/gridSelector/GridSelector";
import { css } from "@emotion/css";
import ModalSizes from "@library/modal/ModalSizes";

interface IProps {
    disabled?: boolean;
    renderAbove?: boolean;
    renderLeft?: boolean;
    onVisibilityChange?: React.ComponentProps<typeof FlyoutToggle>["onVisibilityChange"];
    onSelectionComplete: (tableLayout: GridSelectorLayout) => void;
    isVisible?: boolean; //for storybook purposes
}

export default function InsertTableFlyout(props: IProps) {
    const [isVisible, setIsVisible] = useState(props.isVisible);

    const id = useUniqueID("insertTableFlyout");
    const title = t("Insert Table");
    const handleID = id + "-handle";
    const contentID = id + "-content";
    return (
        <DropDown
            handleID={handleID}
            contentID={contentID}
            title={title}
            name={title}
            contentsClassName={classes.content} // bit more narrow than the default smallest width
            onVisibilityChange={(isVisible) => {
                isVisible ? setIsVisible(isVisible) : setIsVisible(false);
            }}
            disabled={props.disabled}
            buttonContents={
                <>
                    <ScreenReaderContent>{title}</ScreenReaderContent>
                    <Icon icon="table" />
                </>
            }
            buttonType={ButtonTypes.ICON_MENUBAR}
            renderAbove={!!props.renderAbove}
            renderLeft={!!props.renderLeft}
            flyoutType={FlyoutType.FRAME}
            isVisible={isVisible}
            preventFocusOnVisible
            modalSize={ModalSizes.SMALL}
            asReachPopover
        >
            <Frame
                body={
                    <FrameBody>
                        <GridSelector
                            onSelect={(tableLayout: GridSelectorLayout) => {
                                setIsVisible(false);
                                props.onSelectionComplete(tableLayout);
                            }}
                        />
                    </FrameBody>
                }
            />
        </DropDown>
    );
}

const classes = {
    content: css({ "&&": { minWidth: 200, width: 200 } }),
};
