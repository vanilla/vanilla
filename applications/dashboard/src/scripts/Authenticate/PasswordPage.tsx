import apiv2 from "@core/apiv2";
import { formatUrl, t } from '@core/application';
import DocumentTitle from '@core/Components/DocumentTitle';
import React from 'react';
import { withRouter } from 'react-router-dom';

class PasswordPage extends React.Component {
    constructor(props) {
        super(props);
    }
    //
    // // Disable button when in submit state
    // // Error handling from server side messages
    // // If errors is empty, use global message, if not ignore and use per input messages
    //
    // handleSubmit() {
    //     this.setState({status: submitting});
    //
    //     apiv2.post({
    //         username: this.username,
    //         password: this.password,
    //         persist: this.persist,
    //     }).then((r) => {
    //         // Do the redirect.
    //         let target = this.props.location.query.target || '/';
    //         window.location.href = formats
    //     }).catch((e) => {
    //         this.setState({
    //             status: undefined,
    //             errors: normalizeErorrs(response.data.errors),
    //         });
    //     };
    // }

    public render() {
        return <DocumentTitle classNames="isCentered" title={t('Sign In')}/>;
    }
}

export default withRouter(PasswordPage);
