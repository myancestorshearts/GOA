import React from 'react';
import Header from '../../fields/header';
import Label from '../../fields/label';
import Spacer from '../../components/spacer';
import Functions from '../../functions';

export default class Model extends React.Component {

    constructor(props) {
        super(props)

        this.state = {
        };

    }


    render() {
        let model = this.props.model;

        let rawData = JSON.parse(model.raw);
        console.log(rawData);

        return (
            <React.Fragment>
                <Header title='Corrected Meta' top={true} />
                <Spacer space='10px' />

                <Label label='Amount'>
                    {Functions.convertToMoney(Functions.deepGetFromString(model, 'amount', ''))}
                </Label>

                <Label label='Service'>
                    {Functions.deepGetFromString(model, 'service', '')}
                </Label>

                <Label label='Weight'>
                    {Functions.deepGetFromString(model, 'weight', '')} oz
                </Label>

                <Label label='Width'>
                    {Functions.deepGetFromString(model, 'width', '')}"
                </Label>

                <Label label='Length'>
                    {Functions.deepGetFromString(model, 'length', '')}"
                </Label>

                <Label label='Height'>
                    {Functions.deepGetFromString(model, 'height', '')}"
                </Label>

                <Header title='Entered Meta' />
                <Spacer space='10px' />

                <Label label='Service'>
                    {Functions.deepGetFromString(model, 'label,service', '')}
                </Label>

                <Label label='Weight'>
                    {Functions.deepGetFromString(model, 'label,weight', '')} oz
                </Label>

                <Label label='Width'>
                    {Functions.deepGetFromString(model, 'label,shipment,package,width', '')}"
                </Label>

                <Label label='Length'>
                    {Functions.deepGetFromString(model, 'label,shipment,package,length', '')}"
                </Label>

                <Label label='Height'>
                    {Functions.deepGetFromString(model, 'label,shipment,package,height', '')}"
                </Label>

                <Header title='Carrier Raw Meta' />
                <Spacer space='10px' />
                {
                    Object.keys(rawData).map((key) => {
                        return (
                            <Label label={key}>
                                {rawData[key]}
                            </Label>
                        )
                    })
                }

            </React.Fragment>
        )
    }
}