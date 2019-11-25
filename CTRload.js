function getUrlVars() {
	var vars = {};
	var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(
		m,
		key,
		value
	) {
		vars[key] = value;
	});
	return vars;
}

function pageload() {
	projectId = getUrlVars()["projectId"];
	return new Promise((resolve, reject) => {
		$.ajax({
			url: "http://localhost:3000/query/fetch.php",
			method: "POST",
			data: {
				projectId: projectId
			},
			success: data => {
				data = JSON.parse(data);
				console.log(data);
				resolve(data);
			}
		});
	});
}

unpackInvoicedAmount = data => {
	[id, ...invoicedAmount] = data.invoicedAmountGroupByJob;
	return invoicedAmount;
};

unpackReconciledAmount = data => {
	[id, ...reconciledAmount] = data.reconciledAmountGroupByJob;
	return reconciledAmount;
};

unpackReconciledAmount = data => {
	[id, ...reconciledAmount] = data.reconciledAmountGroupByJob;
	return reconciledAmount;
};
