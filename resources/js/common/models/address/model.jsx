import React from 'react';
import Header from '../../fields/header';
import Label from '../../fields/label';
import Spacer from '../../components/spacer';
import Functions from '../../functions';
import Button from '../../inputs/button';
import toastr from 'toastr';
import GoaBrand from '../../brand';
import Boolean from '../../../common/inputs/boolean';

export default class Model extends React.Component {
    
    constructor(props) {
        super(props)

        this.state = {

        }

        this.handleSave = this.handleSave.bind(this);
        this.handleModelUpdate = this.handleModelUpdate.bind(this);
    }

    handleSave() {

        //saving
        GoaApi.Address.set(this.props.model, success => {
            toastr.success('Saved address');
            if (this.props.onSave) this.props.onSave();
        }, failure => {
            toastr.error(failure.message);
        })
    }

    handleModelUpdate(value, property) {
        this.props.model[property] = value;
        this.forceUpdate();
    }

    render() {
        let model = this.props.model;

        return (
            <React.Fragment>
                <Header title='Details' top={true}/>
                <Spacer space='10px'/>

                <Label label='Name'>
                    {Functions.deepGetFromString(model, 'name', '')}
                </Label>

                <Label label='Company'>
                    {Functions.deepGetFromString(model, 'company', '')}
                </Label>
                
                <Label label='Phone'>
                    {Functions.deepGetFromString(model, 'phone', '')}
                </Label>
                
                <Label label='Email'>
                    {Functions.deepGetFromString(model, 'email', '')}
                </Label>

                <Label label='Address'>
                    {Functions.formatAddress(model)}
                </Label>


                
                <Label label='Default Address'>
                    <Spacer space='5px'/>
                    <Boolean title='' model={this.props.model} property='default' onChange={() => this.forceUpdate()}/>
                </Label>


                <Spacer space='10px'/>

                <Button 
                    color={GoaBrand.getPrimaryColor()}
                    stylesbutton={{marginTop: '0px'}}
                    props={{onClick: this.handleSave}}
                >
                    Save Address
                </Button> 
            </React.Fragment>
        )
    }
}