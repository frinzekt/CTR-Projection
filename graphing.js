function linspace(startValue, stopValue, cardinality) {
	var arr = [];
	var step = (stopValue - startValue) / (cardinality - 1);
	for (var i = 0; i < cardinality; i++) {
		arr.push(startValue + step * i);
	}
	return arr;
}

budget = 1000;
max = 100;
x = linspace(0, max, max * 10);
sigmoid = xValue => 1 / (1 + Math.exp((-max / 1000) * (-max / 2 + xValue)));

createTrace = (xValues, yValues) => ({
	x: xValues,
	y: yValues,
	type: "scatter"
});

let trace1 = createTrace(
	x,
	x.map(x => budget * sigmoid(x))
);
console.log(trace1);

var data = [trace1];

var layout = {};

Plotly.newPlot("graph", data, layout, { responsive: true });
