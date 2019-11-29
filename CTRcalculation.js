/*
    Inputs:
        - array1
        - array2 
    Action: Adds the two arrays together element-wise while ensuring adding [] does not raise error
    Dependencies:
        - Numjs
    Output:
        - 
    Return: Sum of the two array
*/
const numjsAddition = (array1, array2) => {
	//ADDS ARRAY2 TO ARRAY1 IF ARRAY2 IS AN ARRAY AND NOT EMPTY
	array1 = nj.array(array1);
	if (!(!Array.isArray(array2) || !array2.length)) {
		array1 = array1.add(nj.array(array2));
	}
	return array1.tolist();
};
/*
    Inputs:
        - array1
        - array2
    Action: subtract the two arrays together element-wise while ensuring adding [] does not raise error
    Dependencies:
        - Numjs
    Output:
        - 
    Return: Sum of the two array
*/
const numjsSubtraction = (array1, array2) => {
	//ADDS ARRAY2 TO ARRAY1 IF ARRAY2 IS AN ARRAY AND NOT EMPTY
	array1 = nj.array(array1);
	if (!(!Array.isArray(array2) || !array2.length)) {
		array1 = array1.subtract(nj.array(array2));
	}
	return array1.tolist();
};

/*
    Inputs:
        - array
        - precision
    Action: Rounds all element in the array by precision
    Dependencies:
        - 
    Output:
        - 
    Return: original array (TypeError) or rounded array
*/
const roundArray = (array, precision) => {
	try {
		return array.map(x => parseFloat(x).toFixed(precision));
	} catch (e) {
		if (e instanceof TypeError) {
			return array;
		}
	}
};

/*
    Inputs:
        - 	groupByJobValue,
	    -   groupBySubjobValue,
	    -   subjobIdToSubtract,
	    -   expectedLength - used to initialize the summing array as groupByJobValue not necessarily filled
    Action: Subtracts the value of the subjobs that are unchecked in the jobs
    Dependencies:
        - numjsAddition() <- CTRcalculation.js
        - numjsSubtraction() <- CTRcalculation.js
        - roundArray() <- CTRcalculation.js
    Output:
        - 
    Return: the remaining sum of Job Value excluding the subjob unchecked
*/
const subjobSubtraction = (
	groupByJobValue,
	groupBySubjobValue,
	subjobIdToSubtract,
	expectedLength
) => {
	result = new Array(expectedLength).fill(0);
	result = numjsAddition(result, groupByJobValue); // INIT VALUE

	//SUBTRACT ONLY IF THE ID BELONGS TO THE SET(subjobId) TO SUBTRACT
	groupBySubjobValue.forEach(([id, ...values]) => {
		//OTHER HAS A ID = "-1"
		//IF ID = NULL OR SOMETHING EQUIVALENT, CONVERT TO -1
		if (["other", "null", ""].includes(id.toLowerCase())) {
			id = "-1";
		}
		if (subjobIdToSubtract.includes(id)) {
			result = numjsSubtraction(result, values);
		}
	});

	return roundArray(result);
};

/*
    Inputs:
        - 	groupByJobValue,
	    -   groupBySubjobValue,
	    -   subjobIdToSubtract,
	    -   expectedLength - used to initialize the summing array as groupByJobValue not necessarily filled
    Action: Subtracts the value of the tasks that are unchecked in the jobs
    Dependencies:
        - numjsAddition() <- CTRcalculation.js
        - numjsSubtraction() <- CTRcalculation.js
        - roundArray() <- CTRcalculation.js
    Output:
        - 
    Return: the remaining sum of Job Value excluding the task unchecked
*/
const subjobTaskSubtraction = (
	groupByJobValue,
	groupByTaskValue,
	subjobTaskIdToSubtract, // in the form [[s1,t1],[s2,t2]...]
	expectedLength
) => {
	result = new Array(expectedLength).fill(0);
	result = numjsAddition(result, groupByJobValue); // INIT VALUE

	//SUBTRACT ONLY IF THE ID BELONGS TO THE SET(subjobId) TO SUBTRACT
	//EXCEPT 1: if taskId = other ... where subjobId does not matter
	groupByTaskValue.forEach(([subjobId, taskId, ...values]) => {
		if (["other", "null", ""].includes(subjobId.toLowerCase())) {
			subjobId = "-1";
		}
		if (["other", "null", ""].includes(taskId.toLowerCase())) {
			taskId = "-1";
		}
		let combinedKey = subjobId + "," + taskId;

		subjobTaskIdToSubtract.forEach(key => {
			[targetSubjobId, targetTaskId] = key.split(",");

			// Original Condition or subjob doesnt matter as task "other" is picked
			if (
				(targetSubjobId == subjobId && targetTaskId == taskId) ||
				(targetTaskId == "-1" && taskId == "-1")
			) {
				result = numjsSubtraction(result, values);
			}
		});
	});

	return roundArray(result);
};

/*
    Inputs:
        - 	payroll,
        -   invoicedIn,
        -   expenses,
        -   expectedLength - used to initialize sum array as parameters may not necesarilly be filled
    Action: Used to calculate amount spent
    Dependencies:
        - numJsAddition() <- CTRcalculation.js
    Output:
        - 
    Return: amount spent
*/
const calculateAmountSpent = (
	payroll,
	invoicedIn,
	expenses,
	expectedLength
) => {
	let amountSpent = new Array(expectedLength).fill(0);
	amountSpent = numjsAddition(amountSpent, payroll);
	amountSpent = numjsAddition(amountSpent, invoicedIn);
	amountSpent = numjsAddition(amountSpent, expenses);
	return amountSpent;
};
