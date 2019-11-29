/*
	Inputs:
		- xValues
		- yValues
		- Name of Trace
	Action: Creates a Scatter trace for Plotly depending on the above inputs
	Dependencies:
		- 
	Output:
		- 
	Return: Plotly Scatter Trace
*/
createTrace = (xValues, yValues, name) => ({
	x: xValues,
	y: yValues,
	type: "scatter",
	name: name
});

// const calculateDisplay() => {

// }

const createCTRTraces = (
	dateInterval,
	currentBudget,
	originalSchedule,
	invoicedAmount,
	reconciledAmount,
	value,
	amountSpent
) => {
	let currentBudgetTrace = createTrace(
		dateInterval,
		currentBudget,
		"Current Budget"
	);
	let originalScheduleTrace = createTrace(
		dateInterval,
		originalSchedule,
		"Original Schedule"
	);

	let invoicedAmountTrace = createTrace(
		dateInterval,
		invoicedAmount,
		"Invoiced Amount"
	);
	let paidAmountTrace = createTrace(
		dateInterval,
		reconciledAmount,
		"Paid Amount"
	);
	let valueTrace = createTrace(dateInterval, value, "Value");
	let amountSpentTrace = createTrace(dateInterval, amountSpent, "Amount spent");

	let graphData = [
		currentBudgetTrace,
		originalScheduleTrace,
		invoicedAmountTrace,
		paidAmountTrace,
		valueTrace,
		amountSpentTrace
	];

	return graphData;
};
const createCTRLayout = (initWidth, initHeight) => {
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
				text: "Percentage Value?? Amount $",
				font: {
					size: 18
				}
			}
		}
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
    Return: None
*/
const reDrawGraph = (
	dateInterval,
	currentBudget,
	originalSchedule,
	invoicedAmount,
	reconciledAmount,
	value,
	amountSpent
) => {
	graphData = createCTRTraces(
		dateInterval,
		currentBudget,
		originalSchedule,
		invoicedAmount,
		reconciledAmount,
		value,
		amountSpent
	);
	let target = document.querySelector("#graph");
	let layout = createCTRLayout(target.clientWidth, target.clientHeight);
	return Plotly.newPlot(target, graphData, layout, { responsive: true });
};
function mainGraph(data) {
	let { currentBudgetJob, dateInterval, originalSchedule } = data;

	//GROUP BY JOB DATA UNPACKING
	invoicedAmount = unpackInvoicedAmount(data);
	reconciledAmount = unpackReconciledAmount(data);
	payroll = unpackPayroll(data);
	invoicedIn = unpackInvoicedIn(data);
	expenses = unpackExpenses(data);
	value = unpackValue(data);

	let amountSpent = calculateAmountSpent(
		payroll,
		invoicedIn,
		expenses,
		dateInterval.length
	);

	//SHOWING GRAPH - global variable to be picked up by event handlers
	graphData = createCTRTraces(
		dateInterval,
		currentBudgetJob,
		originalSchedule,
		invoicedAmount,
		reconciledAmount,
		value,
		amountSpent
	);
	let target = document.querySelector("#graph");
	let layout = createCTRLayout(target.clientWidth, target.clientHeight);

	return Plotly.plot(target, graphData, layout, { responsive: true });
}
