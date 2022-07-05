/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { languageSettingsStyles } from "@dashboard/languages/LanguageSettings.styles";
import { IAddon, ITranslationService } from "@dashboard/languages/LanguageSettingsTypes";
import { cx } from "@emotion/css";
import { useLocaleConfig } from "@library/config/configHooks";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import React, { useEffect, useMemo, useState } from "react";

const baseSchema: JsonSchema = {
    type: "object",
    properties: {
        translationService: {
            type: "string",
            "x-control": {
                inputType: "dropDown",
                choices: {
                    staticOptions: {},
                },
            },
        },
    },
    required: ["translationService"],
};

export interface IProps {
    isVisible: boolean;
    locale: IAddon | null;
    onExit(): void;
    modalSize?: ModalSizes;
}

export const LocaleConfigurationModal = (props: IProps) => {
    const { isVisible, onExit, locale, modalSize } = props;
    const titleID = useUniqueID("configureLanguage_Modal");
    const classes = languageSettingsStyles();
    const classFrameFooter = frameFooterClasses();
    const classesFrameBody = frameBodyClasses();
    const [value, setValue] = useState<JsonSchema>({});

    const [schema, setSchema] = useState(baseSchema);

    const { translationService, configuredServices, setTranslationService } = useLocaleConfig({
        localeID: locale?.key,
    });

    const defaultServiceType = useMemo(() => {
        if (configuredServices) {
            return configuredServices.find((service) => service.isDefault)?.type ?? "none";
        }
        return "";
    }, [configuredServices]);

    useEffect(() => {
        if (translationService) {
            setValue({
                translationService: translationService.type === null ? defaultServiceType : translationService.type,
            });
        }
    }, [translationService]);

    // TODO: Replace this with schema from the API in the future
    useEffect(() => {
        if (configuredServices) {
            const choices = Object.fromEntries(configuredServices.map((service) => [service.type, service.name]));
            setSchema((prevSchema) => {
                const loadedSchema = prevSchema;
                loadedSchema.properties.translationService["x-control"].choices.staticOptions = {
                    none: "None",
                    ...choices,
                };
                return loadedSchema;
            });
        }
    }, [configuredServices]);

    const setService = (selection) => {
        if (locale) {
            setTranslationService({
                localeID: locale.key,
                service:
                    !selection.translationService || selection.translationService === "null"
                        ? null
                        : selection.translationService,
            });
            onExit();
        }
    };

    return (
        <Modal
            isVisible={isVisible}
            size={modalSize ? modalSize : ModalSizes.MEDIUM}
            exitHandler={() => {
                onExit();
            }}
            titleID={titleID}
            className={classes.modalSuggestionOverride}
        >
            <Frame
                header={
                    <FrameHeader
                        titleID={titleID}
                        closeFrame={() => {
                            onExit();
                        }}
                        title={locale && locale.name}
                    />
                }
                bodyWrapClass={classes.modalSuggestionOverride}
                body={
                    <FrameBody>
                        <div className={cx("frameBody-contents", classesFrameBody.contents)}>
                            <JsonSchemaForm
                                schema={schema}
                                instance={value}
                                FormSection={({ children }) => (
                                    <>
                                        <p>{t("Select the Translation Service Provider to use for this language.")}</p>
                                        {children}
                                    </>
                                )}
                                FormControlGroup={DashboardFormControlGroup}
                                FormControl={DashboardFormControl}
                                onChange={setValue}
                            />
                        </div>
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight={true}>
                        <Button
                            className={classFrameFooter.actionButton}
                            buttonType={ButtonTypes.TEXT}
                            onClick={() => {
                                onExit();
                            }}
                        >
                            {t("Cancel")}
                        </Button>
                        <Button
                            className={classFrameFooter.actionButton}
                            onClick={() => setService(value)}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                        >
                            {t("Save")}
                        </Button>
                    </FrameFooter>
                }
            />
        </Modal>
    );
};
