import React from 'react';
import Panel from '../panel';
import Text from '../../inputs/text';
import Button from '../../inputs/button';
import toastr from 'toastr';
import GoaBrand from '../../brand';
import FlexContainer from '../../components/flex-container';
import PaymentMethod from '../panel/payment-method';
import FormApikey from './forms/add-api';
import FormApiCallback from './forms/add-api-callback';
import FormAddSubUser from './forms/add-sub-user';
import Modal from '../modal/index';
import PanelSearchTable from '../panel/search-table';
import ModelApiCallback from '../../models/api-callback/model';
import Functions from '../../functions';
import { TourMethods } from 'react-shepherd'

import GoaState from '../../goa-state';

const KEY_PROPERITES = {
    name: {
        title: 'Label',
        property: 'name',
        type: 'TEXT',
        default: true
    },
    key: {
        title: 'Key',
        property: 'key',
        type: 'TEXT',
        default: true
    },
    password: {
        title: 'Password',
        property: 'password',
        type: 'TEXT',
        default: true
    },
    created: {
        title: 'Date Created',
        property: 'created_at',
        type: 'DATETIME',
        default: true
    }
};


const CALLBACK_PROPERTIES = {
    name: {
        title: 'Type',
        property: 'type',
        type: 'TEXT',
        default: true
    },
    key: {
        title: 'Callback Url',
        property: 'callback_url',
        type: 'TEXT',
        default: true
    }
};


const SUB_USER_PROPERTIES = {
    name: {
        title: 'Name',
        property: 'name',
        type: 'TEXT',
        default: true
    },
    email: {
        title: 'Email',
        property: 'email',
        type: 'TEXT',
        default: true
    }
}


export default class Profile extends React.Component {

    constructor(props) {
        super(props)

        this.state = {
            user: GoaUser.user,
            password: '',
            newPassword: '',
            confirmNwePassword: ''
        }

        this.handleChange = this.handleChange.bind(this);
        this.handleSaveUser = this.handleSaveUser.bind(this);
        this.handlePasswordSet = this.handlePasswordSet.bind(this);
        this.handleApiAdd = this.handleApiAdd.bind(this);
        this.handleDeleteApiKey = this.handleDeleteApiKey.bind(this);
        this.handleDeleteApiCallback = this.handleDeleteApiCallback.bind(this);
        this.handleSelectApiCallback = this.handleSelectApiCallback.bind(this);
        this.handleDeleteSubUser = this.handleDeleteSubUser.bind(this);
    }

    componentDidMount() {
        this.subscribeUser = GoaUser.subscribe(user => {
            this.setState({ user: user })
        })
    }

    handleChange(property, value) {
        GoaUser.user[property] = value;
        GoaUser.triggerCallbacks();
    }

    componentWillUnmount() {
        GoaUser.unsubscribe(this.subscribeUser);
    }

    handleSaveUser(e, tour) {
        e.preventDefault();
        GoaUser.save(success => {
            toastr.success('Saved Successfully');
            if (tour.isActive()) {
                tour.next();
            }
        }, failure => {
            toastr.error(failure.message);
        });
    }

    handlePasswordSet(e) {
        e.preventDefault();
        if (this.state.newPassword != this.state.confirmNewPassword) {
            toastr.error('Passwords do not match');
            return;
        }
        GoaApi.User.passwordSet({
            current_password: this.state.password,
            new_password: this.state.newPassword
        }, () => {
            this.setState({
                password: '',
                newPassword: '',
                confirmNewPassword: ''
            })
            toastr.success('Set password successfully');
        }, failure => {
            toastr.error(failure.message);
        })
    }

    handleApiAdd() {
        if (this.apiTable) this.apiTable.handleSearch();
        if (this.apiCallbackTable) this.apiCallbackTable.handleSearch();
        this.setState({
            showAdd: false,
            showAddCallback: false
        })
    }

    handleDeleteApiKey(model) {
        GoaApi.ApiKey.delete({ id: model.id }, () => {
            toastr.success('Deleted successfully');
            if (this.apiTable) this.apiTable.handleSearch();
        }, failure => {
            toastr.error(failure.message);
        })
    }

    handleDeleteApiCallback(model) {
        GoaApi.ApiCallback.delete({ id: model.id }, () => {
            toastr.success('Deleted successfully');
            if (this.apiCallbackTable) this.apiCallbackTable.handleSearch();
        }, failure => {
            toastr.error(failure.message);
        })
    }

    handleSelectApiCallback(model) {
        GoaState.set('active-model', {
            model: model, component: <ModelApiCallback
                model={model}
                onPurchase={this.handlePurchase}
            />
        })
    }

    handleDeleteSubUser(model) {

        GoaApi.SubUser.delete({ id: model.id }, () => {
            toastr.success('Deleted successfully');
            if (this.subUserTable) this.subUserTable.handleSearch();
        }, failure => {
            toastr.error(failure.message);
        })
    }

    render() {
        let user = this.state.user;

        let name = user ? user.name : '';
        let email = user ? user.email : '';
        let phone = user ? user.phone : '';

        return (
            <TourMethods>
                {(tourContext) => (
                    <React.Fragment>
                        <FlexContainer>
                            <Panel
                                headerTitle='Basic Information'
                                headerIcon='fa fa-user'
                                className='BasicInfo'
                            >
                                <form onSubmit={(e) => { this.handleSaveUser(e, tourContext) }}>

                                    <Text
                                        title='Name'
                                        autoComplete='name'
                                        onChange={e => this.handleChange('name', e.target.value)}
                                        value={name}
                                    />
                                    <Text
                                        title='Email'
                                        autoComplete='email'
                                        onChange={e => this.handleChange('email', e.target.value)}
                                        value={email}
                                    />
                                    <Text
                                        title='Phone'
                                        autoComplete='phone'
                                        onChange={e => this.handleChange('phone', e.target.value)}
                                        value={phone}
                                    />
                                    <Button
                                        props={{ type: 'submit' }}
                                        color={GoaBrand.getPrimaryColor()}
                                    >
                                        Save
                                    </Button>
                                </form>
                            </Panel>
                            <Panel
                                headerTitle='Security'
                                headerIcon='fa fa-lock'
                            >

                                <form onSubmit={this.handlePasswordSet}>
                                    <Text
                                        title='Current Password'
                                        autoComplete='password'
                                        type='password'
                                        onChange={e => this.setState({ password: e.target.value })}
                                        value={this.state.password}
                                    />
                                    <Text
                                        title='New Password'
                                        autoComplete='newpassword'
                                        type='password'
                                        onChange={e => this.setState({ newPassword: e.target.value })}
                                        value={this.state.newPassword}
                                    />
                                    <Text
                                        title='Confirm New Password'
                                        autoComplete='confirmnewpassword'
                                        type='password'
                                        onChange={e => this.setState({ confirmNewPassword: e.target.value })}
                                        value={this.state.confirmNewPassword}
                                    />
                                    <Button
                                        props={{ type: 'submit' }}
                                        color={GoaBrand.getPrimaryColor()}
                                    >
                                        Set Password
                                    </Button>
                                </form>
                            </Panel>
                            {
                                Functions.isEmpty(user.parent_user_id) ?
                                    <React.Fragment>
                                        {
                                            window.GOA_ENVIRONMENT != 'sandbox' ?
                                                <FlexContainer>
                                                    <PaymentMethod
                                                        type={'ach'}
                                                        headerTitle={'ACH'}
                                                        headerIcon={'fa fa-university'}
                                                        className={'ACH'}
                                                    />
                                                    <PaymentMethod
                                                        type={'cc'}
                                                        headerTitle={'Credit Card'}
                                                        headerIcon={'fa fa-credit-card-alt'}
                                                        classNAme={'CC'}
                                                    />
                                                </FlexContainer> : null
                                        }
                                        <PanelSearchTable
                                            ref={e => this.apiTable = e}
                                            properties={KEY_PROPERITES}
                                            tableKey='ApiKeys'
                                            tableTitle='Api Keys'
                                            tableIcon='fa fa-key'
                                            searchMethod={GoaApi.ApiKey.search}
                                            onDelete={this.handleDeleteApiKey}
                                            onAdd={() => this.setState({ showAdd: true })}
                                        />


                                        <PanelSearchTable
                                            ref={e => this.apiCallbackTable = e}
                                            onSelectModel={this.handleSelectApiCallback}
                                            properties={CALLBACK_PROPERTIES}
                                            tableKey='ApiCallbacks'
                                            tableTitle='Api Callbacks'
                                            tableIcon='fa fa-share'
                                            searchMethod={GoaApi.ApiCallback.search}
                                            onDelete={this.handleDeleteApiCallback}
                                            onAdd={() => this.setState({ showAddCallback: true })}
                                        />


                                        <PanelSearchTable
                                            ref={e => this.subUserTable = e}
                                            properties={SUB_USER_PROPERTIES}
                                            tableKey='SubUsers'
                                            tableTitle='Sub Users'
                                            tableIcon='fa fa-users'
                                            searchMethod={GoaApi.SubUser.search}
                                            onDelete={this.handleDeleteSubUser}
                                            onAdd={() => {
                                                GoaState.set('active-modal', {
                                                    component: <FormAddSubUser
                                                        onAdd={() => {
                                                            GoaState.empty('active-modal');
                                                            if (this.subUserTable) this.subUserTable.handleSearch();
                                                        }}
                                                    />
                                                })
                                            }}
                                        />
                                    </React.Fragment> : null
                            }
                        </FlexContainer>

                        {
                            this.state.showAdd ?
                                <Modal>
                                    <FormApikey
                                        onAdd={this.handleApiAdd}
                                        onCancel={() => this.setState({ showAdd: false })}
                                    />
                                </Modal> : null
                        }
                        {
                            this.state.showAddCallback ?
                                <Modal>
                                    <FormApiCallback
                                        onAdd={this.handleApiAdd}
                                        onCancel={() => this.setState({ showAddCallback: false })}
                                    />
                                </Modal> : null
                        }
                    </React.Fragment>
                )}
            </TourMethods>
        )
    }
}