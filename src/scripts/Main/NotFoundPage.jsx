import React from 'react';
import PropTypes from "prop-types";
import { sprintf } from 'sprintf-js';
import { t } from '@core/application';
import DocumentTitle from '@core/Components/DocumentTitle';

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
    type: PropTypes.string,
    title: PropTypes.string,
    message: PropTypes.string,
};
