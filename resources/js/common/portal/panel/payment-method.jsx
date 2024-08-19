


import React from 'react';


import Panel from '../panel';
import Button from '../../../common/inputs/button';
import Text from '../../../common/inputs/text';
import Boolean from '../../../common/inputs/boolean';
import toastr from 'toastr';
import Loading from '../../../common/components/loading';
import FormPayment from './forms/payment';
import Modal from '../modal';
import { CONFIRM } from '../modal';
import CommonFunctions from '../../../common/functions';
import GoaBrand from '../../../common/brand';
import Spacer from '../../../common/components/spacer';
import { TourMethods } from 'react-shepherd'
import Label from '../../fields/label';

export default class PaymentMethod extends React.Component {
    constructor(props) {
        super(props)

        this.state = {
            addShow: false,
            limit: 0,
            refillTo: 0,
            autofill: false,
            paymentMethod: undefined,
            loading: true
        }

        this.handlePaymentAdd = this.handlePaymentAdd.bind(this);
        this.handleDeletePayment = this.handleDeletePayment.bind(this);
        this.handleAutoFill = this.handleAutoFill.bind(this);
        this.handleSave = this.handleSave.bind(this);
        this.handleShow = this.handleShow.bind(this);
        this.handleCancel = this.handleCancel.bind(this);
    }


    componentDidMount() {
        GoaApi.PaymentMethod.get({ type: this.props.type }, success => {
            if (success.data.payment_method) {
                this.setState({
                    paymentMethod: success.data.payment_method,
                    autofill: CommonFunctions.inputToBool(success.data.payment_method.auto_pay),
                    refillTo: (success.data.payment_method.reload),
                    limit: (success.data.payment_method.threshold),
                })
            }
            this.setState({ loading: false })
        })
    }

    handlePaymentAdd(paymentMethod, tour) {
        this.setState({
            paymentMethod: paymentMethod,
            addShow: false
        }, success => {
            if (tour.isActive()) {
                tour.next()
            }
        })
    }

    handleCancel(tour) {
        this.setState({ addShow: false }, success => {
            if (tour.isActive()) {
                tour.next()
            }
        })
    }


    handleDeletePayment() {
        CONFIRM(`Are you sure you want to delete your payment method?`, () => {
            GoaApi.PaymentMethod.delete({ id: this.state.paymentMethod.id }, () => {
                this.setState({
                    paymentMethod: undefined
                });
            }, failure => {
                toastr.error(failure.message);
            });

        })
    }

    handleAutoFill(value) {
        this.setState({ autofill: value })
    }

    handleSave() {
        GoaApi.PaymentMethod.set({
            id: this.state.paymentMethod.id,
            reload: this.state.refillTo,
            threshold: this.state.limit,
            auto_pay: this.state.autofill
        }, () => {
            toastr.success('Saved Payment Method')
        }, failure => {
            toastr.error(failure.message);
        })
    }

    handleShow(tour) {
        this.setState({ addShow: true }, success => {
            if (tour.isActive()) {
                tour.next();
            }
        })
    }


    render() {

        return (
            <TourMethods>
                {(tourContext) => (
                    <Panel
                        style={STYLES.panel}
                        headerTitle={this.props.headerTitle}
                        headerIcon={this.props.headerIcon}
                        className={this.props.className}
                        onDelete={this.state.paymentMethod ? this.handleDeletePayment : undefined}
                    >
                        <Loading loading={this.state.loading}>
                            {this.state.paymentMethod ?
                                <React.Fragment>
                                    <Label label={this.state.paymentMethod.type == 'cc' ? 'Card Information' : 'Account Information'}>
                                        {this.state.paymentMethod.name}<br />
                                        {(this.state.paymentMethod.type == 'ach' ? 'Account' : 'Card') + ' Ending in ' + this.state.paymentMethod.last_four}<br />
                                        {this.state.paymentMethod.type == 'cc' ?
                                            <React.Fragment>
                                                {'Expires ' + this.state.paymentMethod.expiration_month + '/' + this.state.paymentMethod.expiration_year}
                                                <br />
                                            </React.Fragment> : null
                                        }
                                        {this.state.paymentMethod.zipcode}
                                    </Label>

                                    <Label label='Autofill Account'>
                                        <Spacer space='5px' />
                                        <Boolean title='' model={this.state} property='autofill' onChange={this.handleAutoFill} />

                                        {CommonFunctions.inputToBool(this.state.autofill) ?
                                            <React.Fragment>
                                                <Text
                                                    title={'Automatic Balance Refill Threshold'}
                                                    value={this.state.limit}
                                                    onChange={e => this.setState({ limit: e.target.value })}
                                                    color='rgb(175, 175, 175)'
                                                    stylesinput={STYLES.textInput}
                                                />
                                                <Text
                                                    title={'Refill Transaction Amount'}
                                                    value={this.state.refillTo}
                                                    onChange={e => this.setState({ refillTo: e.target.value })}
                                                    color='rgb(175, 175, 175)'
                                                    stylesinput={STYLES.textInput}
                                                />
                                            </React.Fragment>
                                            : null
                                        }
                                    </Label>

                                    <Button
                                        color={GoaBrand.getPrimaryColor()}
                                        props={{ onClick: this.handleSave }}
                                    >
                                        Save
                                    </Button>
                                </React.Fragment> :
                                <Button
                                    color={GoaBrand.getPrimaryColor()}
                                    stylesbutton={{ marginTop: '0px' }}
                                    props={{ onClick: () => this.handleShow(tourContext) }}
                                >
                                    <i className={'fa fa-plus'} />
                                </Button>
                            }
                        </Loading>
                        {
                            this.state.addShow ?
                                <Modal className={this.props.className + '-form'}>
                                    <FormPayment
                                        method={this.props.type}
                                        onCancel={() => this.handleCancel(tourContext)}
                                        onAdd={(payment) => this.handlePaymentAdd(payment, tourContext)}
                                    />
                                </Modal> : null
                        }
                    </Panel>
                )}
            </TourMethods>
        )
    }
}



const STYLES = {
    panel: {
        width: '300px'
    },
    header: {
        fontSize: '22px',
        color: '#aaa',
        marginTop: '10px',
        minWidth: '200px'
    },
    text: {
        fontSize: '22px',
        marginTop: '5px',
        minWidth: '200px'

    },
    ccDisclaimer: {
        fontSize: '18px',
        marginTop: '5px'
    },
    deleteButtonContainer: {
        width: '150px'
    },
    saveButtonContainer: {
        marginTop: 'auto',
        width: '150px'
    },
    buttonColumn: {
        display: 'flex', flexDirection: 'column', marginLeft: 'auto',
    },
    textInput: {
        fontWeight: 'Bold',
        fontSize: '15px',
        color: '#555'
    }
}