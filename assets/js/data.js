"use strict";
const Aviton = (() => {
    let csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
    const $ = id => document.getElementById(id);

    // Use textContent so item names and notes cannot insert HTML into the page.
    const el = (tag, text = "", className = "") => {
        const element = document.createElement(tag);
        element.textContent = text;

        if (className) {
            element.className = className;
        }

        return element;
    };

    // Read the typed XML values returned by the PHP endpoints.
    function decodeValue(node) {
        switch (node.getAttribute("type")) {
        case "null":
            return null;
        case "boolean":
            return node.textContent === "true";
        case "number":
            return Number(node.textContent);
        case "text":
            return node.textContent;
        case "list":
            return Array.from(node.children, entry => decodeValue(entry.firstElementChild));
        case "map":
            {
                const result = Object.create(null);

                for (const entry of node.children) {
                    result[entry.getAttribute("key")] = decodeValue(entry.firstElementChild);
                }

                return result;
            }
        default:
            throw new Error("Invalid server response.");
        }
    }

    async function request(url, payload) {
        let response;

        try {
            response = await fetch(url, {
                method: payload ? "POST" : "GET",
                credentials: "same-origin",
                cache: "no-store",

                headers: payload ? {
                    "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                    "X-CSRF-Token": csrf,
                    Accept: "application/xml"
                } : {
                    Accept: "application/xml"
                },

                body: payload ? new URLSearchParams(payload) : undefined
            });
        } catch {
            throw {
                message: "Connection failed. Check your records before retrying a submitted request.",
                errors: {}
            };
        }

        let body;

        try {
            const document = new DOMParser().parseFromString(await response.text(), "application/xml");

            if (document.querySelector("parsererror") || document.documentElement.tagName !== "response") {
                throw new Error("Invalid response.");
            }

            body = decodeValue(document.documentElement.firstElementChild);
        } catch {
            throw {
                message: "The server did not return a valid response. Check database setup.",
                errors: {}
            };
        }

        if (body.csrf) {
            csrf = body.csrf;
        }

        if (response.status === 401 && document.body.dataset.role) {
            location.assign("index.php");
        }

        if (!response.ok || !body.ok) {
            throw body;
        }

        return body;
    }

    const read = (action, params = {}) => request("read.php?" + new URLSearchParams({
        action,
        ...params
    }));

    function clearErrors(form) {
        form.querySelectorAll("[data-error-for],.form-message").forEach(element => {
            element.textContent = "";
            element.classList.remove("success", "checking");
        });

        form.querySelectorAll("[aria-invalid]").forEach(element => {
            element.removeAttribute("aria-invalid");
            element.classList.remove("is-invalid");
        });
    }

    function errors(form, error) {
        const message = form.querySelector(".form-message");

        if (message) {
            message.textContent = error.message || "Unable to save. Please try again.";
        }

        let first;

        form.querySelectorAll("[data-error-for]").forEach(span => {
            const field = span.dataset.errorFor;

            if (error.errors?.[field]) {
                span.textContent = error.errors[field];
                // A server error replaces any earlier successful field check.
                span.classList.remove("success", "checking");
                const input = form.elements.namedItem(field);

                if (input) {
                    input.setAttribute("aria-invalid", "true");
                    input.classList.add("is-invalid");
                    first ||= input;
                }
            }
        });

        first?.focus();
    }

    function bindForm(form, success) {
        form.addEventListener("reset", () => clearErrors(form));

        form.addEventListener("submit", async event => {
            event.preventDefault();

            if (form.dataset.busy === "true") {
                return;
            }

            clearErrors(form);

            const data = {
                ...Object.fromEntries(new FormData(form)),
                action: form.dataset.action
            };

            const controls = [...form.querySelectorAll("input,textarea,select,button")];
            const disabled = controls.map(control => control.disabled);
            form.dataset.busy = "true";
            form.setAttribute("aria-busy", "true");
            const submit = form.querySelector('[type="submit"]');
            const label = submit.textContent;
            submit.textContent = "Checking…";
            controls.forEach(control => (control.disabled = true));

            try {
                await request("validation.php", data);
                submit.textContent = "Saving…";
                const result = await request("save.php", data);

                if (result.redirect) {
                    if (result.message) {
                        const message = form.querySelector(".form-message");
                        message.textContent = result.message;
                        message.classList.add("success");
                    }

                    location.assign(result.redirect);
                } else {
                    await success?.(result);
                }
            } catch (error) {
                controls.forEach((control, index) => (control.disabled = disabled[index]));
                errors(form, error);
            } finally {
                controls.forEach((control, index) => (control.disabled = disabled[index]));
                submit.textContent = label;
                delete form.dataset.busy;
                form.removeAttribute("aria-busy");
            }
        });
    }

    function notify(message, error = false) {
        const element = $("global-message");
        element.textContent = message;
        element.classList.toggle("error", error);
        element.hidden = false;
    }

    function date(value) {
        if (!value) {
            return "—";
        }

        // Database times are UTC; display them in the browser's local time.
        const timestamp = new Date(value.replace(" ", "T") + "Z");

        return Number.isNaN(timestamp.valueOf()) ? "—" : timestamp.toLocaleString(undefined, {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        });
    }

    // Reuse this token when retrying a borrow request to avoid creating a second loan.
    function randomToken() {
        return [...crypto.getRandomValues(new Uint8Array(16))].map(element => element.toString(16).padStart(2, "0")).join("");
    }

    function openModal(id) {
        const modal = $(id);
        delete modal.dataset.allowClose;
        modal.returnFocus = document.activeElement;

        jQuery(modal).modal({
            backdrop: true,
            keyboard: true,
            show: true
        });
    }

    function closeModal(id, saved = false) {
        const modal = $(id);

        if (saved) {
            modal.dataset.allowClose = "true";
        }

        jQuery(modal).modal("hide");
    }

    function button(label, callback, variant = "outline-secondary") {
        const buttonElement = el("button", label, "btn btn-sm btn-" + variant);
        buttonElement.type = "button";
        buttonElement.addEventListener("click", callback);
        return buttonElement;
    }

    function empty(body, count, message) {
        const row = el("tr");
        const cell = el("td", message, "empty-state");
        cell.colSpan = count;
        row.append(cell);
        body.replaceChildren(row);
    }

    function pager(id, result, change) {
        const node = $(id);

        node.replaceChildren(el(
            "span",
            `${result.total} records · Page ${result.page} of ${Math.max(1, Math.ceil(result.total / result.per_page))}`
        ));

        const previous = button("Previous", () => change(result.page - 1));
        previous.disabled = result.page <= 1;
        const next = button("Next", () => change(result.page + 1));
        next.disabled = result.page * result.per_page >= result.total;
        node.append(previous, next);
    }

    function debounce(callback, delay = 250) {
        let timer;

        return () => {
            clearTimeout(timer);
            timer = setTimeout(callback, delay);
        };
    }

    return {
        $,
        el,
        decodeValue,
        request,
        read,
        clearErrors,
        errors,
        bindForm,
        notify,
        date,
        randomToken,
        openModal,
        closeModal,
        button,
        empty,
        pager,
        debounce
    };
})();

class Dashboard {
    constructor(admin) {
        this.admin = admin;
        this.view = "overview";

        this.pages = {
            overview: 1,
            equipment: 1,
            records: 1,
            reports: 1
        };

        // Track each view's latest request so a slow search cannot replace newer results.
        this.generation = {};

        this.statuses = {
            PENDING: "Awaiting release",
            BORROWED: "On loan",
            RETURN_PENDING: "Awaiting receipt",
            RETURNED: "Returned",
            REJECTED: "Rejected",
            CANCELLED: "Cancelled"
        };

        this.init();
    }

    init() {
        const {
            $,
            bindForm,
            notify
        } = Aviton;

        const sidebar = $("sidebar"), backdrop = $("nav-backdrop"), toggle = $("sidebarToggleTop"), mobile = matchMedia("(max-width:767px)");

        // Keep keyboard focus inside the sidebar while the mobile menu is open.
        const syncNav = () => {
            const open = !sidebar.classList.contains("toggled");
            backdrop.hidden = !(mobile.matches && open);
            toggle.setAttribute("aria-expanded", String(open));
            sidebar.inert = mobile.matches && !open;
            $("content-wrapper").inert = mobile.matches && open;
            document.body.classList.toggle("drawer-open", mobile.matches && open);
        };

        const closeNav = () => {
            if (mobile.matches && !sidebar.classList.contains("toggled")) {
                toggle.click();
                toggle.focus();
            }
        };

        toggle.addEventListener("click", () => {
            syncNav();

            if (mobile.matches && !sidebar.inert) {
                $("sidebar-close").focus();
            }
        });

        backdrop.addEventListener("click", closeNav);
        $("sidebar-close").addEventListener("click", closeNav);

        document.addEventListener("keydown", event => {
            if (event.key === "Escape") {
                closeNav();
            }

            if (event.key === "Tab" && mobile.matches && !sidebar.classList.contains("toggled")) {
                const focusable = [...sidebar.querySelectorAll("a,button")].filter(element => element.getClientRects().length);
                const first = focusable[0], last = focusable.at(-1);

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });

        const resetNav = () => {
            sidebar.classList.toggle("toggled", mobile.matches);
            document.body.classList.toggle("sidebar-toggled", mobile.matches);
            syncNav();
        };

        mobile.addEventListener("change", resetNav);
        window.addEventListener("resize", syncNav);
        resetNav();
        document.body.classList.add("ui-ready");

        document.querySelectorAll("[data-view]").forEach(buttonElement => buttonElement.addEventListener("click", () => {
            this.view = buttonElement.dataset.view;
            document.querySelectorAll(".page-section").forEach(v => (v.hidden = v.id !== buttonElement.dataset.target));

            document.querySelectorAll("[data-view]").forEach(element => {
                element.classList.toggle("active", element === buttonElement);
                element.closest(".nav-item").classList.toggle("active", element === buttonElement);

                if (element === buttonElement) {
                    element.setAttribute("aria-current", "page");
                } else {
                    element.removeAttribute("aria-current");
                }
            });

            $("page-title").textContent = buttonElement.dataset.title;
            closeNav();
            this.refresh();
        }));

        document.querySelectorAll("[data-refresh]").forEach(buttonElement => buttonElement.addEventListener("click", () => this.refresh()));

        $("logout-btn").addEventListener("click", async () => {
            try {
                const result = await Aviton.request("save.php", {
                    action: "logout"
                });

                location.assign(result.redirect);
            } catch (error) {
                notify(error.message, true);
            }
        });

        document.querySelectorAll("[data-close-dialog]").forEach(buttonElement => buttonElement.addEventListener("click", () => {
            Aviton.closeModal(buttonElement.closest(".modal").id);
        }));

        jQuery(".modal").on("hide.bs.modal", function(event) {
            if (this.querySelector('[data-busy="true"]') && !this.dataset.allowClose) {
                event.preventDefault();
            }
        }).on("shown.bs.modal", function() {
            this.querySelector('input:not([type="hidden"]),textarea,button')?.focus();
        }).on("hidden.bs.modal", function() {
            if (this.returnFocus?.isConnected) {
                this.returnFocus.focus();
            } else {
                $("main-content").focus();
            }
        });

        ["equipment-search", "record-search"].forEach(id => $(id).addEventListener("input", Aviton.debounce(() => {
            this.pages[id === "equipment-search" ? "equipment" : "records"] = 1;
            this.refresh();
        })));

        ["record-status", "report-type"].forEach(id => $(id)?.addEventListener("change", () => {
            this.pages = {
                overview: 1,
                equipment: 1,
                records: 1,
                reports: 1
            };

            this.refresh();
        }));

        bindForm($("action-form"), async result => {
            if ($("action-form").dataset.action === "equipment_delete") {
                this.pages = {
                    overview: 1,
                    equipment: 1,
                    records: 1,
                    reports: 1
                };
            }

            Aviton.closeModal("action-dialog", true);
            notify(result.message);
            await this.refresh();
        });

        bindForm($("password-form"));

        if (this.admin) {
            $("add-equipment").addEventListener("click", () => this.editEquipment());

            bindForm($("equipment-form"), async result => {
                Aviton.closeModal("equipment-dialog", true);
                notify(result.message);
                await this.refresh();
            });
        }

        this.refresh();

        // Refresh shared stock while the page is visible, without interrupting an open form.
        setInterval(() => {
            if (!document.hidden && !document.querySelector(".modal.show")) {
                this.refresh();
            }
        }, 30000);

        document.addEventListener("visibilitychange", () => {
            if (!document.hidden && !document.querySelector(".modal.show")) {
                this.refresh();
            }
        });
    }

    async refresh() {
        const tasks = [this.summary()];

        if (this.view === "overview") {
            tasks.push(this.overview());
        }

        if (this.view === "equipment") {
            tasks.push(this.equipment());
        }

        if (this.view === "records") {
            tasks.push(this.records());
        }

        if (this.view === "reports") {
            tasks.push(this.reports());
        }

        const results = await Promise.allSettled(tasks);

        results.forEach(result => {
            if (result.status === "rejected") {
                Aviton.notify(result.reason.message || "Unable to refresh records.", true);
            }
        });
    }

    async summary() {
        const {
            $,
            read
        } = Aviton;

        const result = await read("summary");
        $("stat-total-items").textContent = result.data.total_quantity;
        $("stat-available").textContent = result.data.available_quantity;
        $("stat-borrowed").textContent = result.data.on_loan;
        $("stat-confirmations").textContent = Number(result.data.pending_requests) + Number(result.data.pending_returns);
        $("stat-pending").textContent = result.data.pending_requests;
        $("stat-returns").textContent = result.data.pending_returns;
    }

    async overview() {
        const {
            $,
            read,
            el,
            empty,
            pager
        } = Aviton;

        const requestNumber = this.generation.overview = (this.generation.overview || 0) + 1;
        const body = $("overview-items-body");

        if (!body.children.length) {
            empty(body, 5, "Loading inventory…");
        }

        const result = await read("equipment", {
            page: this.pages.overview
        });

        if (requestNumber !== this.generation.overview) {
            return;
        }

        body.replaceChildren();

        if (!result.data.length) {
            empty(body, 5, "No equipment has been added yet.");
        }

        for (const item of result.data) {
            const available = Number(item.available_quantity), total = Number(item.total_quantity);
            const label = available === 0 ? "Unavailable" : available === total ? "All Available" : "Partly Available";
            const status = el("td");

            status.append(el(
                "span",
                label,
                "status-badge " + (available === 0 ? "badge-unavailable" : available === total ? "badge-available" : "badge-partial")
            ));

            const row = el("tr");

            row.append(
                el("td", item.name, "font-weight-bold"),
                el("td", item.category),
                el("td", item.total_quantity),
                el("td", item.available_quantity),
                status
            );

            body.append(row);
        }

        pager("overview-pagination", result, page => {
            this.pages.overview = page;
            this.refresh();
        });
    }

    async equipment() {
        const {
            $,
            read,
            el,
            button,
            empty,
            pager
        } = Aviton;

        const requestNumber = (this.generation.equipment = (this.generation.equipment || 0) + 1);
        const body = $("equipment-body");

        if (!body.children.length) {
            empty(body, 4, "Loading equipment…");
        }

        const result = await read("equipment", {
            page: this.pages.equipment,
            search: $("equipment-search").value.trim()
        });

        if (this.generation.equipment !== requestNumber) {
            return;
        }

        body.replaceChildren();

        if (!result.data.length) {
            empty(body, 4, "No equipment matches your search.");
        }

        for (const item of result.data) {
            const row = el("tr");
            const name = el("td");
            name.append(el("span", item.name, "cell-title"), el("span", item.code, "cell-meta"));

            if (item.description) {
                name.append(el("span", item.description, "cell-meta"));
            }

            const category = el("td");
            category.append(el("span", item.category), el("span", item.location || "No location set", "cell-meta"));
            const quantity = el("td", `${item.available_quantity} / ${item.total_quantity}`);
            const actions = el("td");
            const group = el("div", "", "cell-actions");

            if (this.admin) {
                group.append(
                    button("Edit", () => this.editEquipment(item), "outline-primary"),
                    button("Delete", () => this.action("equipment_delete", item), "outline-danger"),
                    button("History", () => this.history("equipment_history", item.id))
                );
            } else {
                const borrow = button("Request borrow", () => this.action("borrow_request", item), "primary");
                borrow.disabled = Number(item.available_quantity) < 1;
                group.append(borrow);
            }

            actions.append(group);
            row.append(name, category, quantity, actions);
            body.append(row);
        }

        pager("equipment-pagination", result, page => {
            this.pages.equipment = page;
            this.refresh();
        });
    }

    async records() {
        const {
            $,
            read,
            el,
            button,
            empty,
            pager,
            date
        } = Aviton;

        const requestNumber = (this.generation.records = (this.generation.records || 0) + 1);
        const body = $("records-body");

        if (!body.children.length) {
            empty(body, this.admin ? 7 : 6, "Loading records…");
        }

        const result = await read("records", {
            page: this.pages.records,
            search: $("record-search").value.trim(),
            status: $("record-status").value
        });

        if (this.generation.records !== requestNumber) {
            return;
        }

        body.replaceChildren();

        if (!result.data.length) {
            empty(body, this.admin ? 7 : 6, "No borrow records match this filter.");
        }

        for (const record of result.data) {
            const row = el("tr"), name = el("td");

            name.append(
                el("span", record.equipment_name, "cell-title"),
                el("span", `#${record.id} · ${record.equipment_code}`, "cell-meta")
            );

            if (record.note) {
                name.append(el("span", record.note, "cell-meta"));
            }

            row.append(name);

            if (this.admin) {
                const owner = el("td");
                owner.append(el("span", record.borrower_name, "cell-title"), el("span", "@" + record.username, "cell-meta"));
                row.append(owner);
            }

            row.append(el("td", record.quantity + (Number(record.quantity) === 1 ? " unit" : " units")));
            const borrowed = el("td", "", "cell-dates"), returned = el("td", "", "cell-dates");

            borrowed.append(
                el("div", date(record.borrowed_at)),
                el("span", "Requested: " + date(record.requested_at), "cell-meta")
            );

            returned.append(el("div", date(record.returned_at)));

            if (record.return_requested_at) {
                returned.append(el("span", "Return requested: " + date(record.return_requested_at), "cell-meta"));
            }

            const state = el("td");
            state.append(el("span", this.statuses[record.status], "status-badge status-" + record.status));
            row.append(borrowed, returned, state);
            const actionCell = el("td"), group = el("div", "", "cell-actions");

            if (this.admin && record.status === "PENDING") {
                group.append(
                    button("Confirm release", () => this.action("approve_borrow", record), "primary"),
                    button("Reject", () => this.action("reject_borrow", record), "outline-danger")
                );
            }

            if (this.admin && record.status === "RETURN_PENDING") {
                group.append(
                    button("Confirm receipt", () => this.action("confirm_return", record), "primary"),
                    button("Reject return", () => this.action("reject_return", record), "outline-danger")
                );
            }

            if (!this.admin && record.status === "PENDING") {
                group.append(button("Cancel", () => this.action("cancel_request", record), "outline-danger"));
            }

            if (!this.admin && record.status === "BORROWED") {
                group.append(button("Request return", () => this.action("request_return", record), "primary"));
            }

            group.append(button("History", () => this.history("record_history", record.id)));
            actionCell.append(group);
            row.append(actionCell);
            body.append(row);
        }

        pager("records-pagination", result, page => {
            this.pages.records = page;
            this.refresh();
        });
    }

    action(action, item) {
        const {
            $,
            clearErrors,
            randomToken
        } = Aviton;

        const form = $("action-form");
        form.reset();
        clearErrors(form);
        form.dataset.action = action;
        const isBorrow = action === "borrow_request", isDelete = action === "equipment_delete";

        const title = {
            borrow_request: "Request equipment",
            approve_borrow: "Confirm physical handover",
            reject_borrow: "Reject borrow request",
            cancel_request: "Cancel borrow request",
            request_return: "Submit all units for return",
            confirm_return: "Confirm physical receipt",
            reject_return: "Reject return request",
            equipment_delete: "Permanently delete equipment"
        };

        const rejection = ["reject_borrow", "reject_return"].includes(action);
        form.elements.note.required = rejection;
        $("note-field").querySelector(".form-label").textContent = rejection ? "Reason" : "Note (optional)";
        $("note-field").querySelector(".field-hint").textContent = rejection ? "Explain why this request cannot be confirmed." : "Add context for the transaction record, if needed.";
        $("action-title").textContent = title[action];
        $("action-submit").textContent = title[action];
        $("action-submit").classList.toggle("btn-danger", isDelete);
        $("action-submit").classList.toggle("btn-primary", !isDelete);
        $("quantity-field").hidden = !isBorrow;
        $("note-field").hidden = isDelete;
        form.elements.record_id.value = isBorrow || isDelete ? "" : item.id;
        form.elements.equipment_id.value = isBorrow || isDelete ? item.id : "";
        form.elements.version.value = item.version || "";
        form.elements.quantity.value = "1";
        form.elements.request_token.value = isBorrow ? randomToken() : "";

        const descriptions = {
            borrow_request: `${item.name}: ${item.available_quantity} units currently available. A request is not a reservation. Staff must confirm the handover.`,
            approve_borrow: `Confirm that ${item.borrower_name} has physically received all ${item.quantity} units of ${item.equipment_name}. This reduces available stock.`,
            confirm_return: `Confirm you have received all ${item.quantity} units of ${item.equipment_name} from ${item.borrower_name}. This restores available stock.`,
            request_return: `Return all ${item.quantity} units of ${item.equipment_name} to staff. Your loan remains open until an administrator confirms receipt.`,
            equipment_delete: `Permanently delete ${item.name} (${item.code}) and all ${item.total_quantity} units from inventory? Its equipment change history will also be removed. This cannot be undone. Items with any borrowing records cannot be deleted.`,
            reject_borrow: "Explain why this request cannot be fulfilled.",
            reject_return: "Explain why receipt cannot be confirmed. The loan will remain active.",
            cancel_request: "Cancel this pending request? No stock has been reserved or released."
        };

        $("action-description").textContent = descriptions[action];
        Aviton.openModal("action-dialog");
    }

    async editEquipment(item) {
        const {
            $,
            clearErrors,
            read,
            notify
        } = Aviton;

        const form = $("equipment-form");
        form.reset();
        clearErrors(form);

        try {
            if (item) {
                const response = await read("equipment_get", {
                    id: item.id
                });

                item = response.data[0];

                if (!item) {
                    throw {
                        message: "Equipment no longer exists."
                    };
                }
            }

            form.elements.equipment_id.value = item?.id || "0";
            form.elements.version.value = item?.version || "0";

            for (const key of ["code", "name", "category", "location", "description", "total_quantity"]) {
                form.elements[key].value = item?.[key] ?? (key === "total_quantity" ? "0" : "");
            }

            $("equipment-title").textContent = item ? "Edit equipment" : "Add equipment";
            Aviton.openModal("equipment-dialog");
        } catch (error) {
            notify(error.message, true);
        }
    }

    async history(type, id) {
        const {
            $,
            read,
            el,
            date
        } = Aviton;

        const content = $("history-content");
        content.replaceChildren(el("p", "Loading history…"));
        $("history-title").textContent = type === "record_history" ? "Transaction history" : "Equipment change history";
        Aviton.openModal("history-dialog");

        try {
            const result = await read(type, {
                id
            });

            content.replaceChildren();

            if (!result.data.length) {
                content.append(el("p", "No changes recorded. This item may be part of the initial catalog."));
            }

            for (const event of result.data) {
                const node = el("article", "", "history-event");

                node.append(
                    el("strong", event.event_type.replaceAll("_", " ")),
                    el("small", `${date(event.created_at)} · ${event.actor}`)
                );

                if (event.note) {
                    node.append(el("p", event.note));
                }

                if (event.details) {
                    node.append(el("pre", event.details));
                }

                content.append(node);
            }
        } catch (error) {
            content.replaceChildren(el("p", error.message));
        }
    }

    async reports() {
        const {
            $,
            read,
            el,
            empty,
            pager,
            date
        } = Aviton;

        const type = $("report-type").value;
        const requestNumber = (this.generation.reports = (this.generation.reports || 0) + 1);

        const definitions = {
            report_equipment: {
                fields: [
                    ["code", "Code"],
                    ["name", "Equipment"],
                    ["total_quantity", "Total"],
                    ["available_quantity", "Available"],
                    ["requests", "Requests"],
                    ["released_units", "Released units"],
                    ["on_loan", "On loan"]
                ]
            },

            report_users: {
                fields: [
                    ["username", "Username"],
                    ["name", "Name"],
                    ["role", "Role"],
                    ["requests", "Requests"],
                    ["on_loan", "Units on loan"],
                    ["last_borrowed_at", "Last borrowed"]
                ]
            },

            report_full: {
                fields: [
                    ["equipment_id", "Equipment ID"],
                    ["code", "Code"],
                    ["name", "Equipment"],
                    ["released_units", "Released units"],
                    ["match_type", "Match"]
                ]
            }
        };

        const def = definitions[type];

        const result = await read(type, {
            page: this.pages.reports
        });

        if (this.generation.reports !== requestNumber) {
            return;
        }

        const head = el("tr");
        def.fields.forEach(([, label]) => head.append(el("th", label)));
        $("report-head").replaceChildren(head);
        const body = $("report-body");
        body.replaceChildren();

        if (!result.data.length) {
            empty(body, def.fields.length, "No report data.");
        }

        for (const record of result.data) {
            const tableRow = el("tr");
            def.fields.forEach(([key]) => {
                const value = key.endsWith("_at") ? date(record[key]) : String(record[key] ?? "—");
                tableRow.append(el("td", value));
            });
            body.append(tableRow);
        }

        pager("report-pagination", result, page => {
            this.pages.reports = page;
            this.refresh();
        });
    }
}
