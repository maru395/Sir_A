"use strict";
var registrationChecks = Object.create(null);

function cancelFieldCheck(field) {
    var pending = registrationChecks[field];
    delete registrationChecks[field];

    if (!pending) {
        return;
    }

    clearTimeout(pending.timer);

    if (pending.request) {
        pending.request.abort();
    }

    pending.resolve(false);
}

// Each input calls this to show its error beside the field.
function checkField(field, value, hintId, mode) {
    var hint = document.getElementById(hintId);
    var input = document.getElementById(field);

    if (!hint || !input) {
        return Promise.resolve(false);
    }

    var form = input.form;

    if (form.dataset.busy === "true" && mode !== "submit") {
        return Promise.resolve(false);
    }

    // Spaces can be part of a password. Only trim the other fields.
    value = String(value === null || value === undefined ? "" : value);

    if (field !== "password" && field !== "confirm_password") {
        value = value.trim();
    }

    cancelFieldCheck(field);

    // Recheck confirmation when the original password changes.
    if (field === "password" && mode !== "submit" && form.elements.confirm_password.value !== "") {
        checkField("confirm_password", form.elements.confirm_password.value, "confirmPasswordHint", "format");
    }

    var checkMode = mode === "submit" ? (field === "username" ? "availability" : "format") : (mode || "format");
    hint.textContent = field === "username" && checkMode === "availability" ? "Checking if this username is available…" : "Checking your entry…";
    hint.classList.remove("success");
    hint.classList.add("checking");
    input.classList.remove("is-invalid");
    input.removeAttribute("aria-invalid");
    input.setAttribute("aria-busy", "true");

    return new Promise(function(resolve) {
        var state = {
            resolve: resolve,
            timer: null,
            request: null
        };

        registrationChecks[field] = state;

        function finish(valid, message) {
            // Ignore an old reply if the user has already typed something else.
            if (registrationChecks[field] !== state) {
                return;
            }

            delete registrationChecks[field];
            hint.textContent = message;
            hint.classList.remove("checking");
            hint.classList.toggle("success", valid && message !== "");
            input.removeAttribute("aria-busy");
            input.classList.toggle("is-invalid", !valid);

            if (valid) {
                input.removeAttribute("aria-invalid");
            } else {
                input.setAttribute("aria-invalid", "true");
            }

            resolve(valid);
        }

        // Wait briefly while typing so every keystroke does not send a request.
        state.timer = setTimeout(function() {
            var xmlhttp = new XMLHttpRequest();
            state.request = xmlhttp;

            xmlhttp.onreadystatechange = function() {
                if (xmlhttp.readyState !== 4 || registrationChecks[field] !== state) {
                    return;
                }

                var raw = xmlhttp.responseText || "";

                if (xmlhttp.status === 200 && raw === "VALID") {
                    var messages = {
                        first_name: "Looks good.",
                        last_name: "Looks good.",
                        email: "Email format looks good.",
                        mobile: value === "" ? "" : "Mobile number format looks good.",
                        password: "Password length looks good.",
                        confirm_password: "Your passwords match.",
                        // A format check alone does not tell us whether a username is taken.
                        username: checkMode === "availability" ? "This username is available." : ""
                    };
                    finish(true, messages[field]);
                } else if (xmlhttp.status === 403) {
                    finish(false, "Please refresh this page, then enter your details again.");
                } else if (xmlhttp.status === 429) {
                    finish(false, "Please wait a little before trying again.");
                } else {
                    finish(false, raw.startsWith("INVALID|") ? raw.slice(8) : "We cannot check this right now. Please try again in a moment.");
                }
            };

            xmlhttp.onerror = xmlhttp.ontimeout = function() {
                finish(false, "We could not connect. Check your connection and try again.");
            };

            var body = new URLSearchParams({
                field: field,
                value: value,
                mode: checkMode
            });

            if (field === "confirm_password") {
                body.set("password", form.elements.password.value);
            }

            try {
                xmlhttp.open("POST", "validate_registration.php", true);
                xmlhttp.timeout = 15000;
                xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xmlhttp.setRequestHeader("X-CSRF-Token", document.querySelector('meta[name="csrf-token"]').content);
                xmlhttp.send(body.toString());
            } catch {
                finish(false, "We could not connect. Check your connection and try again.");
            }
        }, mode === "format" ? 250 : 0);
    });
}

function registrationRequest(url, body) {
    return new Promise(function(resolve, reject) {
        // A lost reply after saving does not mean the account was not created.
        var connectionMessage = url === "save.php"
            ? "We could not confirm whether your account was created. Try signing in before submitting again."
            : "We could not check your details. Check your connection and try again.";
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.open("POST", url, true);
        xmlhttp.timeout = 15000;
        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xmlhttp.setRequestHeader("X-CSRF-Token", document.querySelector('meta[name="csrf-token"]').content);

        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState !== 4) {
                return;
            }

            try {
                var doc = new DOMParser().parseFromString(xmlhttp.responseText, "application/xml");

                if (doc.querySelector("parsererror") || doc.documentElement.tagName !== "response") {
                    throw new Error();
                }

                var result = Aviton.decodeValue(doc.documentElement.firstElementChild);

                if (result.csrf) {
                    document.querySelector('meta[name="csrf-token"]').content = result.csrf;
                }

                if (xmlhttp.status >= 200 && xmlhttp.status < 300 && result.ok) {
                    resolve(result);
                } else {
                    if (xmlhttp.status === 403) {
                        result.message = "Please refresh this page, then enter your details again.";
                    } else if (xmlhttp.status === 429) {
                        result.message = "There have been several attempts. Please wait a while before trying again.";
                    } else if (xmlhttp.status >= 500) {
                        result.message = "We cannot complete your request right now. Please try again in a moment.";
                    }
                    reject(result);
                }
            } catch {
                reject({
                    message: connectionMessage
                });
            }
        };

        xmlhttp.onerror = xmlhttp.ontimeout = function() {
            reject({
                message: connectionMessage
            });
        };

        xmlhttp.send(body.toString());
    });
}

// Keep the form on this page until every AJAX check has passed.
function validateFormBeforeSubmit(formId) {
    var form = document.getElementById(formId);

    if (!form || form.dataset.busy === "true") {
        return false;
    }

    var fields = Array.from(form.querySelectorAll("input[aria-describedby]"));
    var controls = Array.from(form.querySelectorAll("input, button"));

    var disabled = controls.map(function(input) {
        return input.disabled;
    });

    // Read the values first: disabled inputs are left out of FormData.
    var body = new URLSearchParams(new FormData(form));

    var button = form.querySelector('[type="submit"]');
    var label = button.textContent;
    Aviton.clearErrors(form);
    form.dataset.busy = "true";
    form.setAttribute("aria-busy", "true");

    // Prevent edits and double-clicks while this submission is being checked.
    controls.forEach(function(input) {
        input.disabled = true;
    });

    button.textContent = "Checking your details…";

    (async function() {
        try {
            var valid = await Promise.all(fields.map(function(input) {
                var hint = form.querySelector('[data-error-for="' + input.name + '"]');
                return checkField(input.name, input.value, hint.id, "submit");
            }));

            if (!valid.every(Boolean)) {
                form.querySelector(".form-message").textContent = "Please check the messages beside the highlighted fields and try again.";
                return;
            }

            // Field checks help the user; the server still checks the whole form before saving.
            await registrationRequest("validation.php", body);

            button.textContent = "Creating your account…";
            var result = await registrationRequest("save.php", body);
            form.querySelector(".form-message").textContent = result.message;
            form.querySelector(".form-message").classList.add("success");

            if (result.redirect) {
                location.assign(result.redirect);
            }
        } catch (error) {
            Aviton.errors(form, error);
        } finally {
            controls.forEach(function(input, index) {
                input.disabled = disabled[index];
            });

            delete form.dataset.busy;
            form.removeAttribute("aria-busy");
            button.textContent = label;
            form.querySelector('[aria-invalid="true"]')?.focus();
        }
    })();

    return false;
}

function resetRegistrationValidation(formId) {
    var form = document.getElementById(formId);

    if (!form) {
        return;
    }

    Object.keys(registrationChecks).forEach(cancelFieldCheck);
    Aviton.clearErrors(form);

    form.querySelectorAll("input[aria-busy]").forEach(function(input) {
        input.removeAttribute("aria-busy");
    });
}
