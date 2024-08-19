

import React from 'react';
export default class StyledComponent extends React.Component {

	constructor(props)
	{
		super(props)
		this.state = {
			hover: false
		}
		this.handleMouseChange = this.handleMouseChange.bind(this);
	}

	handleMouseChange(hoverring) {
		this.setState({hover: hoverring})
		if (this.props.onHoverUpdate) this.props.onHoverUpdate(hoverring);
	}

	render() {
		let active = this.props.active || (this.props.activeMethod ? this.props.activeMethod(this.props) : false)
		let styles = {
			...this.props.style, 
			...active ? this.props.styleActive : {}, 
			...this.state.hover ? this.props.styleHover : {}
		}

		return (
			<this.props.tagName 
				{...this.props.includeRef ? {ref: e => this.element = e} : {}}
				id={this.props.id}
				onMouseEnter={() => this.handleMouseChange(true)}
				onMouseLeave={() => this.handleMouseChange(false)}
				{...this.props.props}
				style={styles}
				className={this.props.className}
			>
				{this.props.children}
			</this.props.tagName>
		)
	}
    
}