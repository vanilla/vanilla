/**
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Paragraph from "@library/layout/Paragraph";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { Tabs } from "@library/sectioning/Tabs";
import { siteUrl } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import { useAuthenticatorForm } from "@oauth2/AuthenticatorHooks";
import { IAuthenticationRequestPrompt, IAuthenticator, IAuthenticatorFormState } from "@oauth2/AuthenticatorTypes";
import { t } from "@vanilla/i18n";
import * as React from "react";
import { useParams } from "react-router";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import Loader from "@library/loaders/Loader";
import { IFieldError, LoadStatus } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import get from "lodash/get";
import set from "lodash/set";
import flatten from "lodash/flatten";
import { useAuthenticatorActions } from "@oauth2/AuthenticatorActions";
import { useHistory } from "react-router-dom";
import produce from "immer";
import { authenticatorAddEditClasses } from "@oauth2/pages/AuthenticatorAddEdit.styles";

interface IProps {
    authenticatorID: number | undefined;
    onClose: () => void;
}

interface ITabContentsProps {
    form: IAuthenticatorFormState;
    update: (data: Partial<IAuthenticator>) => void;
    fieldsError?: {
        [key: string]: IFieldError[];
    };
}

export default function OAuth2AddEdit(props: IProps) {
    const classes = authenticatorAddEditClasses();
    const params = useParams<{ authenticatorID?: string }>();
    const authenticatorID =
        props.authenticatorID || (params.authenticatorID ? parseInt(params.authenticatorID) : undefined);
    const { form, update, save, fieldsError } = useAuthenticatorForm(authenticatorID);
    const isEditing = !!authenticatorID;
    const titleID = useUniqueID("Oauth2Connection");
    const isLoading = false;
    const { clearForm } = useAuthenticatorActions();

    const onClose = () => {
        clearForm();
        props.onClose();
    };

    if (isLoading || (isEditing && form.status !== LoadStatus.SUCCESS)) {
        return (
            <div style={{ height: 400 }}>
                <Loader />
            </div>
        );
    }

    return (
        <form
            onSubmit={async (event) => {
                event.preventDefault();
                event.stopPropagation();
                await save();
                clearForm();
                onClose();
            }}
        >
            <Frame
                header={
                    <FrameHeader
                        titleID={titleID}
                        closeFrame={onClose}
                        title={t("OAuth Connection")}
                        borderless
                    ></FrameHeader>
                }
                bodyWrapClass={classes.bodyWrap}
                body={
                    <Tabs
                        extendContainer
                        data={[
                            {
                                label: "OAuth Settings",
                                panelData: "",
                                contents: (
                                    <OAuth2AddEditSettings form={form} fieldsError={fieldsError} update={update} />
                                ),
                            },
                            {
                                label: "General SSO Settings",
                                panelData: "",
                                contents: (
                                    <AuthenticatorUserMappings form={form} fieldsError={fieldsError} update={update} />
                                ),
                            },
                        ]}
                    ></Tabs>
                }
                footer={
                    <div className="Buttons form-footer padded-right">
                        <Button submit={true} buttonType={ButtonTypes.DASHBOARD_PRIMARY} disabled={isLoading}>
                            {isLoading ? <ButtonLoader /> : t("Save")}
                        </Button>
                    </div>
                }
            />
        </form>
    );
}

export function OAuth2AddEditSettings(props: ITabContentsProps) {
    const { form, update, fieldsError } = props;
    const callbackUrl = siteUrl("/entry/oauth2");
    const descriptionString = (
        <Translate
            source={
                "Configure your forum to connect with an OAuth 2 application by putting your unique Client ID, Client, Secret, and required endpoints. You will probably need to provide your SSO application with an allowed callback URL, in part, to validate requests. The callback url for this forum is <0/>"
            }
            c0={() => <a href={callbackUrl}>{siteUrl(callbackUrl)}</a>}
        ></Translate>
    );

    const promptOptions = Object.entries(IAuthenticationRequestPrompt).map(([value, label]) => ({ label, value }));

    const makeInputProps = (path: string, deleteWhenEmpty = false) => ({
        value: get(form.data, path) || "",
        onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
            const { value } = event.target;
            const data = produce(form.data, (state) => {
                if (value !== "" || !deleteWhenEmpty) {
                    set(state, path, value);
                } else {
                    set(state, path, null);
                }
            });
            update(data);
        },
    });

    return (
        <div className="padded-left padded-right">
            <div className="padded-bottom padded-top">
                {form.error && typeof fieldsError === undefined && <ErrorMessages errors={flatten([form.error])} />}
                <Paragraph>{descriptionString}</Paragraph>
            </div>
            <DashboardFormSubheading>{t("OAuth Configuration")}</DashboardFormSubheading>
            <DashboardFormGroup label={t("Client ID")} description={t("Unique ID of the authentication application.")}>
                <DashboardInput errors={fieldsError?.["clientID"]} inputProps={makeInputProps("clientID")} />
            </DashboardFormGroup>
            <DashboardFormGroup
                label={t("Name")}
                description={t("The name of the connection. This is displayed on some pages.")}
            >
                <DashboardInput errors={fieldsError?.["name"]} inputProps={makeInputProps("name")} />
            </DashboardFormGroup>
            <DashboardFormGroup label={t("Secret")} description={t("Secret provided by the authentication provider.")}>
                <DashboardInput errors={fieldsError?.["secret"]} inputProps={makeInputProps("secret")} />
            </DashboardFormGroup>
            <DashboardFormGroup
                label={t("Authorize URL")}
                description={t("URL where users sign-in with the authentication provider.")}
            >
                <DashboardInput
                    errors={fieldsError?.["authorizeUrl"]}
                    inputProps={makeInputProps("urls.authorizeUrl", true)}
                />
            </DashboardFormGroup>
            <DashboardFormGroup
                label={t("Token URL")}
                description={t("Endpoint to retrieve the access token for a user.")}
            >
                <DashboardInput errors={fieldsError?.["tokenUrl"]} inputProps={makeInputProps("urls.tokenUrl", true)} />
            </DashboardFormGroup>
            <DashboardFormGroup label={t("Profile URL")} description={t("Endpoint to retrieve a user's profile.")}>
                <DashboardInput
                    errors={fieldsError?.["profileUrl"]}
                    inputProps={makeInputProps("urls.profileUrl", true)}
                />
            </DashboardFormGroup>
            <DashboardFormGroup
                label={t("Register URL")}
                description={t("Enter the endpoint to direct a user to register.")}
            >
                <DashboardInput inputProps={makeInputProps("urls.registerUrl", true)} />
            </DashboardFormGroup>
            <DashboardFormGroup label={t("Sign Out URL")} description={t("Enter the endpoint to log a user out.")}>
                <DashboardInput inputProps={makeInputProps("urls.signOutUrl", true)} />
            </DashboardFormGroup>
            <DashboardFormSubheading>{t("Advanced Settings")}</DashboardFormSubheading>
            <DashboardFormGroup
                label={t("Request Scope")}
                description={t("Enter the scope to be sent with token requests.")}
            >
                <DashboardInput
                    errors={fieldsError?.["scope"]}
                    inputProps={makeInputProps("authenticationRequest.scope")}
                />
            </DashboardFormGroup>
            <DashboardFormGroup label={t("Prompt")} description={t("Prompt parameter set with authorize requests.")}>
                <DashboardSelect
                    options={promptOptions}
                    value={promptOptions.find((option: { label: string; value: string }) => {
                        return option.label === form.data.authenticationRequest.prompt;
                    })}
                    onChange={(option) => {
                        update({
                            authenticationRequest: {
                                prompt: option?.label ? (option!.label as IAuthenticationRequestPrompt) : undefined,
                            },
                        });
                    }}
                ></DashboardSelect>
            </DashboardFormGroup>
            <DashboardFormGroup
                label={t("Authorization Code in Header")}
                description={
                    <Translate
                        source={
                            "When requesting the profile, pass the access token in the HTTP header.<0/>i.e Authorization: Bearer [accesstoken]"
                        }
                        c0={() => <br />}
                    ></Translate>
                }
                labelType={DashboardLabelType.WIDE}
            >
                <DashboardToggle
                    onChange={(isToggled) => {
                        update({ useBearerToken: isToggled });
                    }}
                    checked={form.data.useBearerToken || false}
                ></DashboardToggle>
            </DashboardFormGroup>
            <DashboardFormGroup
                label={t("Allow Access Tokens")}
                description={t("Allow this connection to issue API access tokens.")}
                labelType={DashboardLabelType.WIDE}
            >
                <DashboardToggle
                    onChange={(isToggled) => {
                        update({ allowAccessTokens: isToggled });
                    }}
                    checked={form.data.allowAccessTokens || false}
                ></DashboardToggle>
            </DashboardFormGroup>
        </div>
    );
}

export function AuthenticatorUserMappings(props: ITabContentsProps) {
    const { form, update, fieldsError } = props;

    const makeInputProps = (path: string) => ({
        value: get(form.data, path) || "",
        onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
            const { value } = event.target;
            const data = produce(form.data, (state) => {
                set(state, path, value);
            });
            update(data);
        },
    });

    return (
        <div className="padded-left padded-right">
            <DashboardFormSubheading>{t("User Mapping")}</DashboardFormSubheading>
            <DashboardFormGroup label={t("User ID")} description={t("The key in the JSON array to designate user ID.")}>
                <DashboardInput
                    errors={fieldsError?.["uniqueID"]}
                    inputProps={makeInputProps("userMappings.uniqueID")}
                />
            </DashboardFormGroup>
            <DashboardFormGroup label={t("Email")} description={t("The key in the JSON array to designate emails.")}>
                <DashboardInput errors={fieldsError?.["email"]} inputProps={makeInputProps("userMappings.email")} />
            </DashboardFormGroup>
            <DashboardFormGroup
                label={t("Display Name")}
                description={t("The key in the JSON array to designate display name.")}
            >
                <DashboardInput inputProps={makeInputProps("userMappings.name")} />
            </DashboardFormGroup>
            <DashboardFormGroup
                label={t("Full Name")}
                description={t("The key in the JSON array to designate full name.")}
            >
                <DashboardInput inputProps={makeInputProps("userMappings.fullName")} />
            </DashboardFormGroup>
            <DashboardFormGroup label={t("Photo")} description={t("The key in the JSON array to designate photo URL.")}>
                <DashboardInput inputProps={makeInputProps("userMappings.photoUrl")} />
            </DashboardFormGroup>
            <DashboardFormGroup label={t("Roles")} description={t("The key in the JSON array to designate roles.")}>
                <DashboardInput inputProps={makeInputProps("userMappings.roles")} />
            </DashboardFormGroup>
            <DashboardFormSubheading>{t("Other Settings")}</DashboardFormSubheading>
            <DashboardFormGroup
                label={t("Visible")}
                description={t("This connection is visible on the sign in page.")}
                labelType={DashboardLabelType.WIDE}
            >
                <DashboardToggle
                    onChange={(isToggled) => {
                        update({ visible: isToggled });
                    }}
                    checked={form.data.visible || false}
                ></DashboardToggle>
            </DashboardFormGroup>
            <DashboardFormGroup
                label={t("Default")}
                description={t("Make this connection your default signin method.")}
                labelType={DashboardLabelType.WIDE}
            >
                <DashboardToggle
                    onChange={(isToggled) => {
                        update({ default: isToggled });
                    }}
                    checked={form.data.default || false}
                ></DashboardToggle>
            </DashboardFormGroup>
        </div>
    );
}
