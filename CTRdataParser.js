//USED FOR UNPACKING JSON API
unpackInvoicedAmount = data => {
	[id, ...invoicedAmount] = data.invoicedAmountGroupByJob;
	return invoicedAmount;
};

//USED FOR UNPACKING JSON API
unpackReconciledAmount = data => {
	[id, ...reconciledAmount] = data.reconciledAmountGroupByJob;
	return reconciledAmount;
};

//USED FOR UNPACKING JSON API
unpackReconciledAmount = data => {
	[id, ...reconciledAmount] = data.reconciledAmountGroupByJob;
	return reconciledAmount;
};

unpackPayroll = data => {
	[id, ...payroll] = data.payrollGroupByJob;
	return payroll;
};
unpackInvoicedIn = data => {
	[id, ...invoicedIn] = data.invoicedInGroupByJob;
	return invoicedIn;
};
unpackExpenses = data => {
	[id, ...expenses] = data.expensesGroupByJob;
	return expenses;
};
unpackValue = data => {
	[id, ...value] = data.valueGroupByJob;
	return value;
};

const merge2DArray = (array1, array2, noIdentifyingIndices) => {
	//noIdentifyingIndices = 1 for 1 uniqueId , = 2 for 2 uniqueId ... and so on
	// THIS FUNCTION IS USED TO MERGE 2D ARRAY WHERE noIdentifyingIndices are the point of merging
	// basically like a 2D column appending but ignores the first few items
	merged = [];

	// APPEND THE ITEMS of the uniqueIdentifyingIndices
	function appendArrays() {
		var row = [];
		for (var i = 0; i < arguments.length; i++) {
			if (i == 0) {
				row.push(...arguments[i]);
			} else {
				row.push(...arguments[i].slice(noIdentifyingIndices));
			}
		}
		return row;
	}

	//LOOP OVER EACH INDIVIDUAL uniqueIdentifyingIndices
	for (i = 0; i < array2.length; i++) {
		merged.push(appendArrays(array1[i], array2[i]));
	}
	//console.log(merged);
	return merged;
};
const mergeAPIArray = (data, fetchData) => {
	//MERGE 1D Array - take original data and take only the portion of fetch without the projectId
	data.currentBudgetJob = [...data.currentBudgetJob, ...fetchData.currentBudgetJob];
	data.originalSchedule = [...data.originalSchedule, ...fetchData.originalSchedule];
	data.invoicedAmountGroupByJob = [...data.invoicedAmountGroupByJob, ...fetchData.invoicedAmountGroupByJob.slice(1)];
	data.reconciledAmountGroupByJob = [...data.reconciledAmountGroupByJob, ...fetchData.reconciledAmountGroupByJob.slice(1)];
	data.payrollGroupByJob = [...data.payrollGroupByJob, ...fetchData.payrollGroupByJob.slice(1)];
	data.invoicedInGroupByJob = [...data.invoicedInGroupByJob, ...fetchData.invoicedInGroupByJob.slice(1)];
	data.expensesGroupByJob = [...data.expensesGroupByJob, ...fetchData.expensesGroupByJob.slice(1)];
	// data.valueGroupByJob= [...data.valueGroupByJobb, ...fetchData.valueGroupByJob]; //TODO

	data.purchaseOrderDetails = merge2DArray(data.purchaseOrderDetails, fetchData.purchaseOrderDetails, 1);
	data.invoicedAmountGroupById = merge2DArray(data.invoicedAmountGroupById, fetchData.invoicedAmountGroupById, 1);
	data.invoicedAmountGroupBySubjob = merge2DArray(data.invoicedAmountGroupBySubjob, fetchData.invoicedAmountGroupBySubjob, 1);
	data.invoicedAmountGroupByTask = merge2DArray(data.invoicedAmountGroupByTask, fetchData.invoicedAmountGroupByTask, 2);
	data.reconciledAmountGroupById = merge2DArray(data.reconciledAmountGroupById, fetchData.reconciledAmountGroupById, 1);
	data.reconciledAmountGroupBySubjob = merge2DArray(data.reconciledAmountGroupBySubjob, fetchData.reconciledAmountGroupBySubjob, 1);
	data.reconciledAmountGroupByTask = merge2DArray(data.reconciledAmountGroupByTask, fetchData.reconciledAmountGroupByTask, 2);
	data.payrollGroupByEId = merge2DArray(data.payrollGroupByEId, fetchData.payrollGroupByEId, 1);
	data.payrollGroupBySubjob = merge2DArray(data.payrollGroupBySubjob, fetchData.payrollGroupBySubjob, 1);
	data.payrollGroupByTask = merge2DArray(data.payrollGroupByTask, fetchData.payrollGroupByTask, 2);
	data.invoicedInGroupById = merge2DArray(data.invoicedInGroupById, fetchData.invoicedInGroupById, 1);
	data.expensesGroupById = merge2DArray(data.expensesGroupById, fetchData.expensesGroupById, 1);
	data.expensesGroupBySubjob = merge2DArray(data.expensesGroupBySubjob, fetchData.expensesGroupBySubjob, 1);
	data.expensesGroupByTask = merge2DArray(data.expensesGroupByTask, fetchData.expensesGroupByTask, 2);

	//MERGE 2D ARRAY

	return data;
};
