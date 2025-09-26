/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { PageBoxDepthContextProvider } from "@library/layout/PageBox.context";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { SectionOneColumn } from "@library/layout/SectionOneColumn";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import { WidgetLayout } from "@library/layout/WidgetLayout";
import DocumentTitle from "@library/routing/DocumentTitle";
import { t } from "@library/utility/appUtils";
import React from "react";
import { sprintf } from "sprintf-js";

interface IProps {
    type?: string;
    title?: string;
    message?: string;
}

export default function NotFoundPage(props: IProps) {
    const type = props.type ?? "Page";
    const title = props.title || sprintf(t("%s Not Found"), t(type));
    return (
        <div className="Center SplashInfo">
            <WidgetLayout>
                <SectionOneColumn isNarrow>
                    <LayoutWidget>
                        <PageBoxDepthContextProvider depth={0}>
                            <PageHeadingBox
                                options={{
                                    alignment: "center",
                                }}
                                title={<DocumentTitle title={title}>{title}</DocumentTitle>}
                                description={
                                    props.message ||
                                    sprintf(t("The %s you were looking for could not be found."), t(type.toLowerCase()))
                                }
                            ></PageHeadingBox>
                        </PageBoxDepthContextProvider>
                    </LayoutWidget>
                </SectionOneColumn>
            </WidgetLayout>
        </div>
    );
}
