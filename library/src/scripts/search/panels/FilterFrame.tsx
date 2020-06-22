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

export interface ISearchFilterPanel {
    title?: string;
    handleSubmit: (data) => void;
    children: React.ReactNode;
    valid?: boolean;
}

/**
 * Implement search filter panel main component
 */
export function FilterFrame(props: ISearchFilterPanel) {
    const { title = t("Filter Results"), handleSubmit, children, valid } = props;
    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        e.stopPropagation();
        handleSubmit({});
    };

    const titleID = useUniqueID("searchFilter");

    return (
        <form onSubmit={onSubmit} aria-describedby={titleID}>
            <Frame
                header={<FrameHeader titleID={titleID} title={title} />}
                body={<FrameBody>{children}</FrameBody>}
                footer={
                    <FrameFooter justifyRight={true}>
                        <Button disabled={valid} submit={true}>
                            {t("Filter")}
                        </Button>
                    </FrameFooter>
                }
            />
        </form>
    );
}
