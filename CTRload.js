/*
	Inputs:
		- vars (POST/GET REQUEST)
	Action: Gets all variable in the URL by filter using regex and tokenising
	Dependencies:
		- NONE
	Output:
		- NONE
	Return: vars - dictionary of variable
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
		- NONE
	Return: returns/resolves data from a promise
*/
function CTRgraphload() {
	projectId = getUrlVars()["projectId"];
	return new Promise((resolve, reject) => {
		jQuery.ajax({
			url: "http://localhost:3000/query/fetch.php", //ajaxConn.ajax_url,
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
