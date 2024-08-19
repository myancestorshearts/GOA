import React from 'react';
import CommonFunctions from '../functions';
export default class InputInteger extends React.Component {
	constructor(props)
	{
		super(props);
		this.updateInteger = this.updateInteger.bind(this);
	}

	updateInteger(value)
	{
		if (value == undefined) value = this.props.min ? this.props.min : 0;
		if (!CommonFunctions.isEmpty(this.props.min) && value < this.props.min) value = this.props.min;
		if (!CommonFunctions.isEmpty(this.props.max) && value > this.props.max) value = this.props.max;
		if (this.props.onChange) this.props.onChange(value);
	}

	render() {

		let inputStyles = {...STYLES.input};
        let labelStyles = {...STYLES.label};
        let containerStyles = {...STYLES.container}

		inputStyles = {...inputStyles, ...(this.props.stylesinput ? this.props.stylesinput : {})};
        labelStyles = {...labelStyles, ...(this.props.styleslabel ? this.props.styleslabel : {})};
        containerStyles = {...containerStyles, ...(this.props.container ? this.props.ainer : {})};


		let displayLabel = (this.props.label) ? this.props.label : 'Quantity';
		return (
			<div key={'input-' + CommonFunctions.getRandomId()} style={containerStyles}>
				{
					CommonFunctions.inputToBool(this.props.showLabel) ? 
					<label style={labelStyles}>{displayLabel}</label>
					 : null
				}
				<div style={{display: 'flex'}}>
					<span 
						style={STYLES.decrement} 
						onClick={() => this.updateInteger(Number(this.props.value) - 1)}
					>
						–
					</span>
					<input
						style={inputStyles}
						name="quantity" 
						type="number"
						inputMode="numeric"
						value={this.props.value} 
						onChange={e => this.setState({value: e.target.value})} 
						onBlur={(e) => this.updateInteger(e.target.value)}/>
					<span	
						style={STYLES.increment} 
						className="incrementer" 
						onClick={() => this.updateInteger(Number(this.props.value) + 1)}
					>
						+
					</span>
				</div>
			</div>
		)
	}
}

const STYLES = {
    container: {
        display: 'flex',
        flexDirection: 'column',
        marginBottom: '10px',
    },
    input: {
        backgroundColor: 'transparent',
        height: '30px',
        border: '2px solid',
        paddingLeft: '10px',
        paddingRight: '10px',
        fontSize: '16px',
        minHeight: '36px',
        marginTop: '5px',
        width: '100px'
    },
    label: {
    	flex: 1,
        marginBottom: '2px'
    },
    increment: {
		border: '2px solid',
	    borderLeft: 'none',
	    borderRadius: '0 5px 5px 0',
	    fontWeight: '700',
	    display: 'inline-block',
	    width: '35px',
	    minHeight: '30px',
	    cursor: 'pointer',
	    textAlign: 'center',
	    display: 'flex',
	    alignItems: 'center',
	    justifyContent: 'center',
	},
	decrement: {
		border: '2px solid',
	    borderRight: 'none',
	    borderRadius: '5px 0 0 5px',
	    fontWeight: '700',
	    display: 'inline-block',
	    width: '35px',
	    minHeight: '30px',
	    cursor: 'pointer',
	    textAlign: 'center',
	    display: 'flex',
	    alignItems: 'center',
	    justifyContent: 'center',
	}
}