/*
	Inputs:
		- xValues (array - floating point/int)
		- yValues (array - floating point/int)
		- Name of Trace (String)
	Action: Creates a Scatter trace for Plotly depending on the above inputs
	Dependencies:
		- Plotly
	Output:
		- NONE
	Return: Plotly Scatter Trace
*/
createTrace = (xValues, yValues, name) => ({
	x: xValues,
	y: yValues,
	type: "scatter",
	name: name
});
createSplineTrace = (xValues, yValues, name) => {
	let trace = createTrace(xValues, yValues, name);
	trace.line = { shape: "spline" };
	return trace;
};

/*
	Inputs:
		- dateInterval (string - datetime)
		- currentBudget (array - number)
		- originalSchedule(array - number)
		- invoicedAmount(array - number)
		- reconciledAmount(array - number)
		- value (array - number)
		- amountSpent(array - number)
		
	Action: Uses repeated createTrace() function in order to create multiple different traces for each dataset
	Dependencies:
		- createTrace() - CTRgraphing.js
	Output:
		- NONE
	Return: CTR Traces containing data of the inputs this function
*/
const createCTRTraces = (dateInterval, currentBudget, originalSchedule, invoicedAmount, reconciledAmount, value, amountSpent) => {
	// CURRENT BUDGET - BUDGET ALLOTED AT CERTAIN TIME
	//FROM API - CURRENTLY FROM PURCHASE ORDER - VALUE
	let currentBudgetTrace = createTrace(dateInterval, currentBudget, "Current Budget");

	// ORIGINAL SCHEDULE - PREDICTED EXPENDITURE AT CERTAIN TIME
	//FROM API - CURRENTLY FROM GENERATE ORIGINAL SCHEDULE FUNCTION BASED ON MAX BUDGET
	let originalScheduleTrace = createTrace(dateInterval, originalSchedule, "Original Schedule");

	// INVOICED AMOUNT - AMOUNT INVOICED AT CERTAIN TIME
	//FROM API - CURRENTLY FROM INVOICEUNPAID - NUMBER * PRICE
	let invoicedAmountTrace = createTrace(dateInterval, invoicedAmount, "Invoiced Amount");
	// PAID AMOUNT - AMOUNT PAID AT CERTAIN TIME
	//FROM API - CURRENTLY FROM INVOICEUNPAID - RECONCILED
	let paidAmountTrace = createTrace(dateInterval, reconciledAmount, "Paid Amount");
	//VALUE - VALUE OF PROJECT AT A CERTAIN TIME
	//FROM API - CURRENTLY FROM VARIOUS TABLE MAINLY FROM DELIVERABLES HISTORY
	//SEE API FOR MORE INFORMATION  - getValueSQLTemplate()
	let valueTrace = createTrace(dateInterval, value, "Value");

	//AMOUNT SPENT - AMOUNT SPENT
	/*FROM API OF 3 DATASET:
	1. Employee Payroll
	2. Invoice In (Contractor)
	3. Expenditure

	*/
	let amountSpentTrace = createTrace(dateInterval, amountSpent, "Amount spent");

	// PACKAGES TRACES
	let graphData = [currentBudgetTrace, originalScheduleTrace, invoicedAmountTrace, paidAmountTrace, valueTrace, amountSpentTrace];

	return graphData;
};

/*
	Inputs:
		- initWidth (int - window size in pixel)
		- initHeight (int - window size in pixel)
	Action: Creates the CTR Layout
	Dependencies:
		- None
	Output:
		- None
	Return: Plotly CTR Layout
*/
const createCTRLayout = (initWidth, initHeight, dateInterval, currentBudgetJob) => {
	let layout = {
		width: initWidth,
		height: initHeight,
		title: {
			text: "Cost, Time Resource",
			font: {
				size: 24
			}
		},
		xaxis: {
			title: {
				text: "Date Progression",
				font: {
					size: 18
				}
			}
		},
		yaxis: {
			title: {
				text: "Amount ($)",
				font: {
					size: 18
				}
			}
		},
		//HORIZONTAL S-CURVE ASYMPTOTE
		shapes: [
			{
				type: "line",
				xref: "x",
				yref: "y",
				x0: dateInterval[0],
				y0: currentBudgetJob[currentBudgetJob.length - 1],
				x1: dateInterval[dateInterval.length - 1],
				y1: currentBudgetJob[currentBudgetJob.length - 1],
				line: {
					color: "rgb(55, 128, 100)",
					width: 3,
					dash: "dash"
				}
			}
		]
		//hovermode: "closest"
	};
	return layout;
};
/*
    Inputs:
        - API CTR Data
	Action:
		- Creates traces for all the API Data
		- Layouting of the Traces
		- display trace to the selector
    Dependencies:
		- createTrace()
		- Plotly CDN attached
    Output:
        - Produces a graph that is directed selector #graph
    Return: Plotly Promise of Creation
*/
const reDrawGraph = (
	dateInterval,
	currentBudget,
	originalSchedule,
	invoicedAmount,
	reconciledAmount,
	value,
	amountSpent,
	newTitle = "Insert Title Here"
) => {
	//Create Traces for all API Data
	console.log(dateInterval, currentBudget, originalSchedule, invoicedAmount, reconciledAmount, value, amountSpent);
	graphData = createCTRTraces(dateInterval, currentBudget, originalSchedule, invoicedAmount, reconciledAmount, value, amountSpent);
	console.log(graphData);
	// Find Selector then recreate there
	let target = document.querySelector("#graph");
	let layout = createCTRLayout(target.clientWidth, target.clientHeight, dateInterval, currentBudget);
	layout.yaxis.title.text = newTitle;
	return Plotly.newPlot(target, graphData, layout, { responsive: true });
};

/*
    Inputs:
        - API CTR Data
	Action:
		- Creates traces for all the API Data
		- Layouting of the Traces
		- display trace to the selector
    Dependencies:
		- createTrace()
		- Plotly CDN attached
    Output:
        - Produces a graph that is directed selector #graph
    Return: Plotly Promise of creation
*/

function mainGraph(data) {
	let { currentBudgetJob, dateInterval, originalSchedule } = data;

	//GROUP BY JOB DATA UNPACKING
	let invoicedAmount = unpackInvoicedAmount(data);
	let reconciledAmount = unpackReconciledAmount(data);
	let payroll = unpackPayroll(data);
	let invoicedIn = unpackInvoicedIn(data);
	let expenses = unpackExpenses(data);
	let value = unpackValue(data);

	// SUMS ALL THE EXPENSES - CTRcalculation.js
	let amountSpent = calculateAmountSpent(payroll, invoicedIn, expenses, dateInterval.length);

	//SHOWING GRAPH - global variable to be picked up by event handlers
	graphData = createCTRTraces(dateInterval, currentBudgetJob, originalSchedule, invoicedAmount, reconciledAmount, value, amountSpent);

	let target = document.querySelector("#graph");
	let layout = createCTRLayout(target.clientWidth, target.clientHeight, dateInterval, currentBudgetJob);

	return Plotly.plot(target, graphData, layout, { responsive: true });
}
