
import React from 'react';
import Header from '../../../../../../common/fields/header';

import Button from '../../../../../../common/inputs/button';
import Spacer from '../../../../../../common/components/spacer';
import toastr from 'toastr';
import Text from '../../../../../../common/inputs/text';
import GoaBrand from '../../../../../../common/brand';

import JSZip from 'jszip';
import FileSaver from 'file-saver';

export default class WooCommerceConnect extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            name: '',
            domain: ''
        };

        this.handleSubmit = this.handleSubmit.bind(this);
    }

    handleSubmit(e) {
        e.preventDefault();

        GoaApi.Integration.connect({
            type: 'WOOCOMMERCE',
            name: this.state.name,
            domain: this.state.domain
        }, success => {

            GoaApi.Integration.download({
                id: success.data.model.id
            }, (successDownload) => {

                const zip = new JSZip();
                zip.file('wp-plugin.php', successDownload.data.contents);
                zip.generateAsync({ type: 'blob' }).then(function (content) {
                    FileSaver.saveAs(content, 'plugin.zip');
                });

                this.props.onConnect();
            })
        }, failure => {
            toastr.error(failure.message)
        })
    }

    render() {

        return (
            <form onSubmit={this.handleSubmit}>
                <Header title='WooCommerce' top={true} />
                <p>This will download a plugin that you must install.</p>
                <Text
                    autoFocus={true}
                    title='Store Name'
                    value={this.state.name}
                    onChange={e => this.setState({ name: e.target.value })}
                />
                <Text
                    autoFocus={true}
                    title='Domain'
                    value={this.state.domain}
                    onChange={e => this.setState({ domain: e.target.value })}
                />
                <Spacer space='10px' />
                <div style={STYLES.confirmButtonsContainer}>
                    <Button
                        props={{ type: 'submit' }}
                        color={GoaBrand.getPrimaryColor()}
                    >
                        Connect
                    </Button>
                </div>
            </form>
        )
    }
}

const STYLES = {
    confirmButtonsContainer: {
        display: 'flex'
    }
}