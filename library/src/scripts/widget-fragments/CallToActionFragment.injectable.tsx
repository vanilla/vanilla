import type { ButtonType } from "@library/forms/buttonTypes";
import type { BorderType } from "@library/styles/styleHelpersBorders";
import type { ImageSourceSet } from "@library/utility/appUtils";

namespace CallToActionFragmentInjectable {
    export interface Props {
        title: string;
        description?: string;
        alignment?: "center" | "left";
        textColor?: string;
        borderType?: BorderType;
        button?: {
            title?: string;
            type?: ButtonType;
            url?: string;
            shouldUseButton: boolean;
        };
        secondButton?: {
            title: string;
            type?: ButtonType;
            url: string;
        };
        background?: {
            color?: string;
            image?: string;
            imageUrlSrcSet?: ImageSourceSet;
            useOverlay?: boolean;
        };
    }
}

export default CallToActionFragmentInjectable;
