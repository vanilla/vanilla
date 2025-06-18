/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { StaffAdminLayout } from "@dashboard/components/navigation/StaffAdminLayout";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { css } from "@emotion/css";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import { ProductMessagesApi } from "@library/features/adminAssistant/ProductMessages.api";
import Button from "@library/forms/Button";
import { SchemaFormBuilder, type IFieldError } from "@library/json-schema-forms";
import { Row } from "@library/layout/Row";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Message from "@library/messages/Message";
import BackLink from "@library/routing/links/BackLink";
import { useFallbackBackUrl } from "@library/routing/links/BackRoutingProvider";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";
import SmartLink from "@library/routing/links/SmartLink";
import { EMPTY_RICH2_BODY } from "@library/vanilla-editor/utils/emptyRich2";
import { useMutation } from "@tanstack/react-query";
import { useEffect, useMemo, useState } from "react";
import { useHistory, useRouteMatch } from "react-router";

type FormValue = Partial<ProductMessagesApi.EditBody>;

export default function ProductMessageAddEditPage(props: { productMessageID?: string }) {
    const routeProductMessageID = useRouteMatch().params["productMessageID"];
    const productMessageID = props.productMessageID ?? routeProductMessageID;

    const linkContext = useLinkContext();
    const [value, setValue] = useState<FormValue>({
        body: EMPTY_RICH2_BODY,
        name: "",
        announcementType: "Inbox",
        foreignInsertUserID: undefined,
        format: "rich2",
        ctaLabel: "",
        ctaUrl: "",
    });

    const loadInitialMutation = useMutation({
        mutationFn: async (productMessageID: string) => {
            const initialValue = await ProductMessagesApi.getEdit(productMessageID);

            setValue({ ...initialValue, body: JSON.parse(initialValue.body) });
            return initialValue;
        },
    });

    useEffect(() => {
        if (productMessageID) {
            loadInitialMutation.mutate(productMessageID);
        }
    }, [productMessageID]);

    const saveMutation = ProductMessagesApi.useSaveMutation(productMessageID, {
        onSuccess: () => {
            linkContext.pushSmartLocation("/settings/vanilla-staff/product-messages");
        },
    });

    useFallbackBackUrl("/settings/vanilla-staff/product-messages");

    return (
        <StaffAdminLayout
            title={
                <Row align={"center"} gap={8}>
                    <BackLink className={classes.backlink} />
                    {productMessageID ? "Edit Product Message" : "Add Product Message"}
                </Row>
            }
            rightPanel={
                <>
                    <p> Use this space to write a message targeted specifically to this site.</p>

                    <p>
                        This message will only be visible on this site. This is meant for sharing curated updates,
                        custom instructions, or time-sensitive notices that apply to just this customer.
                    </p>

                    <p>
                        Need to send a message to multiple sites instead? Head over to{" "}
                        <SmartLink to="https://success.vanillaforums.com/categories/updates">
                            the Success Community
                        </SmartLink>{" "}
                        and post an Update from there with the appropriate filters.
                    </p>
                </>
            }
            titleBarActions={
                <Button
                    onClick={() => {
                        saveMutation.mutate({
                            ...(value as ProductMessagesApi.EditBody),
                            body: JSON.stringify(value.body),
                        });
                    }}
                    buttonType={"outline"}
                    disabled={loadInitialMutation.isLoading || saveMutation.isLoading}
                >
                    {saveMutation.isLoading ? <ButtonLoader /> : "Save"}
                </Button>
            }
            content={
                <div className={classes.root}>
                    {loadInitialMutation.isError ? (
                        <CoreErrorMessages error={loadInitialMutation.error as any} />
                    ) : (
                        <>
                            {saveMutation.error && <Message error={saveMutation.error as any} />}
                            <Form
                                key={loadInitialMutation.isLoading ? "loading" : "loaded"}
                                fieldErrors={(saveMutation.error as any)?.errors}
                                value={value}
                                isLoading={loadInitialMutation.isLoading}
                                onChange={setValue}
                            />
                        </>
                    )}
                </div>
            }
        />
    );
}

function Form(props: {
    fieldErrors?: Record<string, IFieldError[]>;
    value: FormValue;
    onChange: (value: FormValue) => void;
    isLoading?: boolean;
}) {
    const { value, onChange } = props;
    const schema = useMemo(() => {
        return SchemaFormBuilder.create()
            .selectLookup(
                "foreignInsertUserID",
                "Author",
                "Select the author of the message. These users are sourced from the success community.",
                {
                    searchUrl: "/product-messages/foreign-users",
                    singleUrl: "",
                    labelKey: "name",
                    valueKey: "userID",
                    extraLabelKey: "label",
                },
            )
            .withControlParams({ placeholder: "Search Users" })
            .required()
            .textBox("name", "Title", "Give a descriptive title of the message.")
            .withControlParams({ placeholder: "Enter the message title" })
            .required()
            .custom("body", {
                "x-control": {
                    inputType: "richeditor",
                    placeholder: "Enter the message body",
                    label: "Message Body",
                    description: "This is the message that will be sent to the users.",
                    labelType: "vertical",
                },
            })
            .required()
            .radioGroup(
                "announcementType",
                "Announcement Type",
                "Choose how this message is announced to site admins",
                [
                    {
                        label: "None",
                        value: "None",
                        description: "The message will be added without popping up.",
                    },
                    {
                        label: "Inbox",
                        value: "Inbox",
                        description: "The message will cause the product message inbox to open automatically.",
                    },
                    {
                        label: "Direct",
                        value: "Direct",
                        description: "The message pop up directly.",
                    },
                ],
            )
            .required()
            .textBox("ctaLabel", "Call to Action Label", "Create a call to action that is part of the message.")
            .textBox(
                "ctaUrl",
                "Call to Action Link",
                "Direct the customer to a specific URL as part of the call to action. Supported URL formats are fully qualified URLs (e.g. https://www.example.com), relative URLs (e.g. /path/to/resource) and mailto links (e.g. mailto:myemail@example.com).",
            )
            .getSchema();
    }, []);

    return (
        <DashboardSchemaForm
            fieldErrors={props.fieldErrors}
            disabled={props.isLoading}
            schema={schema}
            instance={value}
            onChange={onChange}
        />
    );
}

const classes = {
    root: css({
        paddingLeft: 18,
        paddingRight: 18,
    }),
    backlink: css({
        position: "static",
        transform: "none",
        margin: 0,
    }),
};
