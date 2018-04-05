import DocumentTitle from '@core/Components/DocumentTitle';
import PropTypes from "prop-types";
import React from 'react';
import { sprintf } from 'sprintf-js';
import { t } from '@core/application';

export default class NotFoundPage extends React.PureComponent {

    get title() {
        return this.props.title || sprintf(t('%s Not Found'), t(this.props.type));
    }

    get message() {
        return this.props.message || sprintf(t('The %s you were looking for could not be found.'), t(this.props.type.toLowerCase()));
    }

    render() {
        return <div className="Center SplashInfo">
            <DocumentTitle title={this.title}/>
            <div>{this.message}</div>
        </div>;
    }
}

NotFoundPage.defaultProps = {
    type: "Page",
};

NotFoundPage.propTypes = {
    message: PropTypes.string,
    title: PropTypes.string,
    type: PropTypes.string,
};
