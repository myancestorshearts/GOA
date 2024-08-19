import React from 'react';
import ReactDOM from 'react-dom'

import Panel from '../panel/index';
import Button from '../../inputs/button';
import Spacer from '../../components/spacer';
import GoaBrand from '../../brand';

//handleClickOutside() for closing on lost focus


export default class Modal extends React.Component {
    constructor(props) {
        super(props);

        this.panelRef = React.createRef();
        this.el = document.createElement('div');
        this.el.style['z-index'] = 1;
        this.el.style.position = 'Fixed';
        this.el.style.inset = '0px';
        this.el.style.background = 'rgba(0, 0, 0, .5)';
        this.el.style.display = 'grid';
        this.el.style.overflow = 'auto';
        this.el.style['align-items'] = 'center';
        this.el.style['justify-content'] = 'center';

        document.body.appendChild(this.el);
        this.handleClickOutside = this.handleClickOutside.bind(this);
    }

    componentDidMount() {
        document.addEventListener('mousedown', this.handleClickOutside);
    }


    componentWillUnmount() {
        document.removeEventListener('mousedown', this.handleClickOutside);
        document.body.removeChild(this.el);
    }

    handleClickOutside(event) {
        if (this.props.onClickOutside && this.panelRef && !this.panelRef.contains(event.target)) {
            this.props.onClickOutside();
        }
    }

    render() {
        let panelStyles = { ...STYLES.panelStyle, ...{ maxHeight: this.props.maxHeight ? this.props.maxHeight : null, minWidth: this.props.width } }
        return ReactDOM.createPortal(
            <React.Fragment>
                <Panel className={this.props.className} style={panelStyles}>
                    <div ref={e => this.panelRef = e}>
                        {this.props.children}
                    </div>
                    {
                        this.props.onClose ?
                            <i className='fa fa-times' onClick={this.props.onClose} style={STYLES.closeIcon} /> : null
                    }
                </Panel>
            </React.Fragment>
            , this.el);
    }

}


const STYLES = {
    panelStyle: {
        display: 'flex',
        flexDirection: 'column',
        fontFamily: 'arial',
        fontSize: '25px',
        padding: '20px',
        margin: '20px',
        position: 'relative',
        maxWidth: 'calc(100%  - 40px)'
    },
    confirmButtonsContainer: {
        display: 'flex'
    },
    closeIcon: {
        cursor: 'pointer',
        position: 'absolute',
        top: '12px',
        right: '20px',
        fontSize: '20px'
    }
}


export const CONFIRM = (message, successCallback, cancelCallback) => {

    var el = document.createElement('div');
    document.body.appendChild(el);
    el.focus();
    let onSuccess = () => {
        if (successCallback) successCallback();
        ReactDOM.unmountComponentAtNode(el);
        document.body.removeChild(el);
    }

    let onCancel = () => {
        if (cancelCallback) cancelCallback(el);
        ReactDOM.unmountComponentAtNode(el);
        document.body.removeChild(el);
    }

    ReactDOM.render(<Modal>
        {message}
        <Spacer space='10px' />
        <div style={STYLES.confirmButtonsContainer}>
            <Button
                props={{
                    autoFocus: true,
                    onClick: onSuccess
                }}
                color={GoaBrand.getPrimaryColor()}
            >
                Confirm
            </Button>
            <Spacer space='20px' />
            <Button
                color={GoaBrand.getPrimaryColor()}
                props={{ onClick: onCancel }}
            >
                Cancel
            </Button>
        </div>
    </Modal>, el);
}