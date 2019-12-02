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
		- spreadsheetData (array - global var)
		- taskname or taskId (string)
		- data (array)
	Action: Adds third level row starting from the 2nd most left side
	Dependencies:
		- addSpreadSheetRow()
	Output:
		- spreadsheetData
	Return: None
*/

function addTaskRow(task, data) {
	addSpreadSheetRow(["", "", task, ...data]);
}

/*
	Inputs:
		- spreadsheetData (array - global var)
		- subCategory Name - (string)
		- dataSet - (array)
	Action: Creates a subcategory along with its dataset
	Dependencies:
		- addSecondLevelRow()
		- addBlankRow()
	Output:
		- spreadsheetData
	Return: None
*/

const addSubCategory = (subCategoryName, dataSet) => {
	if (dataSet && dataSet.length) {
		// not empty
		addSecondLevelRow(subCategoryName, []);
		dataSet.forEach(data => {
			addSecondLevelRow(data[0], data.slice(1, data.length));
		});
		addBlankRow();
	}
};

/*
	Inputs:
		- spreadsheetData (array - global var)
		- dataSetGroupBySubjob (array)
		- dataSetGroupBySubjobTask - (array)
	Action: Creates a display of dataset with subjob and tasks
	Dependencies:
		- addSubjobTaskRow()
		- addSecondLevelRow()
		- addTaskRow()
		- addBlankRow()
	Output:
		- spreadsheetData
	Return: None
*/

const addSubjobTask = (dataSetGroupBySubjob, dataSetGroupBySubjobTask) => {
	addBlankRow();
	addSubjobTaskRow("Subjob", "Task", []);
	dataSetGroupBySubjob.forEach(([subjobId, ...dataSetOnDateBySubjob]) => {
		addSecondLevelRow(subjobId, dataSetOnDateBySubjob);
		dataSetGroupBySubjobTask.forEach(([targetSubjobId, taskId, ...dataSetOnDateByTask]) => {
			if (targetSubjobId === subjobId) {
				addTaskRow(taskId, dataSetOnDateByTask);
			}
		});
	});
	addBlankRow();
};

/*
	Inputs:
		- From HOT Renderer style properties
	Action: A callback function that is passed to another function (HandsonTable Renderer) for custom css renderer
	Output:
		- To HOT Renderer
*/
function firstColRenderer(instance, td, row, col, prop, value, cellProperties) {
	Handsontable.renderers.TextRenderer.apply(this, arguments);
	td.style.fontWeight = "bold";
	td.style.color = "green";
}
function secondColRenderer(instance, td, row, col, prop, value, cellProperties) {
	Handsontable.renderers.TextRenderer.apply(this, arguments);
	td.style.fontWeight = "bold";
	td.style.color = "blue";
}
function thirdColRenderer(instance, td, row, col, prop, value, cellProperties) {
	Handsontable.renderers.TextRenderer.apply(this, arguments);
	td.style.fontWeight = "bold";
	td.style.color = "orange";
}

/*
	Inputs:
		- API Data
	Action:  Inserts different API data into the spreadsheet
	Dependencies:
		- HANDONTABLE
		- addSpreadSheetRow()
		- addJobRow
		- addSecondLevelRow
		- addBlankRow
	Output:
		- spreadSheetData
*/
function insertItemsIntoSpreadsheet(data) {
	//JS ES6 Destructuring
	//UNPACKING OF SPECIFIC DATA, SUBJOB, AND TASK DATA
	let {
		currentBudgetJob,
		originalSchedule,
		dateInterval,
		purchaseOrderDetails,
		invoicedAmountGroupById,
		reconciledAmountGroupById,
		payrollGroupByEId,
		invoicedInGroupById,
		expensesGroupById,

		invoicedAmountGroupBySubjob,
		invoicedAmountGroupByTask,

		reconciledAmountGroupBySubjob,
		reconciledAmountGroupByTask,

		payrollGroupBySubjob,
		payrollGroupByTask
	} = data;
	rowLength = dateInterval.length;

	//GROUP BY JOB DATA UNPACKING
	let invoicedAmount = unpackInvoicedAmount(data);
	let reconciledAmount = unpackReconciledAmount(data);
	let payroll = unpackPayroll(data);
	let invoicedIn = unpackInvoicedIn(data);
	let expenses = unpackExpenses(data);
	let value = unpackValue(data);

	//Current budget
	addJobRow("Current Budget", currentBudgetJob);
	//Purchase Order Details
	addSecondLevelRow("Purchase Order ID", []);
	purchaseOrderDetails.forEach(PO => {
		addSecondLevelRow(PO[0], PO.slice(1, rowLength + 1));
	});

	addBlankRow();

	addJobRow("Original Schedule", originalSchedule);

	invoicedAmount = unpackInvoicedAmount(data);
	[id, ...invoicedAmount] = data.invoicedAmountGroupByJob;
	addJobRow("Invoiced Amount", invoicedAmount);

	//Invoice Details
	addSubCategory("Invoice ID", invoicedAmountGroupById);
	addSubjobTask(invoicedAmountGroupBySubjob, invoicedAmountGroupByTask);

	//Reconciled Amount
	reconciledAmount = unpackReconciledAmount(data);
	addJobRow("Reconciled Amount", reconciledAmount);
	//Invoice Details
	addSubCategory("Invoice ID", reconciledAmountGroupById);
	addSubjobTask(reconciledAmountGroupBySubjob, reconciledAmountGroupByTask);

	//Amount Spent
	let amountSpent = calculateAmountSpent(payroll, invoicedIn, expenses, dateInterval.length);
	addJobRow("Amount Spent", amountSpent);
	addSubCategory("Employee", payrollGroupByEId);
	addSubjobTask(payrollGroupBySubjob, payrollGroupByTask);

	addSubCategory("Invoice In", invoicedInGroupById);
	addSubCategory("Expenses", expensesGroupById);
}

/*
	Inputs:
		- handsontable (HOT)
	Action: binds the spreadsheet to a download button
	Dependencies:
		- HandonTable
		- Selector has to be "export-file" in HTML
	Output:
		- download button to UI
	Return: NONE
*/
function insertDownloadBTN(hot) {
	// Displays the Download Button
	//SELECTOR AND PLUGIN
	var downloadBTN = document.getElementById("export-file");
	var exportPlugin = hot.getPlugin("exportFile");

	//FILE NAME OF DOWNLOADED ITEM
	var filename = "CTR-".concat(id, " [YYYY]-[MM]-[DD]");
	downloadBTN.addEventListener("click", function() {
		exportPlugin.downloadFile("csv", {
			bom: false,
			columnDelimiter: ",",
			columnHeaders: true, //DISPLAY DATE IN DOWNLOAD
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

/*
	Inputs:
		- dateInterval
	Action: Returns an array of formatting for the CTR spreadsheet
	Return: spreadSheet Layout format
*/
function createSpreadsheetFormat(dateInterval) {
	//COLUMN HEADERS
	let colHeaders = ["Categories", "", "", ...dateInterval];

	//CURRENCY FORMATTING
	let columnFormats = [
		"",
		"",
		"",
		...dateInterval.map(item => {
			return {
				type: "numeric",
				numericFormat: {
					pattern: "$0,0.00",
					culture: "en-US" // this is the default culture, set up for USD
				}
			};
		})
	];
	return {
		data: spreadsheetData,
		width: "100%",
		rowHeights: 23,

		rowHeaders: true,
		colHeaders: colHeaders,
		columns: columnFormats,
		//filters: true,
		readOnly: true, // make table cells read-only
		stretchH: "all", //HORIZONTAL STRETCH
		//dropdownMenu: true,
		licenseKey: "non-commercial-and-evaluation",

		// STICKY ROWS AND COLUMNS
		fixedColumnsLeft: 3,
		//fixedRowsTop: 1,

		//RENDERING OFFSET - AFFECTS PERFORMANCE OF BROWSING
		// AND INITIAL LOADING
		viewportColumnRenderingOffset: 20,
		viewportRowRenderingOffset: 20,

		// CELL RENDERERS AND DESIGNS
		cells: function(row, col) {
			var cellProperties = {};
			var data = this.instance.getData();
			if (col === 0) {
				//FIRST COLUMN - JOB
				cellProperties.renderer = firstColRenderer;
			} else if (col === 1) {
				// SECOND COLUMN - SUBJOB
				cellProperties.renderer = secondColRenderer;
			} else if (col === 2) {
				// THIRD COLUMN - TASK
				cellProperties.renderer = thirdColRenderer;
			}
			return cellProperties;
		}
	};
}
/*
	Inputs:
		- API Data
	Action: Display the Spreadsheet form of API Data to the selector #spreadsheet with download button on #export-file
	Dependencies:
		- HandonTable CDN attached
	Output:
		- selector #spreadsheet
	Return: Promise of Finished Rendered
*/
function mainSpread(data) {
	return new Promise((resolve, reject) => {
		insertItemsIntoSpreadsheet(data);

		//Displays Spreadsheet
		var container = document.getElementById("spreadsheet");

		//RENDER THE SPREADSHEET
		var hot = new Handsontable(container, createSpreadsheetFormat(data.dateInterval));
		insertDownloadBTN(hot);

		//RESOLVES PROMISE UPON FINISH OF RENDERING
		//IIFE - immediately invoked function executable
		(hot.afterRender = () => {
			resolve(true);
		})();
	});
}
