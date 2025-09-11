/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { SearchBarPresets } from "@library/banner/SearchBarPresets";
import IndependentSearch from "@library/features/search/IndependentSearch";
import { ISearchScopeNoCompact } from "@library/features/search/SearchScopeContext";
import { ButtonType } from "@library/forms/buttonTypes";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { PageBox } from "@library/layout/PageBox";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import { styleUnit } from "@library/styles/styleUnit";
import { useFragmentImpl } from "@library/utility/FragmentImplContext";
import { t } from "@vanilla/i18n";
import { searchClasses } from "@library/search/SearchWidget.styles";
import { bannerVariables } from "@library/banner/Banner.variables";

interface ISearchWidgetProps {
    title?: string;
    description?: string;
    subtitle?: string;
    containerOptions?: IHomeWidgetContainerOptions;
    scope?: ISearchScopeNoCompact;
    placeholder?: string;
    initialParams?: React.ComponentProps<typeof IndependentSearch>["initialParams"];
    hideButton?: boolean;
    domain?: string;
    postType?: string;
    places?: string[];
    borderRadius?: string;
}

export function SearchWidget(props: ISearchWidgetProps) {
    const Impl = useFragmentImpl("SearchWidgetFragment", SearchWidgetImpl);

    return <Impl {...props} />;
}

export function SearchWidgetImpl(props: ISearchWidgetProps) {
    const {
        title,
        subtitle,
        description,
        scope,
        initialParams,
        placeholder,
        domain,
        postType,
        places,
        containerOptions,
        hideButton,
        borderRadius,
    } = props;

    const device = useDevice();
    const vars = bannerVariables();
    const searchStyles = searchClasses(
        styleUnit(borderRadius && borderRadius?.length > 0 ? borderRadius : vars.searchBar.border.radius),
    );

    const makeParams = () => {
        let params = initialParams || {};
        if (domain) {
            params["domain"] = domain;
        }
        if (postType) {
            params["types"] = postType;
        }
        if (domain === "places") {
            params["types"] = places;
        }
        return params;
    };

    return (
        <LayoutWidget>
            <PageBox>
                <PageHeadingBox
                    title={title}
                    subtitle={subtitle}
                    description={description}
                    options={{
                        alignment: containerOptions?.headerAlignment,
                    }}
                />

                <div style={{ position: "relative" }}>
                    <IndependentSearch
                        buttonClass={searchStyles.searchButton}
                        buttonType={ButtonType.PRIMARY}
                        isLarge={true}
                        placeholder={placeholder ?? t("CommunitySearchPlaceHolder", "Search")}
                        hideSearchButton={hideButton}
                        contentClass={searchStyles.content}
                        scope={scope}
                        initialParams={makeParams()}
                        overwriteSearchBar={{
                            borderRadius: styleUnit(
                                borderRadius && borderRadius?.length > 0 ? borderRadius : vars.searchBar.border.radius,
                            ),
                            preset: SearchBarPresets.BORDER,
                            compact: device === Devices.MOBILE || device === Devices.XS,
                        }}
                    />
                </div>
            </PageBox>
        </LayoutWidget>
    );
}

export default SearchWidget;
