/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { FragmentsApi } from "@dashboard/appearance/fragmentEditor/FragmentsApi";
import { css, cx } from "@emotion/css";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import { pageErrorMessageClasses } from "@library/errorPages/pageErrorMessageStyles";
import Button from "@library/forms/Button";
import Container from "@library/layout/components/Container";
import { Row } from "@library/layout/Row";
import { QueryLoader } from "@library/loaders/QueryLoader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import LinkAsButton from "@library/routing/LinkAsButton";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useMutation, useQuery } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";

export function WidgetBuilderDisclosureAccess(props: { children: React.ReactNode; type: "disclosure" | "redirect" }) {
    const didAcceptQuery = useQuery({
        queryKey: ["didAcceptDisclosure"],
        queryFn: async () => {
            return FragmentsApi.getAcceptedDisclosure();
        },
    });

    const acceptMutation = useMutation({
        mutationFn: async () => {
            await FragmentsApi.setAcceptedDisclosure(true);
            await didAcceptQuery.refetch();
        },
    });

    return (
        <QueryLoader
            query={didAcceptQuery}
            success={(didAccept) => {
                if (!didAccept) {
                    const disclosure = (
                        <Row align={"center"} justify={"center"} style={{ height: "100%" }}>
                            <Container maxWidth={800}>
                                <CoreErrorMessages
                                    error={{
                                        icon: <Icon icon={"status-warning"} className={classes.errorIcon} />,
                                        message: t("WARNING: This interface is for developers only"),
                                        description: (
                                            <>
                                                <p>
                                                    {t(
                                                        "Please do not use this interface unless you are a developer. Making changes in this interface may break your site. Please proceed with caution.",
                                                    )}
                                                </p>
                                                <p>
                                                    {t(
                                                        "By using the Widget Builder you grant a license to Higher Logic Vanilla LLC to view, modify, and distribute the contents. Higher Logic Vanilla staff may view or modify the contents to assist with Support related issues or to improve service the service subscriber.",
                                                    )}
                                                </p>
                                            </>
                                        ),
                                        actionItem: (
                                            <Row align={"center"} gap={16}>
                                                <LinkAsButton
                                                    buttonType={"primary"}
                                                    to="https://success.vanillaforums.com/kb/articles/1764-editing-code-fragments-what-you-need-to-know-before-diving-in"
                                                >
                                                    {t("See documentation")}
                                                </LinkAsButton>
                                                <Button
                                                    buttonType={"standard"}
                                                    mutation={acceptMutation}
                                                    onClick={() => {
                                                        acceptMutation.mutate();
                                                    }}
                                                >
                                                    {t("I understand")}
                                                </Button>
                                            </Row>
                                        ),
                                    }}
                                />
                            </Container>
                        </Row>
                    );
                    if (props.type === "disclosure") {
                        return disclosure;
                    }

                    return (
                        <Modal
                            size={ModalSizes.FULL_SCREEN}
                            isVisible={true}
                            exitHandler={() => {
                                // No exiting.
                            }}
                        >
                            {disclosure}
                        </Modal>
                    );
                }

                return <>{props.children}</>;
            }}
        />
    );
}

const classes = {
    errorIcon: cx(
        pageErrorMessageClasses().errorIcon,
        css({
            "&&": {
                color: ColorsUtils.var(globalVariables().elementaryColors.red),
            },
        }),
    ),
};
