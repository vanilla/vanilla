/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ModerationAdminLayout } from "@dashboard/components/navigation/ModerationAdminLayout";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardFormReadOnlySection } from "@dashboard/forms/DashboardFormReadonlySection";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { categoryLookup } from "@dashboard/moderation/communityManagmentUtils";
import { IRole } from "@dashboard/roles/roleTypes";
import { css } from "@emotion/css";
import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import Translate from "@library/content/Translate";
import { useToast } from "@library/features/toaster/ToastContext";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { FormControlGroup, FormControlWithNewDropdown } from "@library/forms/FormControl";
import InputTextBlock from "@library/forms/InputTextBlock";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { TokenItem } from "@library/metas/TokenItem";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";
import LinkAsButton from "@library/routing/LinkAsButton";
import SmartLink from "@library/routing/links/SmartLink";
import { getMeta } from "@library/utility/appUtils";
import { useMutation, useQuery } from "@tanstack/react-query";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { NumberBox } from "@vanilla/ui";
import { logError, uuidv4 } from "@vanilla/utils";
import { useEffect, useState } from "react";
import { sprintf } from "sprintf-js";

const BETA_ENABLED = getMeta("featureFlags.CommunityManagementBeta.Enabled", false);

const CONF_PREMOD_DISCUSSIONS = "premoderation.discussions";
const CONF_PREMOD_COMMENTS = "premoderation.comments";
const CONF_PREMOD_CATEGORY_IDS = "premoderation.categoryIDs";
const CONF_PREMOD_KEYWORDS = "premoderation.keywords";
const CONF_PREMOD_CHALLENGE_NEW_USERS = "premoderation.challengeNewUsers";
const CONF_PREMOD_CHALLENGE_AGE = "premoderation.challengeAgeCutoffInDays";

const classes = {
    main: css({
        padding: "0 18px",
    }),
    comboInputWrapper: css({
        padding: "0 18px",
        display: "flex",
        flexDirection: "row",
        alignItems: "stretch",
        justifyContent: "flex-end",
    }),
    comboInput: css({
        flex: 1,
        borderTopRightRadius: 0,
        borderBottomRightRadius: 0,
    }),
    comboInputButton: css({
        minWidth: "unset",
        borderTopLeftRadius: 0,
        borderBottomLeftRadius: 0,
        borderLeftWidth: 0,
        margin: 0,
        paddingLeft: 8,
        paddingRight: 8,
        "&&:hover, &&:focus, &&:active": {
            borderTopLeftRadius: 0,
            borderBottomLeftRadius: 0,
            borderLeftWidth: 0,
        },
    }),
};

export function PremoderationSettingsPage() {
    const configs = useConfigsByKeys([
        CONF_PREMOD_DISCUSSIONS,
        CONF_PREMOD_COMMENTS,
        CONF_PREMOD_CATEGORY_IDS,
        CONF_PREMOD_KEYWORDS,
        CONF_PREMOD_CHALLENGE_NEW_USERS,
        CONF_PREMOD_CHALLENGE_AGE,
    ]);
    const challengeNewUsersPatcher = useConfigPatcher();
    const challengeAgePatcher = useConfigPatcher();

    const areConfigsLoading = [LoadStatus.PENDING, LoadStatus.LOADING].includes(configs.status);
    const isChallengeNewUsersLoading: boolean = areConfigsLoading || challengeNewUsersPatcher.isLoading;
    const isChallengeNewUsersEnabled: boolean = configs.data?.[CONF_PREMOD_CHALLENGE_NEW_USERS] ?? false;
    const [challengeCutoffAge, setChallengeCutoffAge] = useState<number>(7);

    useEffect(() => {
        if (configs.data?.[CONF_PREMOD_CHALLENGE_AGE] !== challengeCutoffAge) {
            setChallengeCutoffAge(configs.data?.[CONF_PREMOD_CHALLENGE_AGE] ?? 7);
        }
    }, [configs.data]);

    const rolesQuery = useQuery({
        queryKey: ["roles", "requiringApproval"],
        queryFn: async () => {
            const response = await apiv2.get<IRole[]>("/roles?expand=permissions");
            const roles = response.data;
            return roles.filter((role: IRole) => {
                return role.permissions?.some((permissionSet) => !!permissionSet.permissions["approval.require"]);
            });
        },
    });
    const categoryQuery = useQuery({
        queryKey: ["categories", configs.data?.[CONF_PREMOD_CATEGORY_IDS]],
        queryFn: async () => {
            const catIDs = configs.data?.[CONF_PREMOD_CATEGORY_IDS] ?? [];
            if (catIDs.length === 0) {
                return [];
            }
            const response = await apiv2.get<ICategory[]>("/categories", {
                params: {
                    categoryID: catIDs,
                },
            });
            return response.data;
        },
    });

    const categoryNames = categoryQuery?.data?.map((category) => category.name);
    const roleNames = rolesQuery.data?.map((role) => role.name);
    const keywords = configs.data?.[CONF_PREMOD_KEYWORDS]?.split(";")?.map((keyword: string) => keyword.trim());

    const [showCategoryModal, setShowCategoryModal] = useState(false);
    const [showKeywordModal, setShowKeywordModal] = useState(false);

    let categoryDescription: React.ReactNode = <LoadingRectangle height={16} width={180} />;
    if (configs.data) {
        const baseStr = t("%s in the following categories will require moderator approval.");
        const recordTypes = [
            configs.data[CONF_PREMOD_DISCUSSIONS] && t("Discussions"),
            configs.data[CONF_PREMOD_COMMENTS] && t("Comments"),
        ].filter(Boolean);
        categoryDescription = sprintf(baseStr, recordTypes.join(" & "));
    }
    const cmdEnabled = getMeta("featureFlags.escalations.Enabled", false);

    return (
        <ModerationAdminLayout
            title={t("Premoderation Settings")}
            rightPanel={
                <>
                    <h3>{t("Heads Up!")}</h3>
                    <p>
                        {t(
                            "Configure settings used to automatically moderate community content before it is even created. Options include automated spam detections, requiring approval for posting in certain categories, and requiring approval for posting using certain keywords or phrases.",
                        )}
                    </p>
                </>
            }
            content={
                <section className={classes.main}>
                    <DashboardFormSubheading hasBackground>{t("Spam Detection")}</DashboardFormSubheading>
                    <DashboardFormGroup
                        labelType={DashboardLabelType.JUSTIFIED}
                        label={"Akismet"}
                        description={
                            <Translate
                                source="Enable Akismet to filter spam in all posts by applicant registrations and unverified users. <0/>"
                                c0={<SmartLink to={"https://akismet.com"}>{t("Learn more.")}</SmartLink>}
                            />
                        }
                    >
                        <AddonToggle addonKey={"akismet"} />
                    </DashboardFormGroup>
                    <DashboardFormGroup
                        labelType={DashboardLabelType.JUSTIFIED}
                        label={"StopForumSpam"}
                        description={
                            <Translate
                                source={
                                    "Enable Stopforumspam to check community users against a list of reported spammers, and either reports the post as spam or rejects them outright. The reporting and rejecting thresholds are managed in the <0>addon's settings</0>. Learn more in the <1/>"
                                }
                                c0={(content) => <SmartLink to={"/settings/addons"}>{content}</SmartLink>}
                                c1={<SmartLink to={"https://www.stopforumspam.com"}>{t("documentation.")}</SmartLink>}
                            />
                        }
                    >
                        <AddonToggle addonKey={"stopforumspam"} />
                    </DashboardFormGroup>
                    <DashboardFormGroup
                        label={t("Verify browsers of new members")}
                        description={
                            <Translate
                                source="Unverified members who have been in the community for less than the specified number of days will be prompted to complete a Cloudflare (Captcha or Checkbox) challenge to prevent spam. <0/>"
                                c0={
                                    <SmartLink
                                        to={
                                            "https://success.vanillaforums.com/kb/articles/1643-verify-browsers-of-new-unverified-members"
                                        }
                                    >
                                        {t("Learn more.")}
                                    </SmartLink>
                                }
                            />
                        }
                        labelType={DashboardLabelType.JUSTIFIED}
                    >
                        <DashboardToggle
                            indeterminate={isChallengeNewUsersLoading}
                            enabled={isChallengeNewUsersEnabled}
                            onChange={(enabled) => {
                                void challengeNewUsersPatcher.patchConfig({
                                    [CONF_PREMOD_CHALLENGE_NEW_USERS]: enabled,
                                });
                            }}
                        />
                    </DashboardFormGroup>
                    <DashboardFormGroup
                        label={t("Challenge Cutoff Age")}
                        description={t("Number of days since registration to bypass Cloudflare challenge")}
                        labelType={DashboardLabelType.JUSTIFIED}
                    >
                        <span className={classes.comboInputWrapper}>
                            <NumberBox
                                value={challengeCutoffAge}
                                className={classes.comboInput}
                                onValueChange={setChallengeCutoffAge}
                            />
                            <Button
                                buttonType={ButtonTypes.STANDARD}
                                className={classes.comboInputButton}
                                title={t("Save challenge cutoff age")}
                                onClick={() => {
                                    void challengeAgePatcher.patchConfig({
                                        [CONF_PREMOD_CHALLENGE_AGE]: challengeCutoffAge,
                                    });
                                }}
                            >
                                <Icon icon="send" />
                            </Button>
                        </span>
                    </DashboardFormGroup>
                    <DashboardFormReadOnlySection
                        title={t("Premoderated Roles")}
                        description={
                            <>
                                {t(
                                    'Users with the "Approval.Require" permission will require moderator approval for all posts.',
                                )}
                                <br />
                                {t(
                                    'Users marked as "Verified" will bypass this requirement. These are are the roles with "Approval.Require"',
                                )}
                            </>
                        }
                        emptyMessage={t("No roles are currently set to require premoderation.")}
                        tokens={
                            roleNames?.map((token, index) => (
                                <TokenItem key={`${token}_${index}`}>{token}</TokenItem>
                            )) ?? [<TokenLoader key={uuidv4()} />]
                        }
                        actions={
                            <LinkAsButton to="/role" buttonType={ButtonTypes.STANDARD}>
                                {t("Edit Roles")}
                            </LinkAsButton>
                        }
                    />

                    {cmdEnabled || addonEnabled("premoderatedcategory") ? (
                        <DashboardFormReadOnlySection
                            title={t("Premoderated Categories")}
                            description={categoryDescription}
                            emptyMessage={t("No categories are currently set to require premoderation.")}
                            tokens={
                                categoryNames?.map((token, index) => (
                                    <TokenItem key={`${token}_${index}`}>{token}</TokenItem>
                                )) ?? [<TokenLoader key={uuidv4()} />]
                            }
                            actions={
                                <Button
                                    onClick={() => {
                                        setShowCategoryModal(true);
                                    }}
                                >
                                    {t("Edit Categories")}
                                </Button>
                            }
                        />
                    ) : (
                        <>
                            {BETA_ENABLED && (
                                <DashboardFormReadOnlySection
                                    tokens={[]}
                                    title={"Premoderated Categories"}
                                    description={<RequiresCmd addonName={"Premoderated Category"} />}
                                />
                            )}
                        </>
                    )}

                    <PremoderatedCategoriesModal
                        key={showCategoryModal ? "open" : "closed"}
                        isVisible={showCategoryModal}
                        setIsVisible={setShowCategoryModal}
                    />

                    {cmdEnabled || addonEnabled("keywordblocker") ? (
                        <DashboardFormReadOnlySection
                            title={t("Premoderated Keywords")}
                            description={t(
                                "Posts with any of the following keywords or phrases will required moderator approval.",
                            )}
                            emptyMessage={t("No keywords are currently set to require premoderation.")}
                            tokens={
                                keywords
                                    ?.filter((val: string) => !!val)
                                    ?.map((token: string, index: number) => (
                                        <TokenItem key={`${token}_${index}`}>{token}</TokenItem>
                                    )) ?? [<TokenLoader key={uuidv4()} />]
                            }
                            actions={
                                <Button
                                    onClick={() => {
                                        setShowKeywordModal(true);
                                    }}
                                >
                                    {t("Edit Keywords")}
                                </Button>
                            }
                        />
                    ) : (
                        <>
                            {BETA_ENABLED && (
                                <DashboardFormReadOnlySection
                                    tokens={[]}
                                    title={"Premoderated Keywords"}
                                    description={<RequiresCmd addonName={"Keyword Blocker"} />}
                                />
                            )}
                        </>
                    )}
                    {showKeywordModal && (
                        <KeywordModal isVisible={showKeywordModal} setIsVisible={setShowKeywordModal} />
                    )}
                </section>
            }
        />
    );
}

function RequiresCmd(props: { addonName: string }) {
    return (
        <Translate
            source={`This feature requires either the <0>New Community Management System</0> or the <1/> addon to be enabled.`}
            c0={(content) => <SmartLink to="/dashboard/content/settings">{content}</SmartLink>}
            c1={props.addonName}
        />
    );
}

function AddonToggle(props: { addonKey: string }) {
    const addonPatcher = useAddonPatcher(props.addonKey);
    const { hasPermission } = usePermissionsContext();
    return (
        <DashboardToggle
            enabled={addonPatcher.isEnabled}
            disabled={addonPatcher.isLoading || !hasPermission("site.manage")}
            indeterminate={addonPatcher.isLoading}
            onChange={(enabled) => {
                addonPatcher.setIsEnabled(enabled);
            }}
            tooltip={hasPermission("site.manage") ? undefined : t("Only a site adminstrator can change this setting.")}
        />
    );
}

function TokenLoader() {
    return (
        <>
            <LoadingRectangle height={24} width={80} />
            <LoadingRectangle height={24} width={120} />
            <LoadingRectangle height={24} width={60} />
        </>
    );
}

type ModalProps = { isVisible: boolean; setIsVisible: (val: boolean) => void };

function PremoderatedCategoriesModal(props: ModalProps) {
    const configs = useConfigsByKeys([CONF_PREMOD_CATEGORY_IDS, CONF_PREMOD_COMMENTS, CONF_PREMOD_DISCUSSIONS]);
    const configPatcher = useConfigPatcher();

    const [form, setForm] = useState({});
    const areConfigsLoading = configs.status === LoadStatus.LOADING || configs.status === LoadStatus.PENDING;

    useEffect(() => {
        if (configs.data) {
            setForm(configs.data);
        }
    }, [configs.data]);

    function clearAndClose() {
        setForm(configs.data ?? {});
        props.setIsVisible(false);
    }

    const schema: JsonSchema = {
        type: "object",
        properties: {
            [CONF_PREMOD_CATEGORY_IDS]: {
                type: "array",
                items: {
                    type: "integer",
                },
                "x-control": {
                    label: "Categories",
                    inputType: "dropDown",
                    multiple: true,
                    choices: {
                        api: categoryLookup,
                    },
                },
            },
            [CONF_PREMOD_COMMENTS]: {
                type: "boolean",
                "x-control": {
                    label: "Premoderate Comments",
                    inputType: "checkBox",
                    fullSize: true,
                },
            },
            [CONF_PREMOD_DISCUSSIONS]: {
                type: "boolean",
                "x-control": {
                    label: "Premoderate Discussions",
                    inputType: "checkBox",
                    fullSize: true,
                },
            },
        },
    };

    return (
        <ModalConfirm
            size={ModalSizes.MEDIUM}
            isVisible={props.isVisible}
            onCancel={() => clearAndClose()}
            isConfirmDisabled={areConfigsLoading || configPatcher.isLoading}
            onConfirm={() => {
                void configPatcher.patchConfig(form ?? {}).then(() => {
                    clearAndClose();
                });
            }}
            isConfirmLoading={configPatcher.isLoading}
            confirmTitle={t("Save")}
            title={t("Edit Categories")}
            fullWidthContent
        >
            <JsonSchemaForm
                fieldErrors={configPatcher.error?.errors ?? {}}
                disabled={areConfigsLoading}
                FormControl={FormControlWithNewDropdown}
                FormControlGroup={FormControlGroup}
                schema={schema}
                instance={form}
                onChange={setForm}
            />
        </ModalConfirm>
    );
}

function KeywordModal(props: ModalProps) {
    const configs = useConfigsByKeys([CONF_PREMOD_KEYWORDS]);
    const configPatcher = useConfigPatcher();

    const [keywords, setKeywords] = useState("");
    const areConfigsLoading = configs.status === LoadStatus.LOADING || configs.status === LoadStatus.PENDING;

    useEffect(() => {
        if (configs.data) {
            setKeywords(configs.data?.[CONF_PREMOD_KEYWORDS]);
        }
    }, [configs.data]);

    function clearAndClose() {
        setKeywords(configs.data?.[CONF_PREMOD_KEYWORDS] ?? "");
        props.setIsVisible(false);
    }
    return (
        <ModalConfirm
            size={ModalSizes.MEDIUM}
            isVisible={props.isVisible}
            onCancel={() => clearAndClose()}
            isConfirmDisabled={areConfigsLoading || configPatcher.isLoading}
            onConfirm={() => {
                void configPatcher.patchConfig({ [CONF_PREMOD_KEYWORDS]: keywords }).then(() => {
                    clearAndClose();
                });
            }}
            isConfirmLoading={configPatcher.isLoading}
            confirmTitle={t("Save")}
            title={t("Edit Keywords")}
            fullWidthContent
        >
            <InputTextBlock
                disabled={areConfigsLoading || configPatcher.isLoading}
                label={t("Keywords & Phrases")}
                inputProps={{
                    multiline: true,
                    value: keywords,
                    onChange: (e) => setKeywords(e.target.value),
                }}
                multiLineProps={{
                    rows: 5,
                }}
            />
        </ModalConfirm>
    );
}

export default PremoderationSettingsPage;

function addonEnabled(addonKey: string): boolean {
    const enabledAddonKeys = window.__VANILLA_ENABLED_ADDON_KEYS__ ?? [];
    return enabledAddonKeys.includes(addonKey.toLowerCase());
}

function useAddonPatcher(addonKey: string) {
    const [isEnabled, _setIsEnabled] = useState(addonEnabled(addonKey));
    const toast = useToast();
    const addonMutation = useMutation({
        mutationFn: async (isEnabled: boolean) => {
            _setIsEnabled(isEnabled);
            try {
                await apiv2.patch(`/addons/${addonKey.toLowerCase()}`, {
                    enabled: isEnabled,
                });
                toast.addToast({ body: t("Configuration changes saved."), autoDismiss: true, dismissible: true });
            } catch (err) {
                logError(err);
                toast.addToast({ body: err.message, dismissible: true });
            }
        },
    });

    function setIsEnabled(isEnabled: boolean) {
        addonMutation.mutate(isEnabled);
    }

    const isLoading = addonMutation.isLoading;
    return {
        isEnabled,
        isLoading,
        setIsEnabled,
    };
}
