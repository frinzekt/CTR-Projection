let spreadsheetData = [];

function addSpreadSheetRow(row) {
	spreadsheetData.push(row);
}
function addBlankRow() {
	row = [];
	spreadsheetData.push(row);
}
function addJobRow(title, data) {
	addSpreadSheetRow([title, "", "", ...data]);
}
function addSecondLevelRow(name, data) {
	addSpreadSheetRow(["", name, "", ...data]);
}

function getDays(date) {
	return Math.floor(date / (1000 * 60 * 60 * 24));
}

function titleColRenderer(instance, td, row, col, prop, value, cellProperties) {
	Handsontable.renderers.TextRenderer.apply(this, arguments);
	td.style.fontWeight = "bold";
	td.style.color = "green";
	//td.style.background = "#CEC";
}

function negativeValueRenderer(
	instance,
	td,
	row,
	col,
	prop,
	value,
	cellProperties
) {
	Handsontable.renderers.TextRenderer.apply(this, arguments);

	// if row contains negative number
	if (parseInt(value, 10) < 0) {
		// add class "negative"
		td.className = "make-me-red";
	}

	if (!value || value === "") {
		td.style.background = "#EEE";
	} else {
		if (value === "Nissan") {
			td.style.fontStyle = "italic";
		}
		td.style.background = "";
	}
}
// Initialize Title Layout
/*
value = "2019-11-07";
endvalue = "2019-11-20";
startDate = new Date(value + "T00:00");
endDate = new Date(endvalue + "T00:00");

noLoops = Math.ceil(getDays(endDate - startDate) / 7); // PER WEEK
dates = [startDate];
dateFormatted = [startDate.toLocaleDateString("en-AU")];

console.log(dates);
for (let i = 1; i < noLoops + 1; i++) {
	dates[i] = new Date(startDate);
	dates[i].setDate(dates[i].getDate() + 7 * i);
	dateFormatted[i] = dates[i].toLocaleDateString("en-AU");
}
console.log("after");
console.log(dates);

addSpreadSheetRow(["", "", "", ...dateFormatted]);*/

function mainSpread(data) {
	let {
		currentBudgetJob,
		dateInterval,
		purchaseOrderDetails,
		invoicedAmountGroupById,
		reconciledAmountGroupById,
		payrollGroupByEId,
		invoicedInGroupById,
		expensesGroupById
	} = data;
	rowLength = dateInterval.length;

	//Current budget
	addSpreadSheetRow(["", "", "", ...dateInterval]);
	addJobRow("Current Budget", currentBudgetJob);

	//Purchase Order Details
	addSecondLevelRow("Purchase Order ID", []);
	purchaseOrderDetails.forEach(PO => {
		addSecondLevelRow(PO[0], PO.slice(1, rowLength + 1));
	});

	addBlankRow();

	invoicedAmount = unpackInvoicedAmount(data);
	addJobRow("Invoiced Amount", invoicedAmount);

	//Invoice Details
	addSecondLevelRow("Invoice ID", []);
	invoicedAmountGroupById.forEach(invoiceOrder => {
		addSecondLevelRow(invoiceOrder[0], invoiceOrder.slice(1, rowLength + 1));
	});

	addBlankRow();
	reconciledAmount = unpackReconciledAmount(data);
	addJobRow("Reconciled Amount", reconciledAmount);

	//Invoice Details
	addSecondLevelRow("Invoice ID", []);
	reconciledAmountGroupById.forEach(invoiceOrder => {
		addSecondLevelRow(invoiceOrder[0], invoiceOrder.slice(1, rowLength + 1));
	});

	addBlankRow();
	addJobRow("Amount Spent", []);

	//Employee Payroll
	addSecondLevelRow("Employee", []);
	payrollGroupByEId.forEach(employee => {
		addSecondLevelRow(employee[0], employee.slice(1, rowLength + 1));
	});
	addBlankRow();

	//InvoiceIn / Contractors
	addSecondLevelRow("Invoice In", []);
	invoicedInGroupById.forEach(invoice => {
		addSecondLevelRow(invoice[0], invoice.slice(1, rowLength + 1));
	});
	addBlankRow();

	//Expenses
	addSecondLevelRow("Expenses", []);
	expensesGroupById.forEach(expense => {
		addSecondLevelRow(expense[0], expense.slice(1, rowLength + 1));
	});
	addBlankRow();

	var container = document.getElementById("spreadsheet");
	console.log(spreadsheetData);
	var hot = new Handsontable(container, {
		data: spreadsheetData,
		rowHeaders: true,
		colHeaders: true,
		filters: true,
		readOnly: true, // make table cells read-only

		dropdownMenu: true,
		licenseKey: "non-commercial-and-evaluation",

		cells: function(row, col) {
			var cellProperties = {};
			var data = this.instance.getData();
			if (col === 0 || row === 0) {
				cellProperties.renderer = titleColRenderer;
			}
			return cellProperties;
		}
	});
}
