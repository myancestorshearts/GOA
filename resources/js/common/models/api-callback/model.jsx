import React from 'react';
import Header from '../../fields/header';
import Label from '../../fields/label';
import Spacer from '../../components/spacer';
import Button from '../../inputs/button';
import toastr from 'toastr';
import GoaBrand from '../../brand';
import GoaState from '../../goa-state';
import ComponentLoading from '../../components/loading';

import FormAdd from './add';

export default class Model extends React.Component {

    constructor(props) {
        super(props)

        this.state = {
            headers: {},
            loading: true
        };

        this.handleDeleteHeader = this.handleDeleteHeader.bind(this);

        this.handleSave = this.handleSave.bind(this);
        this.handleAddHeader = this.handleAddHeader.bind(this);
        this.handleTest = this.handleTest.bind(this);
    }

    componentDidMount() {
        GoaApi.ApiCallback.headers({
            api_callback_id: this.props.model.id
        }, success => {

            let headers = {};
            if (success.data.model.headers) {
                Object.keys(success.data.model.headers).map((key) => {
                    headers[key] = success.data.model.headers[key];
                })
            }

            this.setState({
                headers: headers,
                loading: false
            });
        }, failure => {
            this.setState({
                loading: false,
                headers: {}
            })
        });
    }

    handleDeleteHeader(key) {
        delete this.state.headers[key];
        this.handleSave();
        this.forceUpdate();
    }


    handleAddHeader() {
        GoaState.set('active-modal', {
            component: <FormAdd
                onAdd={(key, value) => {
                    this.state.headers[key] = value;
                    this.handleSave();
                    this.forceUpdate();
                }}
                onClose={() => GoaState.empty('active-modal')}
            />
        });
    }

    handleSave() {
        console.log(this.state);
        GoaApi.ApiCallback.headersSet({
            headers: this.state.headers,
            api_callback_id: this.props.model.id
        }, () => {
            toastr.success('Saved headers');
        }, failure => {
            toastr.error(failure.message);
        })
    }

    handleTest() {
        GoaApi.ApiCallback.test({
            api_callback_id: this.props.model.id
        }, (success) => {
            toastr.success('Callback called and got code: ' + success.data.code + '.  View console to see response');
            console.log('Code: ' + success.data.code)
            console.log('Headers Passed', success.data.headers)
            console.log('Body Passed', success.data.body)
            console.log(success.data.response)
        }, failure => {
            toastr.error(failure.message);
        })
    }

    render() {

        let headers = Object.keys(this.state.headers).map((key, index) => <HeaderItem
            key={index}
            keyValue={key}
            value={this.state.headers[key]}
            onDelete={() => this.handleDeleteHeader(key)}
        />)

        return (
            <React.Fragment>

                <Header title='Headers' top={true} />
                <Spacer space='10px' />

                <ComponentLoading loading={this.state.loading}>

                    {headers}

                    <Button
                        color={GoaBrand.getPrimaryColor()}
                        props={{ onClick: this.handleAddHeader }}
                    >
                        Add Header
                    </Button>
                    <Button
                        color={GoaBrand.getPrimaryColor()}
                        props={{ onClick: this.handleTest }}
                    >
                        Test
                    </Button>

                </ComponentLoading>
            </React.Fragment >
        )
    }
}

const HeaderItem = (props) => {


    return (
        <Label label={props.keyValue} onDelete={props.onDelete}>
            {props.value}
        </Label>
    )
}