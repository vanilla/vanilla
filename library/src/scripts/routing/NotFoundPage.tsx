/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useLayoutEffect } from "react";
import { t } from "@library/utility/appUtils";
import DocumentTitle from "@library/routing/DocumentTitle";
import { sprintf } from "sprintf-js";
import { useHistory } from "react-router";
import { SectionOneColumn } from "@library/layout/SectionOneColumn";
import { Widget } from "@library/layout/Widget";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { PageBoxDepthContextProvider } from "@library/layout/PageBox.context";
import { useBackRouting } from "@library/routing/links/BackRoutingProvider";
import { PageBox } from "@library/layout/PageBox";
import { WidgetLayout } from "@library/layout/WidgetLayout";

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
                    <Widget>
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
                    </Widget>
                </SectionOneColumn>
            </WidgetLayout>
        </div>
    );
}
