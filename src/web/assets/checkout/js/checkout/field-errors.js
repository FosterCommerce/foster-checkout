/**
 * Field errors from client validation and from the server.
 */
export const fieldValue = (field) =>
	'modelValue' in field ? field.modelValue : field.value;

// Keep a server error until the field's value changes, since validation reruns on input, blur and save
export const setServerErrors = (field, messages) => {
	field.serverErrors = messages;
	field.serverErrorValue = fieldValue(field);
	field.errors = messages;
};

export const setErrors = (field, messages) => {
	const serverErrors =
		field.serverErrorValue === fieldValue(field) ? field.serverErrors : [];
	const clientMessages = messages.filter(Boolean);
	// Show the client message alone, since the server message for the same field would repeat it
	const next = clientMessages.length
		? clientMessages
		: serverErrors.filter(Boolean);

	if (
		Array.isArray(field.errors) &&
		field.errors.length === next.length &&
		field.errors.every((message, index) => message === next[index])
	) {
		return;
	}

	field.errors = next;
};
