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

//Prefills data for unoccupied piece in the graph (placeholder)
createRandomArr = (length, max) =>
	Array.from({ length: length }, () => Math.floor(Math.random() * max));

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

function mainGraph(data) {
	let { currentBudgetJob, dateInterval, originalSchedule } = data;

	//INVOICES UNPACKING
	let { invoicedAmountGroupByJob, reconciledAmountGroupByJob } = data;
	invoicedAmount = unpackInvoicedAmount(data);
	reconciledAmount = unpackReconciledAmount(data);

	//EXPENSES UNPACKING
	let { payrollGroupByJob, invoicedInGroupByJob, expensesGroupByJob } = data;
	[id, ...payroll] = payrollGroupByJob;
	[id, ...invoicedIn] = invoicedInGroupByJob;
	[id, ...expenses] = expensesGroupByJob;

	amountSpent = payroll;
	if (!(!Array.isArray(invoicedIn) || !invoicedIn.length)) {
		amountSpent = list(nj.array(amountSpent).add(nj.array(invoicedIn)));
	}
	if (!(!Array.isArray(expenses) || !expenses.length)) {
		amountSpent = list(nj.array(amountSpent).add(nj.array(expenses)));
	}

	let viewOptionArr = [true, true, true, true, true, true];
	let currentBudgetTrace = createTrace(
		dateInterval,
		currentBudgetJob,
		"Current Budget"
	);
	//let originalSchedule = createOriginalSchedule();
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
	let valueTrace = createTrace(
		dateInterval,
		createRandomArr(54, 10000),
		"Value"
	);
	let amountSpentTrace = createTrace(dateInterval, amountSpent, "Amount spent");

	//SHOWING GRAPH

	var graphData = [
		currentBudgetTrace,
		originalScheduleTrace,
		invoicedAmountTrace,
		paidAmountTrace,
		valueTrace,
		amountSpentTrace
	];
	let showData = [];
	console.log(graphData);
	console.log(createRandomArr(10, 100));
	viewOptionArr.forEach((isShown, index) => {
		if (isShown) {
			showData.push(graphData[index]);
		}
	});

	var layout = {
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

	Plotly.newPlot("graph", showData, layout, { responsive: true });
}
