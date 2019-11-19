createTrace = (xValues, yValues, name) => ({
	x: xValues,
	y: yValues,
	type: "scatter",
	name: name
});

createRandomArr = (length, max) =>
	Array.from({ length: length }, () => Math.floor(Math.random() * max));

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

	let originalSchedule = createTrace(
		x,
		x.map(x => budget * sigmoid(x)),
		"Original Schedule"
	);

	return originalSchedule;
}
function mainGraph(projectId) {
	let viewOptionArr = [true, true, true, true, true, true];
	let currentBudget = createTrace(
		createRandomArr(10, 100),
		createRandomArr(10, 100),
		"Current Budget"
	);
	let originalSchedule = createOriginalSchedule();
	let invoicedAmount = createTrace(
		createRandomArr(10, 100),
		createRandomArr(10, 100),
		"Invoiced Amount"
	);
	let paidAmount = createTrace(
		createRandomArr(10, 100),
		createRandomArr(10, 100),
		"Paid Amount"
	);
	let value = createTrace(
		createRandomArr(10, 100),
		createRandomArr(10, 100),
		"Value"
	);
	let amountSpent = createTrace(
		createRandomArr(10, 100),
		createRandomArr(10, 100),
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
		}
	};

	Plotly.newPlot("graph", showData, layout, { responsive: true });
}
