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
