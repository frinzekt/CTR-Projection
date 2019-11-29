//HIDES THE CONTENTS AND SHOWS THE LOADER
const hideContent = () => {
	//Opacity is used for manipulating visibility as to allow rendering in the background
	let loading = true;
	jQuery("#loader-parent").css("opacity", "1");
	jQuery("#CTR-content").css("opacity", "0");
};

//SHOWS THE CONTENT AND HIDES THE LOADER
const showContent = () => {
	let loading = false;
	jQuery("#loader-parent").css("opacity", "0");
	jQuery("#CTR-content").css("opacity", "1");
};

/*
    Inputs:
        - name - attribute name of checkbox to be recorded
    Action: Uses jquery in order to filter out all the boxes check and appends to an array
    Dependencies:
        - 
    Output:
        - 
    Return: array of values of the corresponding checkboxes with the name
*/
const getUncheckedValues = name => {
	let values = [];
	jQuery(`input:checkbox[name='${name}']:not(:checked)`).each(function() {
		values.push(jQuery(this).val());
	});
	return values;
};

/*
    Inputs:
        - subjobId
    Action: Hides all the elements with the class corresponding to a subjob specifically used for tasks
    Dependencies:
        - 
    Output:
        - hide Task in UI 
    Return: None
*/
const hideTaskInSubjobs = subjobId => {
	jQuery(`.subjob-${subjobId}`).toggle();
	jQuery(`.subjob-${subjobId}`).prop("checked", true);
};

/*
    Inputs:
        - event
    Action: Takes the value of the subjob element and toggles all the task for that element
    Dependencies:
        - hideTaskInSubJobs() <- CTReventHandler.js
    Output:
        - Hide tasks in UI
    Return: None
*/
const handleSubjobChange = e => {
	let value = e.value;
	hideTaskInSubjobs(value);
};

/*
    Inputs:
        - event (unused for a moment)
    Action: General change handling (Currently used to for a "Apply Changes")
    Dependencies:
        - getUncheckedValues() <- CTReventHandler.js
        - unpack Data Functions <- CTRdataParser.js
        - Trace and graph redraw <- CTRgraphing.js
    Output:
        - Redraw graph
    Return: None
*/
const handleChange = e => {
	let subjobIdUnchecked = getUncheckedValues("Subjobs[]");
	let subjobTaskIdUnchecked = getUncheckedValues("Tasks[]");

	let { currentBudgetJob, dateInterval, originalSchedule } = data;
	let { payrollGroupBySubjob, payrollGroupByTask } = data;

	//GROUP BY JOB DATA UNPACKING
	invoicedAmount = unpackInvoicedAmount(data);
	reconciledAmount = unpackReconciledAmount(data);
	payroll = unpackPayroll(data);
	invoicedIn = unpackInvoicedIn(data);
	expenses = unpackExpenses(data);
	value = unpackValue(data);

	console.log(payroll);
	//Recalculate data points
	newPayroll = subjobSubtraction(
		payroll,
		payrollGroupBySubjob,
		subjobIdUnchecked,
		dateInterval.length
	);
	console.log(newPayroll);
	newPayroll = subjobTaskSubtraction(
		newPayroll,
		payrollGroupByTask,
		subjobTaskIdUnchecked,
		dateInterval.length
	);
	console.log(newPayroll);

	let amountSpent = calculateAmountSpent(
		newPayroll,
		invoicedIn,
		expenses,
		dateInterval.length
	);

	//Redraw Graph
	reDrawGraph(
		dateInterval,
		currentBudgetJob,
		originalSchedule,
		invoicedAmount,
		reconciledAmount,
		value,
		amountSpent
	);
};
const handleTaskChange = e => {
	//STUB
};
