import React from 'react';
import Header from '../../fields/header';
import Label from '../../fields/label';
import Spacer from '../../components/spacer';
import Functions from '../../functions';
import Button from '../../inputs/button';
import toastr, { success } from 'toastr';
import GoaBrand from '../../brand';
import GoaState from '../../goa-state';
import Boolean from '../../../common/inputs/boolean';
import Text from '../../../common/inputs/text';

import AdminApi from '../../../common/api/admin';
import Storage from '../../storage';

import ComponentLoading from '../../components/loading';

import ApiReferral from '../../api/referral';

import FormDownloadLabels from './form-download-labels';
import FormDownloadTotals from './form-download-totals';

export default class Model extends React.Component {

    constructor(props) {
        super(props)

        this.state = {
        };


        this.handleDownloadLatestLabels = this.handleDownloadLatestLabels.bind(this);
        this.handleDownloadLabels = this.handleDownloadLabels.bind(this);
        this.handleDownloadRevenue = this.handleDownloadRevenue.bind(this);
    }

    handleDownloadLatestLabels() {
        ApiReferral.Referral.labelsLatestExport({
            user_id: this.props.model.referred_user_id
        })
    }

    handleDownloadLabels() {

        GoaState.set('active-modal', {
            component: <FormDownloadLabels
                onClose={() => GoaState.empty('active-modal')}
                model={this.props.model}
            />
        });
    }


    handleDownloadRevenue() {

        GoaState.set('active-modal', {
            component: <FormDownloadTotals
                onClose={() => GoaState.empty('active-modal')}
                model={this.props.model}
            />
        });
    }


    render() {

        return (
            <React.Fragment>

                <Header title='Reports' top={true} />
                <Spacer space='10px' />

                <Button
                    color={GoaBrand.getPrimaryColor()}
                    stylesbutton={{ marginTop: '0px' }}
                    props={{ onClick: this.handleDownloadLatestLabels }}
                >
                    Download Latest Labels
                </Button>
                <Spacer space='10px' />
                <Button
                    color={GoaBrand.getPrimaryColor()}
                    stylesbutton={{ marginTop: '0px' }}
                    props={{ onClick: this.handleDownloadLabels }}
                >
                    Download Labels
                </Button>


                <Spacer space='10px' />
                <Button
                    color={GoaBrand.getPrimaryColor()}
                    stylesbutton={{ marginTop: '0px' }}
                    props={{ onClick: this.handleDownloadRevenue }}
                >
                    Download Revenue
                </Button>
            </React.Fragment >
        )
    }
}