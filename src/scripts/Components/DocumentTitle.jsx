import React from 'react';
import PropTypes from 'prop-types';
import { getMeta } from "@core/application";

export default class DocumentTitle extends React.Component {
    componentDidMount() {
        document.title = this.getHeadTitle(this.props);
    }

    componentWillUpdate(nextProps) {
        document.title = this.getHeadTitle(nextProps);
    }

    getHeadTitle(props) {
        const siteTitle = getMeta('title', '');
        const parts = [];

        if (props.title && props.title.length > 0) {
            parts.push(props.title);
        }
        if (siteTitle.length > 0 && siteTitle !== props.title) {
            parts.push(siteTitle);
        }

        return parts.join(' - ');
    }

    render() {
        if (this.props.children && this.props.children.length > 0) {
            return this.props.children;
        } else {
            return <h1>{this.props.title}</h1>;
        }
    }
}

DocumentTitle.propTypes = {
    title: PropTypes.string,
}
