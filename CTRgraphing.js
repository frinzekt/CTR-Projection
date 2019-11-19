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
function mainGraph(projectId) {
	let viewOptionArr = [true, true, true, true, true, true];
	let currentBudget = createTrace(
		createRandomTimeArr(10, 10000000),
		createRandomArr(10, 1000),
		"Current Budget"
	);
	let originalSchedule = createOriginalSchedule();
	let invoicedAmount = createTrace(
		createRandomTimeArr(10, 10000000),
		createRandomArr(10, 1000),
		"Invoiced Amount"
	);
	let paidAmount = createTrace(
		createRandomTimeArr(10, 10000000),
		createRandomArr(10, 1000),
		"Paid Amount"
	);
	let value = createTrace(
		createRandomTimeArr(10, 10000000),
		createRandomArr(10, 1000),
		"Value"
	);
	let amountSpent = createTrace(
		createRandomTimeArr(10, 10000000),
		createRandomArr(10, 1000),
		"Amount spent"
	);

	//SHOWING GRAPH

	var data = [
		currentBudget,
		originalSchedule,
		invoicedAmount,
		paidAmount,
		value,
		amountSpent
	];
	let showData = [];
	console.log(data);
	console.log(createRandomArr(10, 100));
	viewOptionArr.forEach((isShown, index) => {
		if (isShown) {
			showData.push(data[index]);
		}
	});

	var layout = {
		title: {
			text: "Cost Resource Over Time",
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
		},
		hovermode: "closest"
	};

	Plotly.newPlot("graph", showData, layout, { responsive: true });
}
