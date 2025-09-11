/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { BannerAlignment } from "@library/banner/Banner.variables";
import { DefaultBannerBg } from "@library/banner/DefaultBannerBg";
import IndependentSearch, { IIndependentSearchProps } from "@library/features/search/IndependentSearch";
import { IBackground } from "@library/styles/cssUtilsTypes";
import { useFragmentImpl } from "@library/utility/FragmentImplContext";
import { VanillaButtonProps } from "@library/widget-fragments/Components.injectable";
import { t } from "@vanilla/i18n";
import { RecordID } from "@vanilla/utils";

namespace BannerFragmentInjectable {
    export interface Props {
        // Data
        title?: string;
        titleType: "none" | "static" | string;
        description?: string;
        descriptionType: "none" | "static" | string;
        background?: Partial<IBackground> & {
            color?: string;
            useOverlay?: boolean;
            imageUrlSrcSet?: Record<RecordID, string>;
            imageSource?: string;
        };
        showSearch?: boolean;
        textColor?: string;
        alignment?: BannerAlignment;
    }

    export interface SearchProps extends Pick<IIndependentSearchProps, "buttonClass" | "hideSearchButton"> {
        buttonType: VanillaButtonProps["buttonType"];
        compact?: boolean;
    }
}

const SearchInput = (props: BannerFragmentInjectable.SearchProps) => {
    const Impl = useFragmentImpl("SearchWidgetFragment", DefaultBannerSearchImpl);
    return <Impl {...props} />;
};

const DefaultBannerSearchImpl = (props: BannerFragmentInjectable.SearchProps) => {
    return (
        <div>
            <IndependentSearch
                buttonClass={props.buttonClass}
                buttonType={props.buttonType}
                isLarge
                placeholder={t("SearchBoxPlaceHolder", "Search")}
                hideSearchButton={props.hideSearchButton}
                overwriteSearchBar={{
                    compact: props.compact,
                }}
            />
        </div>
    );
};

const DefaultBannerImage = ({ backgroundColor }) => {
    return <DefaultBannerBg bgColor={backgroundColor} />;
};

const BannerFragmentInjectable = {
    SearchInput,
    DefaultBannerImage,
};

export default BannerFragmentInjectable;
