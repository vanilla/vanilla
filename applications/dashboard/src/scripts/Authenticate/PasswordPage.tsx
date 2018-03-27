import React from 'react';
import DocumentTitle from '@core/Components/DocumentTitle';
import { t } from '@core/application';

export default class PasswordPage extends React.Component {
    render() {
        return <DocumentTitle title={t('Sign In')}/>;
    }
}
