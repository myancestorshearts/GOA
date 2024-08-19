
import React from 'react';
import IntegrationPanel from '../../../../../common/portal/panel/integration';
import FlexContainer from '../../../../../common/components/flex-container';

import GoaState from '../../../../../common/goa-state';


import FormShopifyConnect from './shopify/connect';
import FormEtsyConnect from './etsy/connect';
import FormWixConnect from './wix/connect';
import FormSquareConnect from './square/connect';
import FormWooCommerceConnect from './woocommerce/connect';
import FormShipHeroConnect from './shiphero/connect';


import toastr from 'toastr';

export default class FormAdd extends React.Component {


    constructor(props) {
        super(props);
    }

    render() {
        return (
            <FlexContainer>
                <IntegrationPanel
                    image='/global/assets/images/integrations/shopify.png'
                    label='Shopify'
                    onClick={() => GoaState.set('active-modal', {
                        component: <FormShopifyConnect onCancel={() => {
                            GoaState.empty('active-modal')
                        }} />
                    })}
                />
                <IntegrationPanel
                    image='/global/assets/images/integrations/wix.png'
                    label='Wix'
                    onClick={() => GoaState.set('active-modal', {
                        component: <FormWixConnect onCancel={() => {
                            GoaState.empty('active-modal')
                        }} />
                    })}
                />
                <IntegrationPanel
                    image='/global/assets/images/integrations/etsy.png'
                    label='Etsy'
                    onClick={() => GoaState.set('active-modal', {
                        component: <FormEtsyConnect onCancel={() => {
                            GoaState.empty('active-modal')
                        }} />
                    })}
                />
                <IntegrationPanel
                    image='/global/assets/images/integrations/ebay.png'
                    label='Ebay'
                    onClick={() => toastr.warning('Coming Soon!')}
                />
                <IntegrationPanel
                    image='/global/assets/images/integrations/woo-commerce.png'
                    label='Woo Commerce'
                    onClick={() => GoaState.set('active-modal', {
                        component: <FormWooCommerceConnect onConnect={() => {
                            GoaState.empty('active-modal')
                        }} />
                    })}
                />
                <IntegrationPanel
                    image='/global/assets/images/integrations/square.png'
                    label='Square'
                    onClick={() => GoaState.set('active-modal', {
                        component: <FormSquareConnect onCancel={() => {
                            GoaState.empty('active-modal')
                        }} />
                    })}
                />
                <IntegrationPanel
                    image='/global/assets/images/integrations/weebly.png'
                    label='Weebly'
                    onClick={() => GoaState.set('active-modal', {
                        component: <FormSquareConnect onCancel={() => {
                            GoaState.empty('active-modal')
                        }} />
                    })}
                />
                <IntegrationPanel
                    image='/global/assets/images/integrations/shiphero.png'
                    label='ShipHero'
                    onClick={() => GoaState.set('active-modal', {
                        component: <FormShipHeroConnect onConnect={() => {
                            GoaState.empty('active-modal')
                        }} />
                    })}
                />
            </FlexContainer>
        )
    }
}