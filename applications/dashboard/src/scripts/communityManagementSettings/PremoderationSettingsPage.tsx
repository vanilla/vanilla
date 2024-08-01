/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import AdminLayout from "@dashboard/components/AdminLayout";
import { ModerationNav } from "@dashboard/components/navigation/ModerationNav";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardFormReadOnlySection } from "@dashboard/forms/DashboardFormReadonlySection";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
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
import { FormToggle } from "@library/forms/FormToggle";
import InputTextBlock from "@library/forms/InputTextBlock";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { TokenItem } from "@library/metas/TokenItem";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";
import LinkAsButton from "@library/routing/LinkAsButton";
import SmartLink from "@library/routing/links/SmartLink";
import { ToolTip } from "@library/toolTip/ToolTip";
import { getMeta } from "@library/utility/appUtils";
import { useMutation, useQuery } from "@tanstack/react-query";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { t } from "@vanilla/i18n";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { useCollisionDetector } from "@vanilla/react-utils";
import { logError, uuidv4 } from "@vanilla/utils";
import { useEffect, useState } from "react";
import { sprintf } from "sprintf-js";

const BETA_ENABLED = getMeta("featureFlags.CommunityManagementBeta.Enabled", false);

const CONF_PREMOD_DISCUSSIONS = "premoderation.discussions";
const CONF_PREMOD_COMMENTS = "premoderation.comments";
const CONF_PREMOD_CATEGORY_IDS = "premoderation.categoryIDs";
const CONF_PREMOD_KEYWORDS = "premoderation.keywords";

const classes = {
    main: css({
        padding: "0 18px",
    }),
};

export function PremoderationSettingsPage() {
    const configs = useConfigsByKeys([
        CONF_PREMOD_DISCUSSIONS,
        CONF_PREMOD_COMMENTS,
        CONF_PREMOD_CATEGORY_IDS,
        CONF_PREMOD_KEYWORDS,
    ]);

    const device = useTitleBarDevice();
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
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;

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
        <AdminLayout
            title={t("Premoderation Settings")}
            leftPanel={!isCompact && <ModerationNav />}
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
                        className={dashboardClasses().spaceBetweenFormGroup}
                        label={"Akismet"}
                        description={
                            "Adds Akismet spam filtering to all posts by unverified users and applicant registrations."
                        }
                    >
                        <AddonToggle addonKey={"akismet"} />
                    </DashboardFormGroup>
                    <DashboardFormGroup
                        className={dashboardClasses().spaceBetweenFormGroup}
                        label={"StopForumSpam"}
                        description={
                            "Got spammer problems? This integrates the spammer blacklist from stopforumspam.com to mitigate the issue."
                        }
                    >
                        <AddonToggle addonKey={"stopforumspam"} />
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
    let result = (
        <span className="input-wrap">
            <FormToggle
                enabled={addonPatcher.isEnabled}
                disabled={addonPatcher.isLoading || !hasPermission("site.manage")}
                indeterminate={addonPatcher.isLoading}
                onChange={(enabled) => {
                    addonPatcher.setIsEnabled(enabled);
                }}
            />
        </span>
    );
    if (!hasPermission("site.manage")) {
        result = <ToolTip label={t("Only a site adminstrator can change this setting.")}>{result}</ToolTip>;
    }
    return result;
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
                configPatcher.patchConfig(form ?? {}).then(() => {
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
                configPatcher.patchConfig({ [CONF_PREMOD_KEYWORDS]: keywords } ?? {}).then(() => {
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
