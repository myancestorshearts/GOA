import React from 'react';
import GoaBrand from '../brand';
import ProgressBar from './progress-bar';

export default class Loading extends React.Component {

	constructor(props) {
		super(props)
	}



	render() {

		let loadingSearchIconStyles = { ...STYLES.loadingSearchIcon }
		if (this.props.loadingColor) loadingSearchIconStyles.color = this.props.loadingColor;

		if (this.props.loading == true) {
			let loader = <i className="fa fa-spinner fa-spin" style={loadingSearchIconStyles} />;
			if (this.props.percentCompleted !== undefined) loader = <ProgressBar percentCompleted={this.props.percentCompleted} />
			return (
				<div style={{ ...STYLES.loadingSearchDiv, ...this.props.style ? this.props.style : {} }}>
					{loader}
				</div>
			)
		}
		return <React.Fragment>{this.props.children}</React.Fragment>
	}

}

const STYLES = {
	loadingSearchDiv: {
		width: "100%",
		display: "flex",
		minWidth: 1
	},
	loadingSearchIcon: {
		fontSize: "40px",
		color: GoaBrand.getPrimaryColor(),
		top: "50%",
		margin: "auto"
	},
}