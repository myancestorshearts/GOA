import React from 'react';

export default class Select extends React.Component {
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

        let selectableOptions = [...this.props.options]

        if (!this.props.value) {
            selectableOptions.unshift({
                label: this.props.placeholder ? this.props.placeholder : 'Select Option',
                value: undefined
            })
        }

        let options = selectableOptions.map((x, i) => <option key={i} value={x.value}>{x.label}</option>)

        return (
            <div style={containerStyles}>
                {this.props.title && <label style={labelStyles}>{this.props.title}</label>}
                <select
                    style={selectStyles}
                    {...this.props}
                >
                    {options}
                </select>
            </div>
        )
    }
}

const STYLES = {
    container: {
        display: 'flex',
        flexDirection: 'column',
        marginBottom: '10px'
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
        fontWieght: 'bold'
    },
    label: {
        marginBottom: '2px',
        fontSize: '16px',
        fontWeight: 'bold'
    }
}