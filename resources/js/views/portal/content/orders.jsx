import React from 'react';
import PanelSearchTable from '../../../common/portal/panel/search-table';
import GoaState from '../../../common/goa-state';

import FormAddOrderGroup from './forms/add-order-group';
import FormImportSpreadsheets from './forms/import-spreadsheet';

import OrderGroupModel from '../../../common/models/order-group/model';
import OrderGroupMassModel from '../../../common/models/order-group/model-mass';

import ActionPanel from '../../../common/portal/panel/action';

import toastr from 'toastr';
import FlexContainer from '../../../common/components/flex-container';
import FlexExpander from '../../../common/components/flex-expander';
import InputFilter from '../../../common/inputs/filter';


import ApiMass from '../../../common/api-mass';

const FILTERED_PROPERTIES = [
	{
		title: 'Pending',
		value: 0
	},
	{
		title: 'Fulfilled',
		value: 1
	},

	{
		title: 'Failed Integrations',
		value: 'FAILED_INTEGRATION'
	}
]

const INTEGRATION_FAILED_ORDER_PROPERTIES = {
	store: {
		title: 'Store',
		property: 'integration,name',
		type: 'TEXT',
		default: true,
		sortableColumn: 'integrations.name'
	},
	marketplace: {
		title: 'Marketplace',
		property: 'integration,store',
		type: 'TEXT',
		default: true,
		sortableColumn: 'integrations.store'
	},
	reference: {
		title: 'Reference',
		property: 'reference',
		type: 'TEXT',
		default: true,
		sortableColumn: 'reference'
	},
	error: {
		title: 'Error',
		property: 'error_message',
		type: 'TEXT',
		default: true,
		sortableColumn: 'error_message'
	}
}

const ORDER_GROUP_PROPERTIES = {
	name: {
		title: 'Customer Name',
		property: 'name',
		type: 'TEXT',
		default: true,
		sortableColumn: 'name'
	},
	reference: {
		title: 'Reference',
		type: 'METHOD',
		method: (model) => {
			let references = model.orders ? model.orders.map(x => x.reference) : [];
			return references.join(', ');
		},
		default: true,
		sortableColumn: 'orders.reference'
	},
	company: {
		title: 'Company',
		property: 'company',
		type: 'TEXT',
		default: true,
		sortableColumn: 'company'
	},
	email: {
		title: 'Email',
		property: 'email',
		type: 'TEXT',
		default: true,
		sortableColumn: 'email'
	},
	phone: {
		title: 'Phone',
		property: 'phone',
		type: 'TEXT',
		default: true,
		sortableColumn: 'phone'
	},
	address: {
		title: 'Address',
		property: 'address,street_1',
		type: 'TEXT',
		default: true
	},
	weight: {
		title: 'Weight (oz)',
		property: 'weight',
		type: 'TEXT',
		default: true,
		sortableColumn: 'weight'
	},
	quantity: {
		title: 'Quantity',
		property: 'quantity',
		type: 'TEXT',
		default: true,
		sortableColumn: 'quantity'
	},
	placed: {
		title: 'Placed',
		property: 'created_at',
		type: 'DATETIME',
		default: true,
		sortableColumn: 'created_at'
	},
	store: {
		title: 'Store',
		type: 'METHOD',
		method: (model) => {
			return model.orders ? model.orders.map((x, i) => (<div key={i}>{x.store}</div>)) : null;
		},
		default: true,
		sortableColumn: 'orders.store'
	},
	productsnames: {
		title: 'Products',
		type: 'METHOD',
		method: (model) => {
			let products = [];
			model.orders.forEach(order => {
				order.order_products.forEach(x => {
					products.push(x);
				})
			})
			return products.map((x, i) => <div key={i}>{x.quantity}x {x.name}</div>);
		},
		sortableColumn: 'order_products.name'
	},
	productskus: {
		title: 'Skus',
		type: 'METHOD',
		method: (model) => {
			let products = [];
			model.orders.forEach(order => {
				order.order_products.forEach(x => {
					products.push(x);
				})
			})
			return products.map((x, i) => <div key={i}>{x.sku}</div>);
		},
		sortableColumn: 'order_products.sku'
	}
}

export default class Orders extends React.Component {

	constructor(props) {
		super(props)
		this.state = {
			filterValue: 0,
			models: []
		}
		this.handleSelectOrderGroup = this.handleSelectOrderGroup.bind(this);
		this.handleOrderGroupAdd = this.handleOrderGroupAdd.bind(this);
		this.handlePurchase = this.handlePurchase.bind(this);
		this.handleShowUpload = this.handleShowUpload.bind(this);
		this.handleShowAdd = this.handleShowAdd.bind(this);
		this.handleDelete = this.handleDelete.bind(this);
		this.handleSelectModels = this.handleSelectModels.bind(this);
	}

	handleOrderGroupAdd() {
		GoaState.empty('active-modal')
		if (this.tableOrders) this.tableOrders.handleSearch();
	}


	handlePurchase() {
		if (this.tableOrders && this.tableOrders.table) this.tableOrders.table.state.activeModels = [];
		if (this.tableOrders) this.tableOrders.handleSearch();
		if (this.tableOrderHistory) this.tableOrderHistory.handleSearch();
	}


	handleSelectOrderGroup(model) {
		GoaState.set('active-model', {
			model: model, component: <OrderGroupModel
				model={model}
				onPurchase={this.handlePurchase}
			/>
		})
	}

	handleShowUpload() {
		GoaState.set('active-modal', {
			component: <FormImportSpreadsheets
				onOrderGroupAdd={this.handleOrderGroupAdd}
				onCancel={() => GoaState.empty('active-modal')}
			/>
		});
	}

	handleShowAdd() {
		GoaState.set('active-modal', {
			component: <FormAddOrderGroup
				onOrderGroupAdd={this.handleOrderGroupAdd}
				onCancel={() => GoaState.empty('active-modal')}
			/>
		});
	}

	handleDelete() {
		if (this.deleting) return;
		this.deleting = true;
		if (this.state.models.length == 0) {
			toastr.warning('Must select items to delete');
			return;
		}

		let method = this.state.filterValue == 'FAILED_INTEGRATION' ? GoaApi.IntegrationFailedOrder.deactivate : GoaApi.OrderGroup.delete;
		let table = this.state.filterValue == 'FAILED_INTEGRATION' ? this.tableFailedOrdersPending : this.tableOrders;

		let apiMass = new ApiMass(20);

		this.state.models.forEach((x) => {
			apiMass.push(method, {
				id: x.id
			})
		});

		apiMass.finalize(() => {
			table.handleSearch() 
			table.table.handleClearModels();
			this.deleting = false;
			this.handleSelectModels([])
		})

		table.setState({ loading: true });
		apiMass.process();
	}

	handleSelectModels(models) {

		this.setState({
			models: models
		})

		if (models.length == 0) {
			GoaState.set('active-model', { component: undefined });
		}
		else if (models.length == 1) {
			GoaState.set('active-model', {
				model: models[0], component: <OrderGroupModel
					model={models[0]}
					onPurchase={this.handlePurchase}
				/>
			})
		}
		else {
			GoaState.set('active-model', {
				model: undefined, component: <OrderGroupMassModel
					ref={e => this.activeModelMass = e}
					models={models}
					onPurchaseFinalized={this.handlePurchase}
				/>
			})
		}
	}

	render() {
		return (
			<React.Fragment>

				<FlexContainer>
					<InputFilter
						options={FILTERED_PROPERTIES}
						value={this.state.filterValue}
						onChange={x => this.setState({ filterValue: x }, () => {
							if (this.tableFailedOrdersPending) this.tableFailedOrdersPending.handleSearch()
							if (this.tableOrders) this.tableOrders.handleSearch()
						})}

					/>
					<FlexExpander />
					<button style={STYLES.buttons} onClick={this.handleDelete}>
						<i className="fa fa-trash"></i>
					</button>
					<button style={STYLES.buttons} onClick={() => this.props.history.push('/portal/integrations')}>
						<i className="fa fa-link"></i>
					</button>
					<button style={STYLES.buttons} onClick={this.handleShowUpload} >
						<i className="fa fa-upload"></i>
					</button>
					<button style={STYLES.buttonCreate} onClick={this.handleShowAdd}>
						<i className="fa fa-tag" style={STYLES.createInputIcon}></i>
						Create Order
					</button>
				</FlexContainer>
				{
					this.state.filterValue == 'FAILED_INTEGRATION' ?
						<PanelSearchTable
							style={STYLES.searchTableStyles}
							ref={e => this.tableFailedOrdersPending = e}
							properties={INTEGRATION_FAILED_ORDER_PROPERTIES}
							onSelectModel={this.handleSelectOrderGroup}
							tableKey='IntegrationFailedOrders'
							tableTitle='Integration Failed Orders'
							tableIcon='fa fa-tags'
							searchMethod={GoaApi.IntegrationFailedOrder.search}
							hideIfEmpty={true}
							searchArgs={{
								include_classes: 'integration'
							}}
						/> :
						<PanelSearchTable
							style={STYLES.searchTableStyles}
							ref={e => this.tableOrders = e}
							properties={ORDER_GROUP_PROPERTIES}
							onSelectModel={this.handleSelectOrderGroup}
							tableKey='Orders'
							tableTitle='Orders'
							tableIcon='fa fa-tags'
							searchMethod={GoaApi.OrderGroup.search}
							searchArgs={{
								include_classes: 'address,order,orderproduct,label',
								fulfilled: this.state.filterValue
							}}
							showSearch={true}
							onSelectModels={this.handleSelectModels}
						/>
				}
			</React.Fragment>
		)
	}
}

const STYLES = {
	button: {
		marginLeft: '10px'
	},
	buttonsContainer: {
		marginLeft: 'auto',
		display: 'flex'
	},
	searchTableStyles: {
		marginTop: '20px'
	},
	container: {
		display: 'flex',
		flexWrap: 'wrap',
		paddingTop: '10px'
	},
	buttons: {
		width: '60px',
		height: '60px',
		backgroundColor: '#FDB63E',
		border: 'none',
		borderRadius: '20px',
		fontSize: '18px',
		boxShadow: 'rgb(94, 132, 194, 0.06) 2px 2px 5px',
		color: '#04009A',
		cursor: 'pointer'
	},
	buttonCreate: {
		padding: '15px',
		height: '60px',
		backgroundColor: '#FDB63E',
		border: 'none',
		borderRadius: '20px',
		boxShadow: 'rgb(94, 132, 194, 0.06) 2px 2px 5px',
		color: '#04009A',
		fontWeight: 20,
		fontSize: '18px',
		fontFamily: 'Poppins'
	},
	createInputIcon: {
		padding: '5px'
	}

}

