/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Button from "@library/forms/Button";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { t } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import React from "react";
import { filterPanelClasses } from "@library/search/panels/filterPanel.styles";
import { useSection } from "@library/layout/LayoutContext";

export interface ISearchFilterPanel {
    title?: string;
    handleSubmit: (data) => void;
    isSubmitting?: boolean;
    handleClearAll?: (e: any) => void;
    disableClearAll?: boolean;
}

/**
 * Implement search filter panel main component
 */
export function FilterFrame(props: React.PropsWithChildren<ISearchFilterPanel>) {
    const {
        title = t("Filter Results"),
        handleSubmit,
        children,
        handleClearAll,
        disableClearAll,
        isSubmitting,
    } = props;
    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        e.stopPropagation();
        handleSubmit({});
    };

    const titleID = useUniqueID("searchFilter");
    const classes = filterPanelClasses(useSection().mediaQueries);

    return (
        <form onSubmit={onSubmit} aria-describedby={titleID}>
            <Frame
                scrollable={false}
                header={
                    <FrameHeader
                        titleID={titleID}
                        title={title}
                        className={classes.header}
                        titleClass={classes.title}
                    />
                }
                body={
                    <FrameBody scrollable={false} className={classes.body}>
                        {children}
                    </FrameBody>
                }
                footer={
                    <FrameFooter className={classes.footer}>
                        {handleClearAll ? (
                            <Button onClick={handleClearAll} disabled={disableClearAll}>
                                {t("Clear All")}
                            </Button>
                        ) : (
                            <div></div>
                        )}
                        <Button disabled={isSubmitting} submit>
                            {t("Filter")}
                        </Button>
                    </FrameFooter>
                }
            />
        </form>
    );
}
