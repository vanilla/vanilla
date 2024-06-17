import IntegrationModal from "@library/features/discussions/integrations/IntegrationModal";
import { useWriteableIntegrationContext } from "@library/features/discussions/integrations/Integrations.context";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { useState } from "react";

export function IntegrationButtonAndModal(props: { onSuccess?: () => Promise<void> }) {
    const { label } = useWriteableIntegrationContext();
    const { onSuccess } = props;
    const [isVisible, setIsVisible] = useState(false);
    return (
        <>
            <DropDownItemButton onClick={() => setIsVisible(true)}>{label}</DropDownItemButton>
            <IntegrationModal isVisible={isVisible} exitHandler={() => setIsVisible(false)} onSuccess={onSuccess} />
        </>
    );
}
