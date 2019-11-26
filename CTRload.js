/*
	Inputs:
		- vars (POST/GET REQUEST)
	Action: Gets all variable in the URL
	Dependencies:
		- 
	Output:
		- 
	Return: vars
*/
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

//Uses AJAX Promise to Request For Data
/*
	Inputs:
		- projectId (POST/GET REQUEST) using getUrlVars
	Action: Performs an AJAX call from the function ajaxCTRload 
	Dependencies:
		- 
	Output:
		- 
	Return: returns/resolves data from a promise
*/
function CTRgraphload() {
	projectId = getUrlVars()["projectId"];
	return new Promise((resolve, reject) => {
		jQuery.ajax({
			url: ajaxConn.ajax_url,
			method: "POST",
			data: {
				action: "ajaxCTRload",
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
