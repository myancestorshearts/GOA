import React from 'react';
import Spacer from '../../../common/components/spacer';
import PanelSearchTable from '../../../common/portal/panel/search-table';
import GoaState from '../../../common/goa-state';


import Refill from '../../../common/models/wallet-transaction/refill';
import Label from '../../../common/models/wallet-transaction/label';

import AdminApi from '../../../common/api/admin';

import toastr from 'toastr';


const WALLET_TRANSACTION_TYPES = {
    Refill: Refill,
    Label: Label
}

const HISTORY_PROPERITES = {
    username: {
        title: 'Name',
        property: 'user,name',
        type: 'TEXT',
        default: true
    },
    useremail: {
        title: 'Email',
        property: 'user,email',
        type: 'TEXT',
        default: true
    },
    type: {
        title: 'Type',
        property: 'type',
        type: 'TEXT',
        default: true
    },
    amount: {
        title: 'Amount',
        property: 'amount',
        type: 'CURRENCY',
        default: true
    },
    balance: {
        title: 'Balance',
        property: 'balance',
        type: 'CURRENCY',
        default: true
    },
    profit: {
        title: 'Profit',
        property: 'profit',
        type: 'CURRENCY',
        default: true,
        sortableColumn: 'profit'
    },
    processing: {
        title: 'Processing Fee',
        property: 'processing_fee',
        type: 'CURRENCY',
        default: true
    },
    finalized: {
        title: 'Date',
        property: 'finalized_at',
        type: 'DATETIME',
        default: true
    },
    weight: {
        title: 'Weight',
        property: 'label,shipment,weight',
        type: 'WEIGHT'
    },
    width: {
        title: 'Width',
        property: 'label,shipment,package,width',
        type: 'LENGTH'
    },
    length: {
        title: 'Length',
        property: 'label,shipment,package,length',
        type: 'LENGTH'
    },
    height: {
        title: 'Height',
        property: 'label,shipment,package,height',
        type: 'LENGTH'
    },
    packagetype: {
        title: 'Package',
        property: 'label,shipment,package,type',
        type: 'TEXT'
    },
    service: {
        title: 'Rate',
        property: 'label,rate,service',
        type: 'TEXT'
    }
};

const PENDING_PROPERTIES = {
    username: {
        title: 'Name',
        property: 'user,name',
        type: 'TEXT',
        default: true
    },
    useremail: {
        title: 'Email',
        property: 'user,email',
        type: 'TEXT',
        default: true
    },
    type: {
        title: 'Type',
        property: 'type',
        type: 'TEXT',
        default: true
    },
    amount: {
        title: 'Amount',
        property: 'amount',
        type: 'CURRENCY',
        default: true
    },
    created: {
        title: 'Date Submitted',
        property: 'created_at',
        type: 'DATETIME',
        default: true
    }
};

export default class Transactions extends React.Component {

    constructor(props) {
        super(props)
        this.state = {
        }

        this.handleSelectActiveTransaction = this.handleSelectActiveTransaction.bind(this);

        this.handleApproveTransaction = this.handleApproveTransaction.bind(this);
    }


    handleSelectActiveTransaction(model) {
        if (WALLET_TRANSACTION_TYPES[model.type]) {
            let Component = WALLET_TRANSACTION_TYPES[model.type]
            GoaState.set('active-model', { model: model, component: <Component model={model} /> })
        }
    }

    handleApproveTransaction(model) {
        AdminApi.WalletTransaction.approve({ id: model.id }, () => {
            toastr.success('Approved transaction');
            if (this.tableTransactionsPending) this.tableTransactionsPending.handleSearch();
            if (this.tableTransactionsCompleted) this.tableTransactionsCompleted.handleSearch();
        }, failure => toastr.error(failure.message));
    }

    render() {
        return (
            <React.Fragment>
                <Spacer space='20px' />
                <PanelSearchTable
                    ref={e => this.tableTransactionsPending = e}
                    properties={PENDING_PROPERTIES}
                    onSelectModel={this.handleSelectActiveTransaction}
                    tableKey='AdminTransactionsPending'
                    tableTitle='Pending'
                    tableIcon='fa fa-refresh'
                    searchMethod={AdminApi.WalletTransaction.search}
                    onApprove={this.handleApproveTransaction}
                    searchArgs={{
                        include_classes: 'address,label,package,rate,shipment,user',
                        pending: 1
                    }}
                />
                <Spacer space='20px' />
                <PanelSearchTable
                    ref={e => this.tableTransactionsCompleted = e}
                    properties={HISTORY_PROPERITES}
                    onSelectModel={this.handleSelectActiveTransaction}
                    tableKey='AdminTransactionsCompleted'
                    tableTitle='Completed'
                    tableIcon='fa fa-check'
                    searchMethod={AdminApi.WalletTransaction.search}
                    searchArgs={{
                        include_classes: 'address,label,package,rate,shipment,user',
                        pending: 0,
                        order_by: 'finalized_at DESC'
                    }}
                />

            </React.Fragment>
        )
    }
}


const STYLES = {
    dashboardActionsContainer: {
        display: 'flex',
    },
    button: {
        marginLeft: '10px'
    },
    buttonsContainer: {
        marginLeft: 'auto',
        display: 'flex'
    }
}

