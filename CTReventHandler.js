const hideContent = () => {
	//Opacity is used for manipulating visibility as to allow rendering in the background
	let loading = true;
	$("#loader-parent").css("opacity", "1");
	$("#CTR-content").css("opacity", "0");
};
const showContent = () => {
	let loading = false;
	$("#loader-parent").css("opacity", "0");
	$("#CTR-content").css("opacity", "1");
};

const getUncheckedValues = name => {
	let values = [];
	$(`input:checkbox[name='${name}']:not(:checked)`).each(function() {
		values.push($(this).val());
	});
	return values;
};

const hideTaskInSubjobs = subjobId => {
	$(`.subjob-${subjobId}`).toggle();
	$(`.subjob-${subjobId}`).prop("checked", true);
};

const handleSubjobChange = e => {
	let value = e.value;
	hideTaskInSubjobs(value);
};

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

	//Recalculate data points
	newPayroll = subjobSubtraction(
		payroll,
		payrollGroupBySubjob,
		subjobIdUnchecked,
		dateInterval.length
	);
	newPayroll = subjobTaskSubtraction(
		newPayroll,
		payrollGroupByTask,
		subjobTaskIdUnchecked,
		dateInterval.length
	);

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
