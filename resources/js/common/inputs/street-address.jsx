import React from 'react';
import PlacesAutocomplete, { geocodeByAddress, getLatLng } from 'react-places-autocomplete';
export default class StreetAddress extends React.Component {
	constructor(props) {
		super(props);
		this.handleSelect = this.handleSelect.bind(this);
		this.handleEvent = this.handleEvent.bind(this);
		this.placesRef = React.createRef();
	}

	componentDidMount() {
		document.addEventListener('keydown', (event) => {
			if (event.which === 9) {
				this.handleEvent()
			}
		})
	}

	componentWillUnmount() {
		window.removeEventListener('keydown', this.handleEvent, true);
	}

	handleSelect(address) {
		geocodeByAddress(address)
			.then(results => {
				let response = results[0];
				let responseAutofill = {
					street_1: (this.extractFromAdress(response.address_components, 'street_number') + ' ' + this.extractFromAdress(response.address_components, 'route')),
					city: this.extractFromAdress(response.address_components, 'locality'),
					state: this.extractFromAdress(response.address_components, 'administrative_area_level_1', 'short_name'),
					postal: this.extractFromAdress(response.address_components, 'postal_code')
				}
				this.props.handleAutofill(responseAutofill)
			})
			.catch(error => console.error('Error', error));
	};

	handleEvent() {
		if (this.placesRef.current.state.suggestions.length > 0) {
			this.handleSelect(this.placesRef.current.state.suggestions[0].description);
		}
	}

	extractFromAdress(components, type, valueType) {
		for (var i = 0; i < components.length; i++)
			for (var j = 0; j < components[i].types.length; j++)
				if (components[i].types[j] == type)
					return (
						valueType ? valueType == 'short_name'
							? components[i].short_name
							: components[i].long_name
							: components[i].long_name
					)
		return '';
	}

	render() {
		let containerStyles = { ...STYLES.container }
		let labelStyles = { ...STYLES.label };

		labelStyles = { ...labelStyles, ...(this.props.styleslabel ? this.props.styleslabel : {}) };
		containerStyles = { ...containerStyles, ...(this.props.container ? this.props.ainer : {}) };
		return (
			<div style={containerStyles}>
				{this.props.title && <label style={labelStyles}>{this.props.title}</label>}
				<PlacesAutocomplete
					value={this.props.value}
					onChange={this.props.handleChange}
					onSelect={this.handleSelect}
					onEnter={this.handleEvent}
					ref={this.placesRef}
				>
					{({ getInputProps, suggestions, getSuggestionItemProps, loading }) => (
						<div style={{ display: 'flex', flexDirection: 'column' }}>
							<input style={{ ...STYLES.select, ...(this.props.stylesinput ? this.props.stylesinput : {}) }}
								{...getInputProps({
									placeholder: 'Search Places ...',
									className: 'location-search-input',
								})}
							/>
							<div style={{ ...STYLES.menu, ...suggestions.length > 0 ? { display: 'block' } : {} }}>
								{loading && <div>Loading...</div>}
								{suggestions.map((suggestion) => {
									const style = { backgroundColor: '#ffffff', marginBottom: '10px', cursor: 'pointer', fontSize: '16px', fontWieght: 'bold' };
									return (
										<div
											{...getSuggestionItemProps(suggestion, {
												style
											})}
											key={suggestion.placeId}
										>
											<span>{suggestion.description}</span>
										</div>
									);
								})}
							</div>
						</div>
					)}
				</PlacesAutocomplete>
			</div>
		)
	}
}

const STYLES = {
	container: {
		display: 'flex',
		flexDirection: 'column',
		marginBottom: '10px',
		flex: 1
	},
	select: {
		backgroundColor: 'transparent',
		height: '30px',
		borderRadius: '5px',
		border: '2px solid',
		paddingLeft: '6px',
		paddingRight: '6px',
		fontSize: '16px',
		minHeight: '30px',
		fontWieght: 'bold'
	},
	label: {
		marginBottom: '2px',
		fontSize: '16px',
		fontWeight: 'bold'
	},
	menu: {
		display: 'none',
		boxShadow: '0 2px 9px -3px #9e9e9e',
		fontSize: '13px',
		position: 'absolute',
		zIndex: '1000',
		width: 'max-content',
		minWidth: '160px',
		margin: '2px 0 0',
		marginTop: '38px',
		listStyle: 'none',
		textAlign: 'left',
		backgroundColor: '#fff',
		backgroundClip: 'padding-box',
		boxShadow: '0 6px 12px rgba(0, 0, 0, 0.175)',
		borderRadius: '5px',
		border: '2px solid',
		paddingLeft: '6px',
		paddingRight: '6px',
		paddingTop: '10px'
	}
}