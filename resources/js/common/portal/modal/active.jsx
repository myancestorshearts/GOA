import React from 'react';

import GoaState from '../../goa-state';

import Modal from './index';

export default class ActiveModal extends React.Component {

    constructor(props) {
        super(props)
        this.state = {
            component: undefined
        }

        this.handleClose = this.handleClose.bind(this);
    }

    componentDidMount() {
        GoaState.subscribe(this.props.stateKey, (state) => {
            this.setState({ component: state.component, className: state.className })
        })
    }

    handleClose() {
        GoaState.set(this.props.stateKey, { component: undefined, className: undefined });
    }

    render() {
        if (!this.state.component) return null
        return (
            <Modal
                onClose={this.handleClose}
                className={this.state.className}
                width='300px'
            >
                {this.state.component}
            </Modal >
        )
    }
}