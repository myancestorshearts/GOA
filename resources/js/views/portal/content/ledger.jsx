import React from 'react';
import Spacer from '../../../common/components/spacer';
import PanelSearchTable from '../../../common/portal/panel/search-table';
import GoaState from '../../../common/goa-state';


import FormAddFunds from './forms/add-funds';
import FormExportWalletTransactions from './forms/export-wallet-transactions';

import Refill from '../../../common/models/wallet-transaction/refill';
import Label from '../../../common/models/wallet-transaction/label';

import ActionPanel from '../../../common/portal/panel/action';
import Functions from '../../../common/functions';



const WALLET_TRANSACTION_TYPES = {
    Refill: Refill,
    Label: Label
}

const HISTORY_PROPERITES = {
    type: {
        title: 'Type',
        property: 'type',
        type: 'TEXT',
        default: 'true'
    },
    amount: {
        title: 'Amount',
        property: 'amount',
        type: 'CURRENCY',
        default: 'true'
    },
    balance: {
        title: 'Balance',
        property: 'balance',
        type: 'CURRENCY',
        default: 'true'
    },
    processing: {
        title: 'Processing Fee',
        property: 'processing_fee',
        type: 'CURRENCY',
        default: 'true'
    },
    finalized: {
        title: 'Date',
        property: 'finalized_at',
        type: 'DATETIME',
        default: 'true'
    }
};

const PENDING_PROPERTIES = {
    type: {
        title: 'Type',
        property: 'type',
        type: 'TEXT',
        default: 'true'
    },
    amount: {
        title: 'Amount',
        property: 'amount',
        type: 'CURRENCY',
        default: 'true'
    },
    created: {
        title: 'Date Submitted',
        property: 'created_at',
        type: 'DATETIME',
        default: 'true'
    }
};

export default class Orders extends React.Component {

    constructor(props) {
        super(props)

        this.state = {
            user: GoaUser.user
        }

        this.handleSelectActiveTransaction = this.handleSelectActiveTransaction.bind(this);
        this.handleAddedFunds = this.handleAddedFunds.bind(this);
        this.handleShowAddFunds = this.handleShowAddFunds.bind(this);
        this.handleExport = this.handleExport.bind(this);
    }


    componentDidMount() {
        this.subscribeUser = GoaUser.subscribe(user => {
            this.setState({ user: user })
        })
    }

    componentWillUnmount() {
        GoaUser.unsubscribe(this.subscribeUser);
    }

    handleSelectActiveTransaction(model) {
        if (WALLET_TRANSACTION_TYPES[model.type]) {
            let Component = WALLET_TRANSACTION_TYPES[model.type]
            GoaState.set('active-model', { model: model, component: <Component model={model} /> })
        }
    }

    handleAddedFunds() {
        GoaState.empty('active-modal')

        if (this.tableTransactionsCompleted) this.tableTransactionsCompleted.handleSearch();
        if (this.tableTransactionsPending) this.tableTransactionsPending.handleSearch();
    }

    handleShowAddFunds() {
        GoaState.set('active-modal', {
            component: <FormAddFunds
                onAddedFunds={this.handleAddedFunds}
                onCancel={() => GoaState.empty('active-modal')}
            />
        });
    }

    handleExport() {
        GoaState.set('active-modal', {
            component: <FormExportWalletTransactions
                onExport={() => GoaState.empty('active-modal')}
            />
        });
    }

    render() {
        if (Functions.isEmpty(this.state.user)) return null;
        return (
            <React.Fragment>
                {
                    Functions.isEmpty(this.state.user.parent_user_id) ?
                        <React.Fragment>
                            <div style={STYLES.dashboardActionsContainer}>
                                <ActionPanel label='Add Funds' icon='fa fa-dollar' onClick={this.handleShowAddFunds} />
                            </div>

                            <Spacer space='20px' />
                        </React.Fragment> : null
                }
                <PanelSearchTable
                    ref={e => this.tableTransactionsPending = e}
                    properties={PENDING_PROPERTIES}
                    onSelectModel={this.handleSelectActiveTransaction}
                    tableKey='TransactionsPending'
                    tableTitle='Pending'
                    tableIcon='fa fa-refresh'
                    searchMethod={GoaApi.WalletTransaction.search}
                    searchArgs={{
                        include_classes: 'address,label,package,rate,shipment',
                        pending: 1
                    }}
                />
                <Spacer space='20px' />
                <PanelSearchTable
                    ref={e => this.tableTransactionsCompleted = e}
                    properties={HISTORY_PROPERITES}
                    onSelectModel={this.handleSelectActiveTransaction}
                    onExport={this.handleExport}
                    tableKey='TransactionsCompleted'
                    tableTitle='Completed'
                    tableIcon='fa fa-check'
                    searchMethod={GoaApi.WalletTransaction.search}
                    searchArgs={{
                        include_classes: 'address,label,package,rate,shipment',
                        pending: 0
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

