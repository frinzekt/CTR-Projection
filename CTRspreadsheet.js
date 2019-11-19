let spreadsheetData = [];

function addSpreadSheetRow(row) {
	spreadsheetData.push(row);
}
function addBlankRow(length) {
	row = new Array(length).fill("");
	spreadsheetData.push(row);
}

function getDays(date) {
	return Math.floor(date / (1000 * 60 * 60 * 24));
}

// Initialize Title Layout
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

addSpreadSheetRow(["", "", "", ...dateFormatted]);

function mainSpread(projectId) {
	var container = document.getElementById("spreadsheet");
	console.log(spreadsheetData);
	var hot = new Handsontable(container, {
		data: spreadsheetData,
		width: 1000,
		height: 1000,
		rowHeaders: true,
		colHeaders: true,
		filters: true,
		readOnly: true, // make table cells read-only

		dropdownMenu: true,
		licenseKey: "non-commercial-and-evaluation"
	});
}
