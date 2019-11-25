createTrace = (xValues, yValues, name) => ({
	x: xValues,
	y: yValues,
	type: "scatter",
	name: name
});

createRandomArr = (length, max) =>
	Array.from({ length: length }, () => Math.floor(Math.random() * max));

createRandomTimeArr = (length, max) =>
	Array.from(
		{ length: length },
		() => new Date(Math.floor(Math.random() * max) * 1000)
	);

function linspace(startValue, stopValue, cardinality) {
	var arr = [];
	var step = (stopValue - startValue) / (cardinality - 1);
	for (var i = 0; i < cardinality; i++) {
		arr.push(startValue + step * i);
	}
	return arr;
}

function createOriginalSchedule() {
	budget = 1000;
	max = 100;
	x = linspace(0, max, max * 10);
	sigmoid = xValue => 1 / (1 + Math.exp((-max / 1000) * (-max / 2 + xValue)));

	xDates = [...x];
	xDates.forEach(
		(element, index) => (xDates[index] = new Date(element * 1000))
	);

	let originalSchedule = createTrace(
		xDates,
		x.map(x => budget * sigmoid(x)),
		"Original Schedule"
	);

	return originalSchedule;
}
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
