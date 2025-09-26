/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Button from "@library/forms/Button";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { useSection } from "@library/layout/LayoutContext";
import { filterPanelClasses } from "@library/search/panels/filterPanel.styles";
import { t } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import React from "react";

export interface ISearchFilterPanel {
    title?: string;
    handleSubmit?: (data) => void;
    isSubmitting?: boolean;
    handleClearAll?: (e: any) => void;
    disableClearAll?: boolean;
    hideFooter?: boolean;
}

/**
 * Implement search filter panel main component
 */
export function FilterFrame(props: React.PropsWithChildren<ISearchFilterPanel>) {
    const { title = t("Filter Results"), children, hideFooter } = props;

    const titleID = useUniqueID("searchFilter");
    const classes = filterPanelClasses(useSection().mediaQueries);

    return (
        <Frame
            scrollable={false}
            header={
                <FrameHeader titleID={titleID} title={title} className={classes.header} titleClass={classes.title} />
            }
            body={<FrameBody className={classes.body}>{children}</FrameBody>}
            footer={
                !hideFooter ? (
                    <FrameFooter className={classes.footer}>
                        {props.handleClearAll ? (
                            <Button onClick={props.handleClearAll} disabled={props.disableClearAll}>
                                {t("Clear All")}
                            </Button>
                        ) : (
                            <div></div>
                        )}
                        <Button
                            disabled={props.isSubmitting}
                            onClick={(e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                props.handleSubmit && props.handleSubmit({});
                            }}
                        >
                            {t("Filter")}
                        </Button>
                    </FrameFooter>
                ) : (
                    <></>
                )
            }
        />
    );
}
