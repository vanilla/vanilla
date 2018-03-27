import React from 'react';
import { t } from '@core/application';
import DocumentTitle from '@core/Components/DocumentTitle';

export default class SignInPage extends React.Component {
    render() {
        return <DocumentTitle title={t('Sign In')}/>;
    }
}
