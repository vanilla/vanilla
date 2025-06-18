/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { NavigationLinksModal } from "@dashboard/components/navigation/NavigationLinksModal";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import type { ICustomControlProps } from "@library/json-schema-forms";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";
import { useState } from "react";

export default function NavigationModalControlLoadable(props: ICustomControlProps) {
    const [showModal, setShowModal] = useState(false);
    return (
        <>
            <Button
                onClick={() => {
                    setShowModal(true);
                }}
                buttonType={"standard"}
            >
                {t("Edit Links")}
            </Button>
            {showModal && (
                <NavigationLinksModal
                    title={t("Edit Links")}
                    description={
                        <Translate
                            source={
                                "Hide or rename our default links, or create your own. To create a link to a specific page or section of your community or KB, enter the path. To create an external link, include the full URL. Find out more in the <0>documentation</0>."
                            }
                            c0={(text) => (
                                <SmartLink to="https://success.vanillaforums.com/kb/articles/397-customizing-the-title-bar#navigation-links">
                                    {text}
                                </SmartLink>
                            )}
                        />
                    }
                    isNestingEnabled={true}
                    onCancel={() => {
                        setShowModal(false);
                    }}
                    navigationItems={props.value ?? []}
                    onSave={(newValue) => {
                        props.onChange(newValue);
                        setShowModal(false);
                    }}
                />
            )}
        </>
    );
}
