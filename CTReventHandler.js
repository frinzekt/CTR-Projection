/*
	Inputs:
		- No Input (but requires selectors to be named properly in HTML)
	Action: make the content invisible and the loader visible
	Dependencies:
		-  JQUERY
	Output:
		- NONE
	Return: NONE
*/
const hideContent = () => {
	//Opacity is used for manipulating visibility as to allow rendering in the background
	let loading = true;
	jQuery("#loader-parent").css("opacity", "1");
	jQuery("#CTR-content").css("opacity", "0");
};

/*
	Inputs:
		- No Input (but requires selectors to be named properly in HTML)
	Action: make the loader-parent invisible and content visible
	Dependencies:
		-  JQUERY
	Output:
		- NONE
	Return: NONE
*/
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
        -  JQUERY
    Output:
        - NONE
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
        -  JQUERY
    Output:
        - hide Task in UI 
    Return: None
*/
const hideTaskInSubjobs = subjobId => {
	jQuery(`.subjob-${subjobId}`).toggle();
	jQuery(`.subjob-${subjobId}`).prop("checked", true);
};

const getNumberMode = () => {
	return jQuery(`#numberMode`).val();
};

const getDaysInterval = () => {
	return jQuery(`#daysInterval`).val();
};

/*
    Inputs:
        - event
    Action: Takes the value of the subjob element and toggles all the task for that element
    Dependencies:
        - hideTaskInSubJobs() <- CTReventHandler.js
    Output:
        - Hide tasks of specific subjobId in UI
    Return: None
*/
const handleSubjobChange = e => {
	let value = e.value;
	hideTaskInSubjobs(value);
};

/*
    Inputs:
        - event (unused for a moment) - REVIEW  - probably dont need it
    Action: General change handling (Currently used to for a "Apply Changes")
    Dependencies:
        - getUncheckedValues() <- CTReventHandler.js
        - unpack Data Functions <- CTRdataParser.js
        - Trace and graph redraw <- CTRgraphing.js
    Output:
        - Redraw graph with filtered data
    Return: None
*/
const handleChange = e => {
	let subjobIdUnchecked = getUncheckedValues("Subjobs[]");
	let subjobTaskIdUnchecked = getUncheckedValues("Tasks[]");
	let numberMode = getNumberMode();

	let { currentBudgetJob, dateInterval, originalSchedule } = data;
	let {
		invoicedAmountGroupBySubjob,
		invoicedAmountGroupByTask,
		reconciledAmountGroupBySubjob,
		reconciledAmountGroupByTask,
		expensesGroupBySubjob,
		expensesGroupByTask,
		valueGroupBySubjob,
		valueGroupByTask,
		payrollGroupBySubjob,
		payrollGroupByTask
	} = data;

	//TYPE INIT
	let newPayroll = [0];

	//GROUP BY JOB DATA UNPACKING - found in CTRdataParser.js
	// USES ES6 DESTRUCTURING TO EXTRACT THE DATASET FROM API
	invoicedAmount = unpackInvoicedAmount(data);
	reconciledAmount = unpackReconciledAmount(data);
	payroll = unpackPayroll(data);
	invoicedIn = unpackInvoicedIn(data);
	expenses = unpackExpenses(data);
	value = unpackValue(data);

	//Recalculate data points

	//REVIEW: ALL INVOICES THAT HAS FIELD "SUBJOB" (HUMAN READABLE NAME) CANNOT BE TIED TO A SUBJOBID HENCE CANNOT INCLUDE IN CALCULATIONS
	// newInvoicedAmount = subjobSubtraction(invoicedAmount, invoicedAmountGroupBySubjob, subjobIdUnchecked, dateInterval.length);
	// newInvoicedAmount = subjobTaskSubtraction(newInvoicedAmount, invoicedAmountGroupByTask, subjobTaskIdUnchecked, dateInterval.length);
	// newReconciledAmount = subjobSubtraction(reconciledAmount, reconciledAmountGroupBySubjob, subjobIdUnchecked, dateInterval.length);
	// newReconciledAmount = subjobTaskSubtraction(newReconciledAmount, reconciledAmountGroupByTask, subjobTaskIdUnchecked, dateInterval.length);
	newExpenses = subjobSubtraction(expenses, expensesGroupBySubjob, subjobIdUnchecked, dateInterval.length);
	newExpenses = subjobTaskSubtraction(newExpenses, expensesGroupByTask, subjobTaskIdUnchecked, dateInterval.length);
	newValue = subjobSubtraction(value, valueGroupBySubjob, subjobIdUnchecked, dateInterval.length);
	newValue = subjobTaskSubtraction(newValue, valueGroupByTask, subjobTaskIdUnchecked, dateInterval.length);
	newPayroll = subjobSubtraction(payroll, payrollGroupBySubjob, subjobIdUnchecked, dateInterval.length);
	newPayroll = subjobTaskSubtraction(newPayroll, payrollGroupByTask, subjobTaskIdUnchecked, dateInterval.length);
	let amountSpent = calculateAmountSpent(newPayroll, invoicedIn, expenses, dateInterval.length);

	//Redraw Graph
	if (numberMode == "$") {
		reDrawGraph(dateInterval, currentBudgetJob, originalSchedule, invoicedAmount, reconciledAmount, value, amountSpent, "Amount ($)");
	} else {
		let maxBudget = parseFloat(currentBudgetJob[currentBudgetJob.length - 1]);

		reDrawGraph(
			dateInterval,
			convertDisplayToPercentage(currentBudgetJob, maxBudget),
			convertDisplayToPercentage(originalSchedule, maxBudget),
			convertDisplayToPercentage(invoicedAmount, maxBudget),
			convertDisplayToPercentage(reconciledAmount, maxBudget),
			convertDisplayToPercentage(value, maxBudget),
			convertDisplayToPercentage(amountSpent, maxBudget),
			"Amount (% in Budget)"
		);
	}
};
const handleTaskChange = e => {
	//STUB - Nothing so far - this dictates what should happen when task changed
	//Currently being done by handleChange event
};
