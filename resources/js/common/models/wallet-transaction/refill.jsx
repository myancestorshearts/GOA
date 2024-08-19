import Header from '../../fields/header';
import Label from '../../fields/label';
import Spacer from '../../components/spacer';

import React from 'react';

import Functions from '../../functions';

const Refill = (props) => {

    let model = props.model;


    return (
        <React.Fragment>
            <Header title='Details' top={true}/>
            <Spacer space='10px'/>
            <Label label='Amount'>{Functions.convertToMoney(model.amount)}</Label>
            {
                Functions.inputToBool(model.pending) ?
                <Label label='Pending From'>{Functions.convertMysqlToDate(model.created_at).formatDate('n/d/y H:m A')}</Label> :
                <Label label='Date'>{Functions.convertMysqlToDate(model.finalized_at).formatDate('n/d/y H:m A')}</Label>
            }
            {
                !Functions.isEmpty(model.processing_fee) ?
                <Label label='Processing Fee'>{Functions.convertToMoney(model.processing_fee)}</Label> : null
            }
            <Label label='Balance'>{Functions.convertToMoney(model.balance)}</Label>
        </React.Fragment>
    )
}

export default Refill;