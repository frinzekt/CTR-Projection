/*
    Inputs:
        - array1
        - array2
    Action: Adds the two arrays together element-wise while ensuring adding [] does not raise error
    Dependencies:
        - Numjs
    Output:
        - None
    Return: Sum of the two array
*/
const numjsAddition = (array1, array2) => {
	//ADDS ARRAY2 TO ARRAY1 IF ARRAY2 IS AN ARRAY AND NOT EMPTY
	array1 = nj.array(array1);
	try {
		if (!(!Array.isArray(array2) || !array2.length)) {
			array1 = array1.add(nj.array(array2));
		}
	} catch (error) {
		console.log(error);
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
        - None
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
const roundArray = (array, precision = 2) => {
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
const subjobSubtraction = (groupByJobValue, groupBySubjobValue, subjobIdToSubtract, expectedLength) => {
	result = new Array(expectedLength).fill(0);
	result = numjsAddition(result, groupByJobValue); // INIT VALUE

	try {
		//SUBTRACT ONLY IF THE ID BELONGS TO THE SET(subjobId) TO SUBTRACT
		groupBySubjobValue.forEach(([id, ...values]) => {
			//OTHER HAS A ID = "-1"
			//IF ID = NULL OR SOMETHING EQUIVALENT, CONVERT TO -1
			if (id == null || ["other", "null", ""].includes(id.toLowerCase())) {
				id = "-1";
			}
			if (subjobIdToSubtract.includes(id)) {
				result = numjsSubtraction(result, values);
			}
		});
	} catch (error) {
		console.log(error);
	}
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
	subjobTaskIdToSubtract, // in the form ["s1,t1","s2,t2"...]
	expectedLength
) => {
	let result = new Array(expectedLength).fill(0);
	result = numjsAddition(result, groupByJobValue); // INIT VALUE

	try {
		//SUBTRACT ONLY IF THE ID BELONGS TO THE SET(subjobId) TO SUBTRACT
		//EXCEPT 1: if taskId = other ... where subjobId does not matter
		groupByTaskValue.forEach(([subjobId, taskId, ...values]) => {
			if (subjobId == null || ["other", "null", ""].includes(subjobId.toLowerCase())) {
				subjobId = "-1";
			}
			if (taskId == null || ["other", "null", ""].includes(taskId.toLowerCase())) {
				taskId = "-1";
			}
			let combinedKey = subjobId + "," + taskId;

			subjobTaskIdToSubtract.forEach(key => {
				[targetSubjobId, targetTaskId] = key.split(",");

				// Original Condition or subjob doesnt matter as task "other" is picked
				if ((targetSubjobId == subjobId && targetTaskId == taskId) || (targetTaskId == "-1" && taskId == "-1")) {
					result = numjsSubtraction(result, values);
				}
			});
		});
	} catch (error) {
		console.log(error);
	}
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
const calculateAmountSpent = (payroll, invoicedIn, expenses, expectedLength) => {
	let amountSpent = new Array(expectedLength).fill(0);
	amountSpent = numjsAddition(amountSpent, payroll);
	amountSpent = numjsAddition(amountSpent, invoicedIn);
	amountSpent = numjsAddition(amountSpent, expenses);
	return amountSpent;
};

const convertDisplayToPercentage = (array, max) => {
	//USING NUMJS OPERATIONS TO CALCULATE PERCENTAGE OF THE MAX VALUE
	try {
		return nj
			.array(array)
			.multiply(100)
			.divide(max)
			.tolist();
	} catch (error) {
		console.log("Conversion to Percentage Resulted in Division of 0");
		return [0];
	}
};
