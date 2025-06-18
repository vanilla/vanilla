/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { Icon } from "@vanilla/icons";
import Button from "@library/forms/Button";
import { Row } from "@library/layout/Row";
import { t } from "@vanilla/i18n";
import { useIsMounted } from "@vanilla/react-utils";
import { useState } from "react";
import { useToast } from "@library/features/toaster/ToastContext";

type IProps = Omit<React.ComponentProps<typeof Button>, "onClick"> & {
    url: string;
};

export function CopyLinkButton(props: IProps) {
    const { url, ...rest } = props;
    const isMounted = useIsMounted();
    const toast = useToast();

    return (
        <Button
            {...rest}
            onClick={() => {
                navigator.clipboard
                    .writeText(url)
                    .then(() => {
                        if (isMounted()) {
                            toast.addToast({ body: t("Link copied to clipboard"), autoDismiss: true });
                        }
                    })
                    .catch((error) => {
                        if (isMounted()) {
                            toast.addToast({ body: t("Failed to copy link to clipboard"), autoDismiss: true });
                        }
                    });
            }}
        >
            <Row align={"center"} gap={4}>
                <Icon icon="copy-link" />
                {props.children}
            </Row>
        </Button>
    );
}
