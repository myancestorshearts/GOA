import React from 'react';

export default class FileSelect extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {

        let selectStyles = { ...STYLES.select };
        let labelStyles = { ...STYLES.label };
        let containerStyles = { ...STYLES.container }

        let color = this.props.color ? this.props.color : 'black';
        if (this.props.color) {
            selectStyles.borderColor = color;
            selectStyles.color = color;
            labelStyles.color = color;
        }

        selectStyles = { ...selectStyles, ...(this.props.stylesselect ? this.props.stylesselect : {}) };
        labelStyles = { ...labelStyles, ...(this.props.styleslabel ? this.props.styleslabel : {}) };
        containerStyles = { ...containerStyles, ...(this.props.stylescontainer ? this.props.stylescontainer : {}) };

        return (
            <div style={containerStyles}>
                <label style={selectStyles}>
                    <input accept=".csv" type="file" style={{ display: 'none' }} onChange={this.props.onChange} />
                    <div style={labelStyles}>{this.props.title}</div>
                </label>
                <div>
                    {this.props.value}
                </div>
            </div>
        )
    }
}

const STYLES = {
    container: {
        display: 'flex',
        flexDirection: 'row',
        alignItems: 'center'
    },
    select: {
        backgroundColor: 'transparent',
        height: '30px',
        borderRadius: '5px',
        border: '2px solid',
        paddingLeft: '6px',
        paddingRight: '6px',
        fontSize: '16px',
        minHeight: '36px',
        fontWieght: 'bold',
        width: '150px',
        cursor: 'pointer',
        display: 'flex',
        flexDirection: 'column',
        justifyContent: 'center',
        marginRight: '10px'
    },
    label: {
        marginBottom: '2px',
        fontSize: '16px',
        fontWeight: 'bold',
        textAlign: 'center',
    }
}