
import React from 'react';
import Spacer from '../../../common/components/spacer';
import PanelSearchTable from '../../../common/portal/panel/search-table';

import UserModel from '../../../common/models/user/model';

import toastr from 'toastr';

import AdminApi from '../../../common/api/admin';
import GoaState from '../../../common/goa-state';

const USER_PROPERITES = {
    name: {
        title: 'Name',
        property: 'name',
        type: 'TEXT',
        default: true
    },
    company: {
        title: 'Company',
        property: 'company',
        type: 'TEXT',
        default: true
    },
    email: {
        title: 'Email',
        property: 'email',
        type: 'TEXT',
        default: true
    },
    phone: {
        title: 'Phone',
        property: 'phone',
        type: 'TEXT',
        default: true
    },
    verified: {
        title: 'Verified',
        property: 'verified',
        type: 'BOOLEAN',
        default: true
    },
    approved: {
        title: 'Status',
        property: 'status',
        type: 'TEXT',
        default: true
    },
    admin: {
        title: 'Admin',
        property: 'admin',
        type: 'BOOLEAN',
        default: true
    },
    referralprogram: {
        title: 'Referral Program',
        property: 'referral_program',
        type: 'BOOLEAN',
        default: true
    },
    referralprogramtype: {
        title: 'Referral Program Type',
        property: 'referral_program_type',
        type: 'Text',
        default: true
    },
    balance: {
        title: 'Account Balance',
        property: 'wallet_balance',
        type: 'CURRENCY',
        default: true
    },
};

export default class Clients extends React.Component {

    constructor(props) {
        super(props)
        this.state = {
            selectedModel: null,
        }

        this.handleApproveUser = this.handleApproveUser.bind(this)
    }

    handleApproveUser(model) {
        AdminApi.User.approve({ id: model.id }, () => {
            toastr.success('Approved client with email: ' + model.email)
            if (this.clientsPending) this.clientsPending.handleSearch();
            if (this.clients) this.clients.handleSearch();
        }, failure => toastr.error(failure.message));
    }

    handleSelectModel(model) {
        GoaState.set('active-model', { model: model, component: <UserModel model={model} /> })
    }

    render() {
        return (
            <React.Fragment>
                <PanelSearchTable
                    ref={e => this.clientsPending = e}
                    properties={USER_PROPERITES}
                    tableKey='AdminClientsPending'
                    tableTitle='Waiting Approval'
                    tableIcon='fa fa-refresh'
                    searchMethod={AdminApi.User.search}
                    onApprove={this.handleApproveUser}
                    searchArgs={{
                        status: 'PENDING'
                    }}
                    showSearch={true}
                />
                <Spacer space='20px' />
                <PanelSearchTable
                    ref={e => this.clients = e}
                    properties={USER_PROPERITES}
                    onSelectModel={this.handleSelectModel}
                    tableKey='AdminClients'
                    tableTitle='Clients'
                    tableIcon='fa fa-users'
                    searchMethod={AdminApi.User.search}
                    showSearch={true}
                />

            </React.Fragment>
        )
    }
}
