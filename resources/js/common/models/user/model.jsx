import React from 'react';
import Header from '../../../common/fields/header.jsx';
import Label from '../../fields/label';
import Spacer from '../../components/spacer';
import Functions from '../../functions';
import Button from '../../inputs/button';
import toastr, { success } from 'toastr';
import GoaBrand from '../../brand';
import Boolean from '../../../common/inputs/boolean';
import Text from '../../../common/inputs/text';

import AdminApi from '../../../common/api/admin';
import Storage from '../../storage';

import ComponentLoading from '../../components/loading';
export default class Model extends React.Component {

    constructor(props) {
        super(props)

        this.state = {
            rates: {},
            ratesLoading: true
        };

        this.handleSave = this.handleSave.bind(this);
        this.handleModelUpdate = this.handleModelUpdate.bind(this);
        this.handleRateUpdate = this.handleRateUpdate.bind(this);
        this.handleLogin = this.handleLogin.bind(this);

    }

    componentDidUpdate(nextProps) {
        if (nextProps.model.id != this.props.model.id) this.searchRateDiscounts();
    }

    componentDidMount() {
        this.searchRateDiscounts();
    }

    searchRateDiscounts() {
        this.setState({
            ratesLoading: true
        }, () => {
            AdminApi.RateDiscounts.get({ id: this.props.model.id }, success => {
                this.setState({
                    rates: success.data.model.rates,
                    ratesLoading: false
                })
            })
        })
    }

    handleSave() {
        AdminApi.User.set(this.props.model, success => {
            toastr.success('Saved user');
        }, failure => {
            toastr.error(failure.message);
        })

        AdminApi.RateDiscounts.set({
            id: this.props.model.id,
            rates: this.state.rates
        }, () => {

        }, failure => {
            toastr.error('Failed to save rates: ' + failure.message);
        })
    }

    handleModelUpdate(value, property) {
        this.props.model[property] = value;
        this.forceUpdate();
    }

    handleRateUpdate(value, property) {
        Functions.deepSetFromString(this.state, property, value)
        this.forceUpdate();
    }

    handleLogin() {
        AdminApi.User.tokens(this.props.model, success => {
            Storage.set('goa-loginasuser-tokens', success.data.tokens);
            window.location = '/portal'
        }, failure => {
            toastr.error(failure.message);
        })
    }

    render() {
        let model = this.props.model;

        return (
            <React.Fragment>
                <Header title='Details' top={true}/>
                <Spacer space='10px' />

                <Label label='Customer Name'>
                    {Functions.deepGetFromString(model, 'name', '')}
                </Label>

                <Label label='Company'>
                    {Functions.deepGetFromString(model, 'company', '')}
                </Label>

                <Label label='Email'>
                    {Functions.deepGetFromString(model, 'email', '')}
                </Label>

                <Label label='Phone'>
                    {Functions.deepGetFromString(model, 'phone', '')}
                </Label>

                <Label label='Email Verified'>
                    <Spacer space='5px' />
                    <Boolean title='' model={this.props.model} property='verified' onChange={() => this.forceUpdate()} />
                </Label>

                <Spacer space='10px' />

                <Button
                    color={GoaBrand.getPrimaryColor()}
                    stylesbutton={{ marginTop: '0px' }}
                    props={{ onClick: this.handleLogin }}
                >
                    Login As User
                </Button>

                <Spacer space='10px' />
                <Header title='Payment' />
                <Spacer space='10px' />

                <Label label='Ach Auto Approve'>
                    <Spacer space='5px' />
                    <Boolean title='' model={this.props.model} property='ach_auto' onChange={() => this.forceUpdate()} />
                </Label>

                <Spacer space='10px' />
                <Header title='Referral Program' />
                <Spacer space='10px' />

                <Label label='Approved'>
                    <Spacer space='5px' />
                    <Boolean title='' model={this.props.model} property='referral_program' onChange={() => this.forceUpdate()} />
                </Label>



                {
                    Functions.inputToBool(this.props.model.referral_program) ?
                        <React.Fragment>
                            <Label label='Link'>
                                {window.location.origin}/register?code={this.props.model.referral_code}
                            </Label>
                        </React.Fragment>
                        : null
                }

                {
                    window.GOA_LABEL_SERVICE == 'usps' ?
                        <React.Fragment>
                            <Spacer space='10px' />
                            <Header title='Usps Webtool Ids' />
                            <Spacer space='10px' />
                            <Label label='Evs'>
                                <Text
                                    value={this.props.model.usps_webtools_evs || ''}
                                    onChange={x => this.handleModelUpdate(x.target.value, 'usps_webtools_evs')}
                                />
                            </Label>
                            <Label label='Returns'>
                                <Text
                                    value={this.props.model.usps_webtools_returns || ''}
                                    onChange={x => this.handleModelUpdate(x.target.value, 'usps_webtools_returns')}
                                />
                            </Label>
                        </React.Fragment>
                        : null
                }
                <ComponentLoading loading={this.state.ratesLoading}>
                    <Spacer space='10px' />
                    <Header title='Domestic Discounts' />
                    <Spacer space='10px' />

                    <Label label='USPS First Class'>
                        <Text
                            value={Functions.deepGetFromString(this.state, 'rates,domestic,usps,first_class', '')}
                            onChange={x => this.handleRateUpdate(x.target.value, 'rates,domestic,usps,first_class')}
                        />
                    </Label>
                    <Label label='USPS Priority (0 - 20)lbs'>
                        <Text
                            value={Functions.deepGetFromString(this.state, 'rates,domestic,usps,priority', '')}
                            onChange={x => this.handleRateUpdate(x.target.value, 'rates,domestic,usps,priority')}
                        />
                    </Label>
                    <Label label='USPS Priority Express (0 - 10)lbs'>
                        <Text
                            value={Functions.deepGetFromString(this.state, 'rates,domestic,usps,priority_express', '')}
                            onChange={x => this.handleRateUpdate(x.target.value, 'rates,domestic,usps,priority_express')}
                        />
                    </Label>
                    <Label label='USPS Cubic'>
                        <Text
                            value={Functions.deepGetFromString(this.state, 'rates,domestic,usps,cubic', '')}
                            onChange={x => this.handleRateUpdate(x.target.value, 'rates,domestic,usps,cubic')}
                        />
                    </Label>
                    <Label label='USPS Parcel Select (0 - 20)lbs'>
                        <Text
                            value={Functions.deepGetFromString(this.state, 'rates,domestic,usps,parcel_select', '')}
                            onChange={x => this.handleRateUpdate(x.target.value, 'rates,domestic,usps,parcel_select')}
                        />
                    </Label>

                    <Spacer space='10px' />
                    <Header title='International Discounts' />
                    <Spacer space='10px' />

                    <Label label='USPS First Class'>
                        <Text
                            value={Functions.deepGetFromString(this.state, 'rates,international,usps,first_class', '')}
                            onChange={x => this.handleRateUpdate(x.target.value, 'rates,international,usps,first_class')}
                        />
                    </Label>
                    <Label label='USPS Priority (0 - 20)lbs'>
                        <Text
                            value={Functions.deepGetFromString(this.state, 'rates,international,usps,priority', '')}
                            onChange={x => this.handleRateUpdate(x.target.value, 'rates,international,usps,priority')}
                        />
                    </Label>
                    <Label label='USPS Priority Express (0 - 20)lbs'>
                        <Text
                            value={Functions.deepGetFromString(this.state, 'rates,international,usps,priority_express', '')}
                            onChange={x => this.handleRateUpdate(x.target.value, 'rates,international,usps,priority_express')}
                        />
                    </Label>


                    <Spacer space='10px' />
                    <Header title='Canada Discounts' />
                    <Spacer space='10px' />

                    <Label label='USPS First Class'>
                        <Text
                            value={Functions.deepGetFromString(this.state, 'rates,canada,usps,first_class', '')}
                            onChange={x => this.handleRateUpdate(x.target.value, 'rates,canada,usps,first_class')}
                        />
                    </Label>
                    <Label label='USPS Priority (0 - 20)lbs'>
                        <Text
                            value={Functions.deepGetFromString(this.state, 'rates,canada,usps,priority', '')}
                            onChange={x => this.handleRateUpdate(x.target.value, 'rates,canada,usps,priority')}
                        />
                    </Label>
                    <Label label='USPS Priority Express (0 - 20)lbs'>
                        <Text
                            value={Functions.deepGetFromString(this.state, 'rates,canada,usps,priority_express', '')}
                            onChange={x => this.handleRateUpdate(x.target.value, 'rates,canada,usps,priority_express')}
                        />
                    </Label>
                </ComponentLoading>

                <Spacer space='10px' />

                <Button
                    color={GoaBrand.getPrimaryColor()}
                    stylesbutton={{ marginTop: '0px' }}
                    props={{ onClick: this.handleSave }}
                >
                    Save Client
                </Button>
            </React.Fragment>
        )
    }
}