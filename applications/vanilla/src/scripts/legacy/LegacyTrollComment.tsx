/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import UserContent from "@library/content/UserContent";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Mixins } from "@library/styles/Mixins";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { useState } from "react";

interface IProps {
    comment: string;
    hideText: string;
}

const trollCommentClasses = () => {
    const root = css({
        ...Mixins.margin({ vertical: 16 }),
    });
    const layout = css({
        display: "flex",
        ...Mixins.margin({ vertical: 16 }),
        alignItems: "center",
        gap: 8,
        "& > span": {
            display: "flex",
            alignSelf: "start",
            alignItems: "center",
            gap: 4,
        },
    });
    const blurContainer = css({
        "&[data-visible='false']": {
            filter: "blur(5px)",
        },
    });
    return {
        root,
        layout,
        blurContainer,
    };
};

export function TrollComment(props: IProps) {
    const classes = trollCommentClasses();
    const [isVisible, setIsVisible] = useState(false);

    return (
        <div className={classes.root}>
            <UserContent content={props.hideText} />
            <div className={classes.layout}>
                <span>
                    <ToolTip label={isVisible ? t("Hide this content") : t("Show this content")}>
                        <Button buttonType={ButtonTypes.ICON_COMPACT} onClick={() => setIsVisible(!isVisible)}>
                            {isVisible ? <Icon icon="show-content" /> : <Icon icon="hide-content" />}
                        </Button>
                    </ToolTip>
                </span>
                <div className={classes.blurContainer} data-visible={isVisible}>
                    <UserContent content={props.comment} />
                </div>
            </div>
        </div>
    );
}
