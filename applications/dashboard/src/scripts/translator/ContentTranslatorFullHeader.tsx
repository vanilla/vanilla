/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Container from "@library/layout/components/Container";
import { modalClasses } from "@library/modal/modalStyles";
import BackLink from "@library/routing/links/BackLink";
import { t } from "@vanilla/i18n";
import classNames from "classnames";
import { contentTranslatorClasses } from "./contentTranslatorStyles";
import ButtonLoader from "@library/loaders/ButtonLoader";
import PanelWidgetHorizontalPadding from "@library/layout/components/PanelWidgetHorizontalPadding";
import React from "react";
import PanelArea from "@library/layout/components/PanelArea";

export function ContentTranslaterFullHeader(props: { onBack: () => void; isSubmitLoading?: boolean }) {
    const classesModal = modalClasses();

    const classes = contentTranslatorClasses();

    return (
        <nav className={classNames(classesModal.pageHeader)}>
            <Container>
                <PanelArea>
                    <PanelWidgetHorizontalPadding>
                        <ul className={classNames(classes.header)}>
                            <li>
                                <BackLink
                                    visibleLabel={true}
                                    onClick={(e) => {
                                        e.preventDefault();
                                        props.onBack();
                                    }}
                                />
                            </li>
                            <li>
                                <Button buttonType={ButtonTypes.TEXT_PRIMARY} submit>
                                    {props.isSubmitLoading ? (
                                        <ButtonLoader buttonType={ButtonTypes.TEXT_PRIMARY} />
                                    ) : (
                                        t("Save")
                                    )}
                                </Button>
                            </li>
                        </ul>
                    </PanelWidgetHorizontalPadding>
                </PanelArea>
            </Container>
        </nav>
    );
}
