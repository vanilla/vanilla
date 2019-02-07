/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import splashStyles from "@library/components/splash/splashStyles";
import Heading from "@library/components/Heading";
import { color, ColorHelper } from "csx";
import { BackgroundImageProperty } from "csstype";
import { style } from "typestyle";
import Container from "@library/components/layouts/components/Container";
import { PanelWidget } from "@library/components/layouts/PanelLayout";
import SearchBar from "@library/components/forms/select/SearchBar";
import SearchPageActions, { ISearchFormActionProps } from "@knowledge/modules/search/SearchPageActions";
import { IWithSearchProps, withSearch } from "@library/contexts/SearchContext";
import { connect } from "react-redux";
import { withDevice } from "@library/contexts/DeviceContext";
import SearchPageModel from "@knowledge/modules/search/SearchPageModel";
import { IDeviceProps } from "@library/components/DeviceChecker";

interface ISplashStyles extends IDeviceProps, IWithSearchProps {
    colors?: {
        fg?: ColorHelper;
        bg?: ColorHelper;
        primary?: ColorHelper;
    };
    backgroundImage?: BackgroundImageProperty;
    fullWidth?: boolean;
    transparentButton?: boolean;
}

interface IProps extends ISearchFormActionProps, IWithSearchProps {
    title: string; // Often the message to display isn't the real H1
    className?: string;
    styles: ISplashStyles;
}

/**
 * A component representing a single crumb in a breadcrumb component.
 */
export class Splash extends React.Component<IProps> {
    public render() {
        const classes = splashStyles();
        const { title, className, styles } = this.props;
        return (
            <div className={classNames("splash", className, classes.root, { backgroundStyles: !styles.fullWidth! })}>
                <Container className="splash-container">
                    <PanelWidget>
                        {title && <Heading title={title} />}
                        <SearchBar
                        // placeholder={this.props.placeholder || t("Help")}
                        // onChange={this.handleSearchChange}
                        // loadOptions={this.autocomplete}
                        // value={this.props.form.query}
                        // isBigInput={true}
                        // onSearch={this.props.searchActions.search}
                        // optionComponent={SearchOption}
                        // triggerSearchOnClear={true}
                        // title={t("Search")}
                        // titleAsComponent={<>{t("Search")}</>}
                        />
                    </PanelWidget>
                </Container>
            </div>
        );
    }
}

const withRedux = connect(
    SearchPageModel.mapStateToProps,
    SearchPageActions.mapDispatchToProps,
);

export default withRedux(withSearch(Splash));
