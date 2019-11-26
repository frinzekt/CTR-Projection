//Initializes Global ( the whole file) Variable Spreadsheet
let spreadsheetData = [];

/*
	Inputs:
		- spreadsheetData (array - global var)
		- row (array to be added in spreadsheet)
	Action: Adds to row the the spreadsheet Data
	Dependencies:
		- 
	Output:
		- spreadsheetData
	Return: None
*/
function addSpreadSheetRow(row) {
	spreadsheetData.push(row);
}

/*
	Inputs:
		- spreadsheetData (array - global var)
	Action: Adds a blank row to the spreadsheet data, useful for spacing
	Dependencies:
		- 
	Output:
		- spreadsheetData
	Return: None
*/
function addBlankRow() {
	row = [];
	spreadsheetData.push(row);
}

/*
	Inputs:
		- spreadsheetData (array - global var)
		- title (string)
		- data (array)
	Action: Adds a first level row starting from the most left side and adds data
	Dependencies:
		- addSpreadSheetRow()
	Output:
		- spreadsheetData
	Return: None
*/
function addJobRow(title, data) {
	addSpreadSheetRow([title, "", "", ...data]);
}

/*
	Inputs:
		- spreadsheetData (array - global var)
		- title (string)
		- data (array)
	Action: Adds a second level row starting from the 2nd most left side
	Dependencies:
		- addSpreadSheetRow()
	Output:
		- spreadsheetData
	Return: None
*/
function addSecondLevelRow(name, data) {
	addSpreadSheetRow(["", name, "", ...data]);
}

/*
	Inputs:
		- spreadsheetData (array - global var)
		- subjobName
		- taskName
	Action: Adds a second level and third level row starting from the 2nd most left side
	Dependencies:
		- addSpreadSheetRow()
	Output:
		- spreadsheetData
	Return: None
*/

function addSubjobTaskRow(subjob, task, data) {
	addSpreadSheetRow(["", subjob, task, ...data]);
}

/*
	Inputs:
		- From HOT Renderer
	Action: A callback function that is passed to another function (HandsonTable Renderer) for custom css renderer
	Dependencies:
		- 
	Output:
		- To HOT Renderer
	Return: 
*/
function titleColRenderer(instance, td, row, col, prop, value, cellProperties) {
	Handsontable.renderers.TextRenderer.apply(this, arguments);
	td.style.fontWeight = "bold";
	td.style.color = "green";
	//td.style.background = "#CEC";
}

/*
	Inputs:
		- API Data
	Action: Display the Spreadsheet form of API Datato the selector #spreadsheet with download button on #export-file
	Dependencies:
		- addSpreadSheetRow()
		- addJobRow
		- addSecondLevelRow
		- addBlankRow
		- HandonTable CDN attached
	Output:
		- selector #spreadsheet
	Return: 
*/
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
	[id, ...invoicedAmount] = data.invoicedAmountGroupByJob;
	addJobRow("Invoiced Amount", invoicedAmount);

	//Invoice Details
	addSecondLevelRow("Invoice ID", []);
	invoicedAmountGroupById.forEach(invoiceOrder => {
		addSecondLevelRow(invoiceOrder[0], invoiceOrder.slice(1, rowLength + 1));
	});
	addBlankRow();

	//Reconciled Amount
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
	if (payrollGroupByEId && payrollGroupByEId.length) {
		// not empty
		addSecondLevelRow("Employee", []);
		payrollGroupByEId.forEach(employee => {
			addSecondLevelRow(employee[0], employee.slice(1, rowLength + 1));
		});
		addBlankRow();
	}

	//InvoiceIn / Contractors
	if (invoicedInGroupById && invoicedInGroupById.length) {
		// not empty
		addSecondLevelRow("Invoice In", []);
		invoicedInGroupById.forEach(invoice => {
			addSecondLevelRow(invoice[0], invoice.slice(1, rowLength + 1));
		});
		addBlankRow();
	}

	//Expenses
	if (expensesGroupById && expensesGroupById.length) {
		// not empty
		addSecondLevelRow("Expenses", []);
		expensesGroupById.forEach(expense => {
			addSecondLevelRow(expense[0], expense.slice(1, rowLength + 1));
		});
		addBlankRow();
	}

	//Displays Spreadsheet
	var container = document.getElementById("spreadsheet");
	console.log(spreadsheetData);
	var hot = new Handsontable(container, {
		data: spreadsheetData,
		width: "100%",
		rowHeights: 23,

		rowHeaders: true,
		colHeaders: true,
		//filters: true,
		readOnly: true, // make table cells read-only
		stretchH: "all",
		//dropdownMenu: true,
		licenseKey: "non-commercial-and-evaluation",

		fixedColumnsLeft: 2,
		fixedRowsTop: 1,
		viewportColumnRenderingOffset: 20,
		viewportRowRenderingOffset: 20,

		cells: function(row, col) {
			var cellProperties = {};
			var data = this.instance.getData();
			if (col === 0 || row === 0) {
				cellProperties.renderer = titleColRenderer;
			}
			return cellProperties;
		}
	});

	// Displays the Download Button
	var downloadBTN = document.getElementById("export-file");
	var exportPlugin = hot.getPlugin("exportFile");
	var filename = "CTR-".concat(id, " [YYYY]-[MM]-[DD]");
	downloadBTN.addEventListener("click", function() {
		exportPlugin.downloadFile("csv", {
			bom: false,
			columnDelimiter: ",",
			columnHeaders: false,
			rowHeaders: false,
			exportHiddenColumns: false,
			exportHiddenRows: false,
			fileExtension: "csv",
			filename: filename,
			mimeType: "text/csv",
			rowDelimiter: "\r\n"
		});
	});
}
