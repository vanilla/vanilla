/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { CallToAction } from "@library/callToAction/CallToAction";
import Translate from "@library/content/Translate";
import TitleBar from "@library/headers/TitleBar";
import { Backgrounds } from "@library/layout/Backgrounds";
import Container from "@library/layout/components/Container";
import PanelArea from "@library/layout/components/PanelArea";
import PanelWidget from "@library/layout/components/PanelWidget";
import SectionOneColumn from "@library/layout/SectionOneColumn";
import { leavingPageClasses } from "@library/leavingPage/LeavingPage.classes";
import SmartLink from "@library/routing/links/SmartLink";
import { getMeta, t } from "@library/utility/appUtils";
import qs from "qs";
import React from "react";
import "@library/theming/reset";
import { ButtonTypes } from "@library/forms/buttonTypes";
import DocumentTitle from "@library/routing/DocumentTitle";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";
import { sanitizeUrl } from "@vanilla/utils";
import { getClassForButtonType } from "@library/forms/Button";
import { cx } from "@emotion/css";

interface IProps {
    target: string;
    siteName: string;
}

export function LeavingPageImpl(props: IProps) {
    const { siteName } = props;
    const classes = leavingPageClasses();
    const context = useLinkContext();

    let target = props.target;

    try {
        const urlObjectFromTarget = new URL(props.target);
        target = urlObjectFromTarget.href;
    } catch (error) {
        return <ErrorPage error={{ message: t("Url is invalid.") }} />;
    }

    const href = context.makeHref(target);
    const tabIndex = context.areLinksDisabled ? -1 : 0;

    //we don't want to use default CTA button in  <CallToAction/> as our <SmartLink/> will open in separate tab, in this case we want to stay in the same
    const externalButtonAsLink = (
        <a
            className={cx(getClassForButtonType(ButtonTypes.PRIMARY))}
            aria-label={t("Continue to External Site")}
            title={t("Continue to External Site")}
            href={sanitizeUrl(href)}
            rel="noopener"
            role="button"
            tabIndex={tabIndex}
        >
            {t("Continue to External Site")}
        </a>
    );

    const description = (
        <div className={classes.description}>
            <span data-testid="external-link-as-text">{target}</span>
            <Translate source={" is not an official <0/> site."} c0={siteName} />
        </div>
    );

    return (
        <DocumentTitle title={t("Leaving")}>
            <Backgrounds />
            <TitleBar />
            <Container>
                <SectionOneColumn>
                    <PanelArea>
                        <PanelWidget>
                            <div className={classes.container}>
                                <SmartLink className={classes.backLink} to={"/"}>{`${t(
                                    "Back to",
                                )} ${siteName}`}</SmartLink>
                                <div className={classes.contentContainer}>
                                    <CallToAction
                                        title={`${t("Attention: You are leaving")} ${siteName}`}
                                        description={description}
                                        customCTA={externalButtonAsLink}
                                        to={target}
                                        textCTA={t("Continue to External Site")}
                                        className={classes.content}
                                        options={{ alignment: "center", linkButtonType: ButtonTypes.PRIMARY }}
                                    ></CallToAction>
                                </div>
                            </div>
                        </PanelWidget>
                    </PanelArea>
                </SectionOneColumn>
            </Container>
        </DocumentTitle>
    );
}

export default function LeavingPage() {
    const siteName = getMeta("siteSection.name", true);
    const parsedQuery = qs.parse(window.location.search);
    const targetProperty = Object.keys(parsedQuery).find((key) =>
        ["target", "?target", "Target", "?Target"].includes(key),
    );

    return (
        <LeavingPageImpl target={targetProperty ? (parsedQuery[targetProperty] as string) : ""} siteName={siteName} />
    );
}
