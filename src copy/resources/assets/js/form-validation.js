$(document).ready(function () {
    if (!formFields || !$("#form-validation").length) return;

    let rules = {};
    let messages = {};

    // Build rules dynamically
    formFields.forEach(function (field) {
        let fieldRules = (field.rules || "").split("|");
        rules[field.name] = {};
        messages[field.name] = {};

        fieldRules.forEach(function (rule) {
            // Password optional on edit
            if (field.type === "password" && recordId) {
                if (rule === "required") return;
            } else if (rule === "required") {
                rules[field.name].required = true;
                messages[field.name].required = "Please enter " + field.label;
            }

            // Email validation
            if (rule === "email") {
                rules[field.name].email = true;
                messages[field.name].email =
                    "Please enter a valid " + field.label;
            }

            // Min length
            if (rule.startsWith("min:")) {
                let minLength = parseInt(rule.split(":")[1]);
                rules[field.name].minlength = minLength;
                messages[field.name].minlength =
                    field.label +
                    " must be at least " +
                    minLength +
                    " characters";
            }

            // Max length
            if (rule.startsWith("max:")) {
                let maxLength = parseInt(rule.split(":")[1]);
                rules[field.name].maxlength = maxLength;
                messages[field.name].maxlength =
                    field.label +
                    " must be at most " +
                    maxLength +
                    " characters";
            }

            // Unique (remote) validation
            // Unique JS remote validation
            if (rule === "email") {
                rules[field.name].remote = {
                    url: "/email/verification",
                    type: "get",
                    data: {
                        table: tableName,
                        id: recordId,
                        email: function () {
                            return $("#" + field.name).val(); // send current email value
                        },
                    },
                    dataFilter: function (response) {
                        let res = JSON.parse(response);
                        return res === true ? "true" : '"Email already exists"';
                    },
                };
                messages[field.name].remote = field.label + " already exists";
            }
        });
    });

    // Initialize validation
    $("#form-validation").validate({
        rules: rules,
        messages: messages,
        errorClass: "text-danger",
        errorElement: "label",
        highlight: function (element) {
            // optional: add red border
            $(element).addClass("is-invalid");
        },
        unhighlight: function (element) {
            $(element).removeClass("is-invalid");
        },
        submitHandler: function (form) {
            // Prevent default and submit normally
            form.submit();
        },
    });
});
