(function () {
    var root = document.documentElement;
    var btn = document.getElementById("themeBtn");
    function icon() {
        if (btn)
            btn.innerHTML =
                '<i class="bi ' +
                (root.dataset.theme === "glass" ? "bi-sun" : "bi-moon-stars") +
                '"></i>';
    }
    icon();
    if (btn)
        btn.addEventListener("click", function () {
            root.dataset.theme =
                root.dataset.theme === "glass" ? "light" : "glass";
            try {
                localStorage.setItem("theme", root.dataset.theme);
            } catch (e) {}
            icon();
        });
    function toast(t, type) {
        var e = document.createElement("div");
        e.className = "toast" + (type ? " " + type : "");
        e.innerHTML =
            '<i class="bi bi-' +
            (type === "err" ? "exclamation-circle" : "check-circle") +
            '" aria-hidden="true"></i><span></span>';
        e.lastChild.textContent = t;
        (document.getElementById("toasts") || document.body).appendChild(e);
        setTimeout(function () {
            e.remove();
        }, 3200);
    }
    function fieldError(el, msg) {
        var old = el.parentNode.querySelector(".err-msg");
        if (old) old.remove();
        var id = "err" + Date.now(),
            m = document.createElement("p");
        m.className = "err-msg";
        m.id = id;
        m.innerHTML =
            '<i class="bi bi-exclamation-circle" aria-hidden="true"></i>';
        m.appendChild(document.createTextNode(msg));
        el.after(m);
        el.setAttribute("aria-invalid", "true");
        el.setAttribute("aria-describedby", id);
        el.focus();
        el.addEventListener(
            "input",
            function () {
                el.removeAttribute("aria-invalid");
                el.removeAttribute("aria-describedby");
                m.remove();
            },
            { once: true },
        );
        el.addEventListener(
            "change",
            function () {
                el.removeAttribute("aria-invalid");
                m.remove();
            },
            { once: true },
        );
    }

    function esc(t) {
        var d = document.createElement("div");
        d.textContent = t;
        return d.innerHTML;
    }

    // Demo modals for pages that are now real Livewire components (Macros, Team,
    // Tags, Email domains, Developer webhooks, Fields, Tracking events, Workflows)
    // were removed on purpose. Only demo modals for still-static pages remain below.
    var MODALS = {};

    function P(t, c) {
        return (
            '<span class="pill' + (c ? " " + c : "") + '">' + esc(t) + "</span>"
        );
    }
    function av(n) {
        return (
            '<span class="av">' +
            esc(n.charAt(0).toUpperCase()) +
            "</span>" +
            esc(n)
        );
    }
    var M = MODALS;
    M["+ Add contact"] = {
        title: "Add contact",
        fields: [
            ["Name", "Full name"],
            ["Email", "name@example.com"],
            ["Country", "Bangladesh"],
        ],
        row: function (v) {
            return [
                av(v[0]),
                esc(v[1]),
                esc(v[2] || "-"),
                P("Lead"),
                "Just now",
            ];
        },
    };
    M["+ Create flow"] = {
        title: "Create flow",
        fields: [
            ["Flow name", "Welcome visitors"],
            ["Trigger", ["Opens site", "Exit intent", "Time on page"]],
        ],
        row: function (v) {
            return [esc(v[0]), esc(v[1]), "0", P("Draft")];
        },
    };
    M["+ New action"] = {
        title: "New action",
        fields: [
            ["Action name", "Check order status"],
            ["Type", ["API call", "Built in"]],
        ],
        row: function (v) {
            return [esc(v[0]), esc(v[1]), P("Off")];
        },
    };
    M["+ Connect MCP server"] = {
        title: "Connect MCP server",
        fields: [["Server name or URL", "orders-mcp"]],
        row: function (v) {
            return [esc(v[0]), "0", P("Connecting")];
        },
    };
    M["+ New procedure"] = {
        title: "New procedure",
        fields: [["Procedure name", "Refund a purchase"]],
        row: function (v) {
            return [esc(v[0]), "0", P("Draft")];
        },
    };
    M["Create API client"] = {
        title: "Create API client",
        fields: [["Client name", "My integration"]],
        done:
            "API client created: loop_live_" +
            Math.random().toString(36).slice(2, 10),
    };
    M["Import"] = {
        title: "Import contacts",
        fields: [["CSV file", "@file"]],
        done: "Import started (demo)",
    };
    // Still used by the static AI-agent "Roles" page (the "Goal" variant).
    // The Team → Roles variant below is dead now that Team is Livewire, but harmless.
    M["+ New role"] = function () {
        var h = document.querySelector(".card table th:nth-child(2)");
        if (h && h.textContent.trim() === "Goal")
            return {
                title: "New role",
                fields: [
                    ["Role name", "Sales assistant"],
                    ["Goal", "Help visitors choose a plan"],
                ],
                row: function (v) {
                    return [esc(v[0]), esc(v[1] || "-"), P("Off")];
                },
            };
        return {
            title: "New role",
            fields: [
                ["Role name", "Support lead"],
                ["Can manage billing", ["No", "Yes"]],
            ],
            row: function (v) {
                return [
                    esc(v[0]),
                    "0",
                    v[1] === "Yes" ? P("Yes", "ok") : P("No"),
                ];
            },
        };
    };
    function openModal(cfg) {
        if (typeof cfg === "function") cfg = cfg();
        var ov = document.createElement("div");
        ov.className = "ov";
        var h =
            '<div class="mdl" role="dialog" aria-modal="true" aria-labelledby="mdlT"><h3 id="mdlT">' +
            cfg.title +
            "</h3>";
        cfg.fields.forEach(function (f) {
            h +=
                "<label>" +
                f[0] +
                "</label>" +
                (f[1] === "@text"
                    ? '<textarea class="f" rows="4"></textarea>'
                    : f[1] === "@file"
                      ? '<input class="f" type="file">'
                      : Array.isArray(f[1])
                        ? '<select class="f">' +
                          f[1]
                              .map(function (o) {
                                  return "<option>" + o + "</option>";
                              })
                              .join("") +
                          "</select>"
                        : '<input class="f" placeholder="' + f[1] + '">');
        });
        h +=
            '<div class="act"><button class="btn" data-x="1">Cancel</button><button class="btn pri" data-ok="1">Save</button></div></div>';
        ov.innerHTML = h;
        document.body.appendChild(ov);
        var first = ov.querySelector("input,select,textarea");
        if (first) first.focus();
        function close() {
            ov.remove();
        }
        function save() {
            var els = ov.querySelectorAll("input,select,textarea"),
                v = [].map.call(els, function (e) {
                    return e.value.trim();
                });
            if (
                (!v[0] && els[0].type !== "file") ||
                (els[0].type === "file" && !els[0].files.length)
            ) {
                fieldError(els[0], "This field is required.");
                return;
            }
            var table = document.querySelector(".card table");
            if (cfg.row && table) {
                var tr = document.createElement("tr");
                tr.innerHTML = cfg
                    .row(v)
                    .map(function (c) {
                        return "<td>" + c + "</td>";
                    })
                    .join("");
                table.appendChild(tr);
            }
            if (cfg.onSave) cfg.onSave(v);
            close();
            toast(cfg.msg || cfg.done || "Added (demo)");
        }
        ov.addEventListener("click", function (e) {
            if (e.target === ov || e.target.dataset.x) close();
            if (e.target.dataset.ok) save();
        });
        ov.addEventListener("keydown", function (e) {
            if (e.key === "Escape") close();
            if (e.key === "Enter") save();
        });
    }

    var lastBtn = null,
        noteMode = false;
    var FILTER = {
        title: "Filter contacts",
        fields: [["Tag", ["All", "Lead", "Customer", "Trial"]]],
        msg: "Filter applied",
        onSave: function (v) {
            document
                .querySelectorAll(".card table tr")
                .forEach(function (tr, i) {
                    if (!i) return;
                    tr.style.display =
                        v[0] === "All" || tr.textContent.indexOf(v[0]) > -1
                            ? ""
                            : "none";
                });
        },
    };
    var RANGE = {
        title: "Date range",
        fields: [["Range", ["Last 7 days", "Last 30 days", "Last 90 days"]]],
        msg: "Range updated",
        onSave: function (v) {
            if (lastBtn)
                lastBtn.innerHTML = '<i class="bi bi-calendar3"></i> ' + v[0];
            document.querySelectorAll(".bars i").forEach(function (i) {
                i.style.height = 25 + Math.floor(Math.random() * 70) + "%";
            });
            document.querySelectorAll(".kpi b").forEach(function (k) {
                if (/^\d+$/.test(k.textContent.trim()))
                    k.textContent = Math.floor(Math.random() * 400) + 20;
            });
        },
    };
    var PLAN = {
        title: "Choose a plan",
        fields: [["Plan", ["Starter · $19", "Growth · $29", "Pro · $79"]]],
        msg: "Plan updated",
        onSave: function (v) {
            var c = document.querySelector(".two .card");
            if (!c) return;
            var p = v[0].split(" · ");
            c.querySelector("h3").textContent = p[0] + " plan";
            var ps = c.querySelector("p");
            if (ps) ps.textContent = "Billed at " + p[1] + " per month.";
            var pill = c.querySelector(".pill");
            if (pill) pill.textContent = "Active";
        },
    };
    var ASSIGN = {
        title: "Assign conversation",
        fields: [
            [
                "Assign to",
                ["Unassigned", "Amina Rahman", "Rafi Hasan", "Sara Khan"],
            ],
        ],
        msg: "Assigned",
        onSave: function (v) {
            if (lastBtn) lastBtn.textContent = v[0];
        },
    };
    var ANSWER = {
        title: "Add answer",
        fields: [["Answer", "@text"]],
        msg: "Answer added to Lyro",
        onSave: function () {
            var tr = lastBtn && lastBtn.closest("tr");
            if (tr) tr.remove();
        },
    };
    var DEV = {
        title: "Send to developer",
        fields: [["Developer email", "dev@example.com"]],
        msg: "Instructions sent",
    };
    function botReply() {
        var msgs = document.querySelector(".msgs");
        if (!msgs) return;
        var m = document.createElement("div");
        m.className = "m";
        m.textContent = "Thanks for asking! This is a demo answer from Lyro.";
        msgs.appendChild(m);
        msgs.scrollTop = msgs.scrollHeight;
    }
    var sw = document.querySelectorAll(".sw i"),
        prev = document.querySelector(".prev");
    if (sw.length && prev) {
        sw.forEach(function (i) {
            i.addEventListener("click", function () {
                prev.style.setProperty("--cobalt", i.style.background);
            });
        });
        var ins = document.querySelectorAll(".two .card input.f");
        if (ins[0])
            ins[0].addEventListener("input", function () {
                prev.querySelector(".pw header b").textContent = ins[0].value;
            });
        if (ins[1])
            ins[1].addEventListener("input", function () {
                prev.querySelector(".pw .m").textContent = ins[1].value;
            });
    }
    function send() {
        var box = document.querySelector(".reply input"),
            msgs = document.querySelector(".msgs");
        if (!box || !msgs || !box.value.trim()) return;
        var m = document.createElement("div");
        m.className = "m me" + (noteMode ? " note" : "");
        m.textContent = (noteMode ? "Note: " : "") + box.value.trim();
        msgs.appendChild(m);
        box.value = "";
        msgs.scrollTop = msgs.scrollHeight;
        if (noteMode) {
            noteMode = false;
            box.placeholder = "Type a message…";
            var nb = document.querySelector(".reply .btn:not(.pri)");
            if (nb) nb.classList.remove("pri");
        } else if (document.querySelector(".card .msgs"))
            setTimeout(botReply, 500);
    }

    // A button is "owned" by Livewire if it has any wire:* attribute itself, or
    // sits inside a form that has one (e.g. <form wire:submit> with a plain
    // <button type="submit">). The demo layer must not touch those, otherwise it
    // opens fake modals, adds fake rows, disables submit buttons, etc.
    function isLivewireBtn(b) {
        function hasWire(el) {
            return Array.prototype.some.call(el.attributes, function (a) {
                return a.name.indexOf("wire:") === 0;
            });
        }
        if (hasWire(b)) return true;
        var f = b.closest("form");
        return !!(f && hasWire(f));
    }

    // Livewire components call $this->dispatch('toast', message: '...').
    // That fires a browser event on window; show it with the same toast() helper.
    window.addEventListener("toast", function (e) {
        var d = e.detail || {};
        if (Array.isArray(d)) d = d[0] || {};
        toast(d.message || "Done", d.type);
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Enter" && e.target.matches(".reply input")) {
            e.preventDefault();
            send();
        }
    });
    document.addEventListener("click", function (e) {
        if (e.target.closest(".ov")) return;
        var conv = e.target.closest(".conv");
        if (conv) {
            conv.parentNode.querySelectorAll(".conv").forEach(function (c) {
                c.classList.remove("on");
            });
            conv.classList.add("on");
            var h = document.querySelector(".chat header b"),
                msgs = document.querySelector(".msgs");
            if (h) h.textContent = conv.querySelector("b").textContent;
            if (msgs) {
                var s = conv.querySelector("small");
                msgs.innerHTML = "";
                var m = document.createElement("div");
                m.className = "m";
                m.textContent = s ? s.textContent : "";
                msgs.appendChild(m);
            }
            return;
        }
        var b = e.target.closest("button.btn");
        if (!b) return;
        if (isLivewireBtn(b)) return;
        var t = b.textContent.trim();
        if (MODALS[t]) {
            openModal(MODALS[t]);
            return;
        }
        if (t === "Filter") {
            lastBtn = b;
            openModal(FILTER);
            return;
        }
        if (b.querySelector(".bi-calendar3")) {
            lastBtn = b;
            openModal(RANGE);
            return;
        }
        if (t === "Upgrade") {
            location.href = "settings-billing.html";
            return;
        }
        if (t === "Choose a plan") {
            openModal(PLAN);
            return;
        }
        if (t === "Skip" && b.closest(".setup")) {
            b.closest(".setup").style.display = "none";
            toast("Setup hidden");
            return;
        }
        if (t === "Add internal note") {
            noteMode = !noteMode;
            b.classList.toggle("pri", noteMode);
            var bx = document.querySelector(".reply input");
            if (bx) {
                bx.placeholder = noteMode
                    ? "Write an internal note…"
                    : "Type a message…";
                bx.focus();
            }
            return;
        }
        if (b.closest(".chat header") && /Unassigned|Amina|Rafi|Sara/.test(t)) {
            lastBtn = b;
            openModal(ASSIGN);
            return;
        }
        if (t === "Add answer") {
            lastBtn = b;
            openModal(ANSWER);
            return;
        }
        if (t === "Send to developer") {
            openModal(DEV);
            return;
        }
        if (t === "Block") {
            var bi = document.querySelector(".tools input");
            if (bi && bi.value.trim()) {
                var tb = document.querySelector(".card table"),
                    r = document.createElement("tr");
                r.innerHTML =
                    "<td>" +
                    esc(bi.value.trim()) +
                    '</td><td>Today</td><td><button class="btn">Unblock</button></td>';
                tb.appendChild(r);
                bi.value = "";
                toast("Address blocked");
            } else {
                toast("Enter an email address to block.", "err");
            }
            return;
        }
        if (t === "Unblock") {
            var tr2 = b.closest("tr");
            if (tr2) tr2.remove();
            toast("Address unblocked");
            return;
        }
        if (t === "Regenerate") {
            var ki = document.querySelector("input[readonly]");
            if (ki)
                ki.value =
                    "loop_live_" + Math.random().toString(36).slice(2, 14);
            toast("New API key generated");
            return;
        }
        if (/^Connect (Messenger|Instagram|WhatsApp|mailbox)/.test(t)) {
            b.textContent = "Connected ✓";
            b.disabled = true;
            toast("Connected (demo)");
            return;
        }
        if ((b.closest(".reply") && t === "Send") || t === "Send email") {
            send();
            return;
        }
        if (t === "Copy code") {
            var pre = document.querySelector("pre");
            if (pre && navigator.clipboard)
                navigator.clipboard.writeText(pre.textContent);
            toast("Code copied");
            return;
        }
        if (
            /^Save|^Change|^Connect|^Block|^Unblock|^Regenerate|^Download|^Use template|^Send to|^Add answer/.test(
                t,
            )
        ) {
            toast("Done (demo)");
            return;
        }
        if (/^\+|^Create|^Import|^Choose|^Add |^Invite|^Skip/.test(t)) {
            toast("Demo only: not connected yet");
        }
    });
})();
