
import React from 'react';
import toastr, { success } from 'toastr';
import GoaBrand from '../../../../common/brand';
import Header from '../../../../common/fields/header';
import ComponentSpacer from '../../../../common/components/spacer';
import ComponentProgressBar from '../../../../common/components/progress-bar';
import InputButton from '../../../../common/inputs/button';
import InputBoolean from '../../../../common/inputs/boolean';
import InputFile from '../../../../common/inputs/file';
import InputSelect from '../../../../common/inputs/select';
import Functions from '../../../../common/functions';
import Papa from "papaparse";
import ApiMass from '../../../../common/api-mass';

const PROPERTIES = {
	reference: 'Order Reference',
	name: 'Customer Full Name',
	first_name: 'Customer First Name',
	last_name: 'Customer Last Name',
	company: 'Company',
	phone: 'Phone',
	email: 'Email',
	street_1: 'Street 1',
	street_2: 'Street 2',
	city: 'City',
	postal: 'Postal',
	state: 'State',
	country: 'Country',
	product_name: 'Product Name',
	quantity: 'Quantity',
	sku: 'SKU',
	weight: 'Weight'
};

const MAP_ORDER = {
	reference: (model, value) => model.orders[0].reference = value,
	name: (model, value) => {
		model.name = value;
		model.address.name = value;
	},
	first_name: (model, value) => {
		model.name = value;
		model.address.name = value;
	},
	last_name: (model, value) => {
		model.name += ' ' + value;
		model.address.name += ' ' + value;
	},
	company: (model, value) => model.company = value,
	phone: (model, value) => model.phone = value,
	email: (model, value) => model.email = value,
	street_1: (model, value) => model.address.street_1 = value,
	street_2: (model, value) => model.address.street_2 = value,
	city: (model, value) => model.address.city = value,
	postal: (model, value) => model.address.postal = value,
	state: (model, value) => model.address.state = value,
	country: (model, value) => model.address.country = value
}

const MAP_ORDER_PRODUCT = {
	product_name: (model, value) => model.name = value,
	quantity: (model, value) => model.quantity = value,
	sku: (model, value) => model.sku = value,
	weight: (model, value) => model.weight = value,
}

const PARSERS = {
	csv: (file, callback) => {
		Papa.parse(file, {
			header: true,
			complete: (results) => {

				let importModels = [];
				results.data.forEach(x => {
					let foundData = false;
					Object.keys(x).forEach(key => {
						if (x[key] && !Functions.isEmpty(x[key].trim())) {
							foundData = true;
						}
					})
					if (foundData) importModels.push(x)
				})

				callback({
					importProperties: results.meta.fields,
					importModels: importModels
				})
			},
			error: () => {
				toastr.error('Error loading import file - incorrect format or corrupt')
			}
		});
	}
}

export default class ImportOrders extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			ignoreDuplicates: true,
			importProperties: undefined,
			importModels: undefined,
			importPropertyMap: {},
			uploading: false,
			percentCompleted: 0,
			failedOrderGroups: []
		};

		this.handleSubmit = this.handleSubmit.bind(this);
		this.handleFileSubmit = this.handleFileSubmit.bind(this);
		this.handleSetImportPropertyMap = this.handleSetImportPropertyMap.bind(this);
	}

	handleSubmit(e) {

		if (e) e.preventDefault();
		if (this.state.uploading) return;

		// validate all inputs are set
		let validated = true;
		Object.keys(PROPERTIES).forEach(key => {
			if (!this.state.importPropertyMap[key]) validated = false;
		})
		if (!validated) {
			toastr.error('Must select all fields before you can export');
			return;
		}

		// group by reference
		let importModelsByOrder = {}
		this.state.importModels.forEach((x, i) => {
			if (this.state.importPropertyMap['reference'] != 'NA') {
				let orderReference = x[this.state.importPropertyMap['reference']];
				if (!importModelsByOrder[orderReference]) importModelsByOrder[orderReference] = [];
				importModelsByOrder[orderReference].push(x)
			}
			else importModelsByOrder[i] = [x];
		})


		// map import models to database fields
		let orderGroups = Object.keys(importModelsByOrder).map(key => {
			let importOrderProducts = importModelsByOrder[key];

			let orderGroup = {
				orders: [{
					ignore_duplicates: this.state.ignoreDuplicates,
					order_products: []
				}],
				address: {
					country: 'US',
				},
				name: ''
			};

			importOrderProducts.forEach(x => {
				Object.keys(MAP_ORDER).forEach(orderKey => {
					if (this.state.importPropertyMap[orderKey] == 'NA') return;
					MAP_ORDER[orderKey](orderGroup, x[this.state.importPropertyMap[orderKey]]);
				})

				let orderProduct = {
					name: 'Not Specified',
					sku: '',
					quantity: 1,
					weight: 1
				}
				Object.keys(MAP_ORDER_PRODUCT).forEach(orderProductKey => {
					if (this.state.importPropertyMap[orderProductKey] == 'NA') return;
					MAP_ORDER_PRODUCT[orderProductKey](orderProduct, x[this.state.importPropertyMap[orderProductKey]]);
				})
				orderGroup.orders[0].order_products.push(orderProduct);
			})

			return orderGroup;
		})

		this.setState({
			uploading: true,
			precentCompleted: 0,
			failedOrderGroups: []
		}, () => {
			let completedOrders = 0;
			let successOrders = 0;

			let updateProgressBar = () => {
				completedOrders++;
				let percent = 100 * completedOrders / orderGroups.length
				this.setState({
					percentCompleted: percent
				}, () => {
					if (completedOrders == orderGroups.length) {
						this.props.onOrderGroupAdd();
						toastr.success('Successfully imported ' + successOrders + ' orders')
					}
				})
			}

			let apiMass = new ApiMass(4);

			orderGroups.forEach(x => {

				apiMass.push(GoaApi.OrderGroup.add, x, success => {
					successOrders++;
					updateProgressBar();
				}, failure => {
					updateProgressBar();
					this.state.failedOrderGroups.push({
						reference: x.orders[0].reference,
						message: failure.message
					});
					toastr.error('Reference: "' + x.orders[0].reference + '" - failed to import: ' + failure.message);
				})
			})

			apiMass.process();

		})
	}

	handleFileSubmit(e) {
		if (e.target.files && e.target.files[0]) {
			let regex = /(?:\.([^.]+))?$/;
			let file = e.target.files[0];
			let fileExtension = regex.exec(e.target.value)[1];

			// check parsers to see if extension exists then run parser
			if (!PARSERS[fileExtension]) toastr.error('Unsupported file type. Only CVS is allowed');
			PARSERS[fileExtension](file, result => this.setState({ ...result, importPropertyMap: {} }));
		}
	}

	handleSetImportPropertyMap(e, key) {
		this.state.importPropertyMap[key] = e.target.value;
		this.forceUpdate();
	}

	render() {

		let propertiesMapSelects = [];

		if (this.state.importProperties) {
			let propertyMapOptions = this.state.importProperties.map(x => ({
				label: x,
				value: x
			}))

			propertyMapOptions.unshift({
				label: '-- No Matching Property --',
				value: 'NA'
			})

			propertiesMapSelects = Object.keys(PROPERTIES).map(key => <InputSelect
				key={key}
				stylesselect={STYLES.selectInput}
				styleslabel={STYLES.label}
				title={PROPERTIES[key]}
				value={this.state.importPropertyMap[key]}
				options={propertyMapOptions}
				onChange={e => this.handleSetImportPropertyMap(e, key)}
				placeholder={'Select Matching Property'}
			/>)
		}

		return (
			<form onSubmit={this.handleSubmit}>
				<Header title='Import Spreadsheet' top={true} />
				<ComponentSpacer />
				<InputBoolean
					title='Ignore Duplicates'
					model={this.state}
					property='ignoreDuplicates'
					styleslabel={STYLES.label}
				/>
				<InputFile
					title='Choose File'
					onChange={this.handleFileSubmit}
				/>
				<ComponentSpacer />
				{propertiesMapSelects}

				{this.state.uploading ? <ComponentProgressBar percentCompleted={this.state.percentCompleted} /> : null}

				<InputButton
					props={{ type: 'submit' }}
					color={GoaBrand.getPrimaryColor()}
					stylesbutton={STYLES.button}
					stylesbuttonhover={STYLES.buttonHover}
				>
					Import
				</InputButton>
			</form>
		)
	}
}

const STYLES = {
	selectInput: {
		fontWeight: '600',
		fontFamily: 'poppins',
		fontSize: '18px',
		color: '#273240',
		borderRadius: '20px',
		height: '50px',
		borderColor: '#96A0AF'
	},
	textInput: {
		fontWeight: '600',
		fontFamily: 'poppins',
		fontSize: '18px',
		color: '#273240',
		borderRadius: '20px',
		height: '44px',
		borderColor: '#96A0AF'
	},
	label: {
		fontFamily: 'poppins',
		fontWeight: '600',
		fontSize: '12px',
		color: '#96A0AF'
	},
	button: {
		height: '50px',
		borderRadius: '20px',
		color: 'white',
		backgroundColor: GoaBrand.getPrimaryColor()
	},
	buttonHover: {
		backgroundColor: GoaBrand.getPrimaryHoverColor()
	}
}
