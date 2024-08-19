import React from 'react';
import Header from '../../fields/header';
import Label from '../../fields/label';
import Spacer from '../../components/spacer';
import Functions from '../../functions';
import TrackingLink from '../label/tracking-link';


const PostageLabel = (props) => {


    let model = props.model;

    let printImage = undefined;

    let label = Functions.deepGetFromString(model, 'label');

    return (

        <React.Fragment>
            <Header title='Details' top={true}/>
            <Spacer space='10px'/>

            <Label label='Ref'>
                {Functions.deepGetFromString(model, 'label,shipment,reference')}
            </Label>
            <Label label='Tracking'>
                <TrackingLink
                    label={label}
                />
            </Label>

            
            <Label label='From Address'>
                {Functions.deepGetFromString(model, 'label,shipment,from_address,name')} <br/>
                {Functions.deepGetFromString(model, 'label,shipment,from_address,street_1')} {Functions.deepGetFromString(model, 'label,shipment,from_address,street_2')} <br/>
                {Functions.deepGetFromString(model, 'label,shipment,from_address,city')}, {Functions.deepGetFromString(model, 'label,shipment,from_address,state')} <br/>
                {Functions.deepGetFromString(model, 'label,shipment,from_address,postal')}
            </Label>
            <Label label='To Address'>
                {Functions.deepGetFromString(model, 'label,shipment,to_address,name')} <br/>
                {Functions.deepGetFromString(model, 'label,shipment,to_address,street_1')} {Functions.deepGetFromString(model, 'label,shipment,to_address,street_2')} <br/>
                {Functions.deepGetFromString(model, 'label,shipment,to_address,city')}, {Functions.deepGetFromString(model, 'label,shipment,to_address,state')} <br/>
                {Functions.deepGetFromString(model, 'label,shipment,to_address,postal')}
            </Label>

            <Spacer space='10px'/>
            <Header title='Parcel'/>
            <Spacer space='10px'/>
            
            <Label label='Type'>
                {Functions.deepGetFromString(model, 'label,shipment,package,type')}
            </Label>
            <Label label='Weight'>
                {Functions.deepGetFromString(model, 'label,shipment,weight')} oz
            </Label>
            <Label label='Dimensions'>
                <span style={STYLES.dimensionLabel}>L:</span>{Functions.deepGetFromString(model, 'label,shipment,package,length')}″
                <span style={STYLES.dimensionLabel}>,&nbsp;W:</span>{Functions.deepGetFromString(model, 'label,shipment,package,width')}″
                <span style={STYLES.dimensionLabel}>,&nbsp;H:</span>{Functions.deepGetFromString(model, 'label,shipment,package,height')}″
            </Label>


            <Spacer space='10px'/>
            <Header title='Rate'/>
            <Spacer space='10px'/>
            
            <Label label='Service'>
                {Functions.deepGetFromString(model, 'label,rate,service')}
            </Label>
            <Label label='Cost'>
                {Functions.convertToMoney(-1 * model.amount)}
            </Label>


            
            {/*
            <Label label='Estimated Delivery'>
                {Functions.deepGetFromString(model, 'label,rate,service')}
            </Label>*/}



            {/*
                
            <Spacer space='20px'/>
            <div style={STYLES.modalRow}>
                <div style={STYLES.modalTitle}>
                    Label
                </div>
                <div style={STYLES.buttonsContainer}>
                
            </div>
            <div style={STYLES.modalRow}>
                </div>*/}


            {/*<Label label='Image'>
                <img 
                    ref={printImage} 
                    style={STYLES.image} 
                    src={model.label.url}
                />
            </Label>*/}
            
        </React.Fragment>
    )   
}

const STYLES = {
   /*} image: {
        width: '100%'
    },*/
    dimensionLabel: {
        fontWeight: 'Bold',
        fontSize: '15px',
        color: 'rgb(175, 175, 175)'
    }
}


export default PostageLabel;