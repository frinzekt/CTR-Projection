const numjsAddition = (array1, array2, round = true) => {
	//ADDS ARRAY2 TO ARRAY1 IF ARRAY2 IS AN ARRAY AND NOT EMPTY
	array1 = nj.array(array1);
	if (!(!Array.isArray(array2) || !array2.length)) {
		array1 = array1.add(nj.array(array2));
	}
	return array1.tolist();
};
const numjsSubtraction = (array1, array2, round = true) => {
	//ADDS ARRAY2 TO ARRAY1 IF ARRAY2 IS AN ARRAY AND NOT EMPTY
	array1 = nj.array(array1);
	if (!(!Array.isArray(array2) || !array2.length)) {
		array1 = array1.subtract(nj.array(array2));
	}
	return array1.tolist();
};

const roundArray = (array, precision) => {
	try {
		return array.map(x => x.toFixed(precision));
	} catch (e) {
		if (e instanceof TypeError) {
			return array;
		}
	}
};

//REVIEW  NEEDS TESTING
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
