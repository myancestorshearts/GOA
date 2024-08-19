import React from 'react';
import Spacer from '../../../common/components/spacer';
import PanelSearchTable from '../../../common/portal/panel/search-table';

import Functions from '../../../common/functions';
import GoaState from '../../../common/goa-state';

import ActionPanel from '../../../common/portal/panel/action';

import toastr from 'toastr';

import FormInviteReferral from './forms/invite-referral';


import ReferralModel from '../../../common/models/referral/model';

const REFERRAL_PROPERTIES = {
    name: {
        title: 'Name',
        property: 'name',
        type: 'TEXT',
        default: 'true'
    },
    amount: {
        title: 'Email',
        property: 'email',
        type: 'TEXT',
        default: 'true'
    },
    link: {
        title: 'Link',
        type: 'METHOD',
        method: model => {
            if (Functions.isEmpty(model.label_link)) {
                return 'Pending';
            }
            else return <a href={model.label_link}>Download</a>
        },
        default: 'true'
    }
};

export default class Orders extends React.Component {

    constructor(props) {
        super(props)
        this.state = {
        }

        this.handleCopyLink = this.handleCopyLink.bind(this);
        this.handleShowInvite = this.handleShowInvite.bind(this);
        this.handleSelectReferral = this.handleSelectReferral.bind(this);
    }

    handleCopyLink() {
        let link = window.location.origin + '/register?code=' + this.props.user.referral_code;
        Functions.copyToClipboard(link, () => {
            toastr.success('Copied referral link');
        }, () => {
            toastr.error('Failed to copy link: ' + link);
        })
    }

    handleShowInvite() {
        GoaState.set('active-modal', {
            component: <FormInviteReferral
                onCancel={() => GoaState.empty('active-modal')}
            />
        });
    }


    handleSelectReferral(model) {
        console.log(model);
        GoaState.set('active-model', {
            model: model, component: <ReferralModel
                model={model}
            />
        })
    }

    render() {
        return (
            <React.Fragment>
                <div style={STYLES.dashboardActionsContainer}>
                    <ActionPanel label='Refer by Email' icon='fa fa-envelope' onClick={this.handleShowInvite} />
                    <Spacer space='20px' />
                    <ActionPanel label='Copy Referral Link' icon='fa fa-link' onClick={this.handleCopyLink} />
                </div>

                <Spacer space='20px' />

                <PanelSearchTable
                    onSelectModel={this.handleSelectReferral}
                    properties={REFERRAL_PROPERTIES}
                    tableKey='Referrals'
                    tableTitle='Referrals'
                    tableIcon='fa fa-users'
                    searchMethod={GoaApi.Referral.search}
                />
            </React.Fragment>
        )
    }
}


const STYLES = {
    dashboardActionsContainer: {
        display: 'flex',
    }
}

