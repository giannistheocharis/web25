// ================= SweetAlert Helpers =================

function swalSuccess(text, title = "Επιτυχία") {
    return Swal.fire({
        icon: "success",
        title,
        text,
        confirmButtonText: "ΟΚ"
    });
}

function swalError(text, title = "Σφάλμα") {
    return Swal.fire({
        icon: "error",
        title,
        text,
        confirmButtonText: "ΟΚ"
    });
}

function swalInfo(text, title = "Ενημέρωση") {
    return Swal.fire({
        icon: "info",
        title,
        text,
        confirmButtonText: "ΟΚ"
    });
}

function swalConfirm(text, onConfirm, title = "Είσαι σίγουρος;") {
    Swal.fire({
        title,
        text,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ναι",
        cancelButtonText: "Άκυρο"
    }).then(result => {
        if (result.isConfirmed) onConfirm();
    });
}
function swalMsg(msg) {
    Swal.fire({
        icon: 'warning',
        title: 'Προσοχή',
        text: msg,
        confirmButtonText: 'OK'
    });
}
let allStudents = [];
let selectedStudents = null;
let assignThesisId = null;
let activeIndex = -1;
let filteredStudents = [];
let allTheses = [];
function showDashboard() {
  document.getElementById('dashboard').style.display = 'block';
  document.getElementById('theses').style.display = 'none';
}

function showTheses() {
  document.getElementById('dashboard').style.display = 'none';
  document.getElementById('theses').style.display = 'block';
}

// Helper για δημιουργία HTML elements
function el(tag, attrs = {}, children = []) {
    const e = document.createElement(tag);
    Object.entries(attrs).forEach(([k, v]) => {
        if (k === "class") e.className = v;
        else if (k === "html") e.innerHTML = v;
        else e.setAttribute(k, v);
    });
    (Array.isArray(children) ? children : [children])
        .filter(Boolean)
        .forEach(ch => e.appendChild(typeof ch === "string" ? document.createTextNode(ch) : ch));
    return e;
}

let currentThesisId = null;
let currentRole = null;

/*---------------------------------------------------------
  1) ΦΟΡΤΩΣΗ ΠΤΥΧΙΑΚΩΝ ΚΑΘΗΓΗΤΗ
---------------------------------------------------------*/
function loadTheses() {

    fetch("../backend/teachers/list_theses.php")
        .then(res => res.json())
        .then(theses => {

            // Κρατάμε ΜΟΝΟ πτυχιακές που συμμετέχει ο καθηγητής
            allTheses = theses.filter(t => t.thesis_status !== "available");

            // Αρχικό render (χωρίς φίλτρα)
            renderThesesTable(allTheses);

            // Κάτω: διαθέσιμα θέματα
            loadTopics();
        })
        .catch(err => {
            console.error("Σφάλμα φόρτωσης πτυχιακών:", err);
        });
}

function renderThesesTable(list) {

    const tbody = document.querySelector("#theses_table tbody");
    tbody.innerHTML = "";

    // Αν δεν υπάρχουν αποτελέσματα
    if (!list || list.length === 0) {
        const tr = el("tr");
        tr.appendChild(
            el("td", { colspan: 5 }, "Δεν βρέθηκαν αποτελέσματα.")
        );
        tbody.appendChild(tr);
        return;
    }

    // Γέμισμα πίνακα
    list.forEach(t => {

        const tr = el("tr");

        // Φοιτητής
        tr.appendChild(
            el("td", {}, t.student || "-")
        );

        // Θέμα
        tr.appendChild(
            el("td", {}, t.topic_title)
        );

        // Ρόλος
        tr.appendChild(
            el("td", {}, t.role || "-")
        );

        // Κατάσταση (badge ως HTML)
        tr.appendChild(
            el("td", { html: statusBadge(t.thesis_status) })
        );

        // Ενέργειες
        const tdActions = el("td");

        const viewBtn = el(
            "button",
            { class: "btn-small" },
            "Προβολή"
        );
        viewBtn.onclick = () => showDetails(t.id);
        tdActions.appendChild(viewBtn);

        // Ακύρωση ΜΟΝΟ αν pending
        if (t.thesis_status === "pending") {
            const cancelBtn = el(
                "button",
                {
                    class: "btn-small btn-danger",
                    style: "margin-left:6px"
                },
                "Ακύρωση"
            );
            cancelBtn.onclick = () => cancelAssignment(t.id);
            tdActions.appendChild(cancelBtn);
        }

        tr.appendChild(tdActions);
        tbody.appendChild(tr);
    });
}

function cancelAssignment(thesis_id){
    swalConfirm(
        "Ακύρωση ανάθεσης και επιστροφή στα διαθέσιμα;",
        () => {
            fetch("../backend/teachers/cancel_assignment.php",{
                method:"POST",
                headers:{"Content-Type":"application/json"},
                body: JSON.stringify({ thesis_id })
            })
            .then(r=>r.json())
            .then(d=>{
                if(d.success){
                    swalSuccess("✔ Η εργασία επέστρεψε στα διαθέσιμα θέματα!");
                    loadTheses();
                    loadTopics();
                }
            });
        }
    );
}
function applyThesisFilters() {
  const status = document.getElementById("filter-status").value;
  const role   = document.getElementById("filter-role").value;

  let list = allTheses;

  if (status) {
    list = list.filter(t => t.thesis_status === status);
  }

  if (role) {
    list = list.filter(t => t.role === role);
  }

  renderThesesTable(list);
}

/*---------------------------------------------------------
  2) ΛΕΠΤΟΜΕΡΕΙΕΣ ΠΤΥΧΙΑΚΗΣ / ΣΗΜΕΙΩΣΕΙΣ / DRAFTS / ΒΑΘΜΟΣ
---------------------------------------------------------*/
function showDetails(thesisId) {
    const area = document.getElementById("details_area");
    if (!area) return;

    // toggle close
    if (currentThesisId === thesisId) {
        area.innerHTML = "";
        area.style.display = "none";
        currentThesisId = null;
        return;
    }

    currentThesisId = thesisId;
    area.style.display = "block";
    area.innerHTML = "<p>Φόρτωση...</p>";

    fetch(`../backend/teachers/get_thesis_details.php?id=${thesisId}`)
        .then(r => r.json())
        .then(t => {

            area.innerHTML = "";

            if (!t) {
                area.innerHTML = "<p>Δεν υπάρχουν στοιχεία</p>";
                return;
            }

            /* =====================================================
               ⛔ SPECIAL CASE: ΑΚΥΡΩΜΕΝΗ
               ===================================================== */
            if (t.thesis_status === "canceled") {

                area.appendChild(el("h3", {}, `Θέμα: ${t.title}`));
                area.appendChild(el("p", {}, `Φοιτητής: ${t.student_name}`));

                const pStatus = el("p");
                pStatus.append("Κατάσταση: ");
                pStatus.append(
                    el("span", { style: "color:red;font-weight:bold" }, "ΑΚΥΡΩΜΕΝΗ")
                );
                area.appendChild(pStatus);

                area.appendChild(el("hr"));

                area.appendChild(
                    el("p", {}, `Λόγος ακύρωσης: ${t.cancel_reason || "—"}`)
                );

                area.appendChild(
                    el(
                        "p",
                        {},
                        `Ημερομηνία ακύρωσης: ${
                            t.canceled_at
                                ? new Date(t.canceled_at).toLocaleDateString("el-GR")
                                : "—"
                        }`
                    )
                );

                if (t.committee && t.committee.length) {
                    area.appendChild(el("hr"));
                    area.appendChild(el("h4", {}, "Μέλη Επιτροπής"));
                    t.committee.forEach(m => {
                        area.appendChild(
                            el("p", {}, `👤 ${m.name} (${m.status})`)
                        );
                    });
                }

                return; // ⬅️ ΜΟΝΟ ΕΔΩ ΣΤΑΜΑΤΑΜΕ
            }

            /* =====================================================
               ✅ ΟΛΕΣ ΟΙ ΑΛΛΕΣ ΚΑΤΑΣΤΑΣΕΙΣ
               ===================================================== */

            // role από list_theses
            const th = allTheses.find(x => x.id === thesisId);
            currentRole = th ? th.role : null;

            // ===== ΒΑΣΙΚΑ =====
            area.appendChild(el("h3", {}, `Θέμα: ${t.title}`));
            area.appendChild(el("p", {}, `Φοιτητής: ${t.student_name}`));
            area.appendChild(el("p", {}, `Κατάσταση: ${t.thesis_status}`));
            area.appendChild(el("p", {}, `Περίληψη: ${t.abstract || "-"}`));

            // ===== ΕΠΙΤΡΟΠΗ =====
            if (t.committee && t.committee.length) {
                area.appendChild(el("h4", {}, "Μέλη Επιτροπής"));
                t.committee.forEach(m => {
                    area.appendChild(
                        el("p", {}, `👤 ${m.name} (${m.status})`)
                    );
                });
            }

            // ===== PDF =====
            if (t.pdf_path) {
                area.appendChild(
                    el(
                        "p",
                        {},
                        el(
                            "a",
                            { href: "../" + t.pdf_path, target: "_blank" },
                            "📄 Τελικό PDF Πτυχιακής"
                        )
                    )
                );
            }

            // ===== ΚΟΥΜΠΙΑ ΡΟΗΣ =====
            const btns = el("div", { class: "btn-row" });

            if (t.thesis_status === "pending" && currentRole === "Supervisor") {
                mkBtn(btns, "Έγκριση", "primary", () => updateStatus("approved"));
                mkBtn(btns, "Απόρριψη", "danger", () => updateStatus("rejected"));
            }

            if (t.thesis_status === "active" && currentRole === "Supervisor") {
                mkBtn(btns, "🧪 Έναρξη Εξέτασης", "warning", () =>
                    updateStatus("under_exam")
                );
            }

            if (
                (t.thesis_status === "active" || t.thesis_status === "under_exam") &&
                currentRole === "Supervisor"
            ) {
                mkBtn(btns, "❌ Ακύρωση Πτυχιακής", "danger", () =>
                    cancelThesisWithReason(t.id)
                );
            }

            area.appendChild(btns);

            // ===== EXAM INFO =====
            if (t.thesis_status === "under_exam") {
                showExamInfoForTeacher(t);
            } else {
                const examBox = document.getElementById("exam_info_box");
                if (examBox) examBox.style.display = "none";
            }
            // ===== ANNOUNCEMENT =====
         // ===== ANNOUNCEMENT =====
                    if (t.exam_date && t.exam_time) {

                        area.appendChild(el("hr"));
                        area.appendChild(el("h4", {}, "Ανακοίνωση Παρουσίασης"));

                        // 👉 ΑΝ ΥΠΑΡΧΕΙ ΗΔΗ ΑΝΑΚΟΙΝΩΣΗ → ΜΟΝΟ ΠΡΟΒΟΛΗ
                        if (t.presentation_announcement) {

                            area.appendChild(
                                el(
                                    "div",
                                    {
                                        style: "background:#f5f5f5;padding:12px;border-radius:6px"
                                    },
                                    t.presentation_announcement
                                )
                            );

                        // 👉 ΑΝ ΔΕΝ ΥΠΑΡΧΕΙ → ΜΟΝΟ ΤΟΤΕ ΕΜΦΑΝΙΖΕΤΑΙ ΤΟ ΠΛΑΙΣΙΟ
                        } else if (currentRole === "Supervisor") {

                            const ta = el("textarea", {
                                id: "announcement_input",
                                style: "width:100%;height:120px"
                            });

                            const btn = el(
                                "button",
                                {
                                    class: "btn-small btn-primary",
                                    style: "margin-top:6px"
                                },
                                "Αποθήκευση Ανακοίνωσης"
                            );

                            btn.onclick = () => {
                                fetch("/web2025/backend/teachers/save_announcement.php", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/x-www-form-urlencoded"
                                    },
                                    body:
                                        `thesis_id=${t.id}` +
                                        `&announcement=${encodeURIComponent(ta.value)}`
                                })
                                .then(r => r.json())
                                .then(() => {
                                    swalMsg("Η ανακοίνωση αποθηκεύτηκε");
                                    showDetails(t.id); // 🔁 refresh για να φύγει το textarea
                                });
                            };

                            area.appendChild(ta);
                            area.appendChild(btn);
                        }
                    }



            // ===== GRADING STATUS =====
            let gradingBox = document.getElementById("grading_status_box");

            if (t.thesis_status === "under_exam" || t.thesis_status === "completed") {

                if (!gradingBox) {
                    gradingBox = document.createElement("div");
                    gradingBox.id = "grading_status_box";
                    gradingBox.style.marginTop = "20px";
                    area.appendChild(gradingBox);
                }

                gradingBox.style.display = "block";
                loadGradingStatus(thesisId);

            } else if (gradingBox) {
                gradingBox.style.display = "none";
            }

            // ===== DRAFTS =====
            const draftsBox = el("div", { style: "margin-top:20px" }, [
                el("h4", {}, "Πρόχειρες Εκδόσεις (Drafts)"),
                el("div", { id: "drafts_list" }, "Φόρτωση...")
            ]);
            area.appendChild(draftsBox);
            loadThesisDrafts(thesisId);

            // ===== NOTES =====
            const notesBox = document.createElement("div");
            notesBox.style.marginTop = "20px";
            notesBox.innerHTML = `
                <h4>Προσωπικές Σημειώσεις</h4>
                <textarea id="teacher_note_input"
                    style="width:100%;height:70px"
                    maxlength="300"></textarea><br>
                <button class="btn-small btn-primary"
                    id="save_teacher_note_btn">Αποθήκευση</button>
                <div id="teacher_notes_list">Φόρτωση...</div>
            `;
            area.appendChild(notesBox);

            loadTeacherNotes(thesisId);

            document.getElementById("save_teacher_note_btn").onclick = () => {
                const txt = document
                    .getElementById("teacher_note_input")
                    .value.trim();

                if (!txt) return swalMsg("Γράψτε κάτι στη σημείωση.");
                saveTeacherNote(thesisId, txt);
            };

            // ===== Grade Section =====
            const gradeSection = document.createElement("div");
            gradeSection.id = "gradeSection";
            gradeSection.style.display = "none";
            gradeSection.style.marginTop = "10px";
            gradeSection.innerHTML = `
                <h3>Βαθμολόγηση</h3>
                <input type="number"
                    id="gradeInput"
                    min="0"
                    max="10"
                    step="0.5"
                    placeholder="Εισαγωγή βαθμολογίας (0–10)">
                <button type="button" onclick="submitGrade()">Καταχώρηση</button>
                <p id="gradeStatus"></p>
            `;
            area.appendChild(gradeSection);

        })
        .catch(err => {
            console.error(err);
            area.innerHTML = "<p>Σφάλμα φόρτωσης δεδομένων.</p>";
        });
}

function loadGradingStatus(thesis_id) {
    fetch(`../backend/teachers/get_grading_status.php?thesis_id=${thesis_id}`)
        .then(r => r.json())
        .then(list => {

            const box = document.getElementById("grading_status_box");
            if (!box) return;

            const gradeBox = document.getElementById("gradeSection");

            // αν δεν υπάρχει επιτροπή
            if (!list || list.length === 0) {
                box.innerHTML = "<p><i>Δεν υπάρχει επιτροπή.</i></p>";
                if (gradeBox) gradeBox.style.display = "none";
                return;
            }

            let html = `
                <h4>Κατάσταση Βαθμολόγησης Επιτροπής</h4>
                <table class="grading-table">
                    <tr>
                        <th>Καθηγητής</th>
                        <th>Βαθμός</th>
                        <th>Κατάσταση</th>
                    </tr>
            `;

            let allGraded = true;

            list.forEach(m => {
                if (!m.graded) allGraded = false;

                html += `
                    <tr>
                        <td>${m.fullname}</td>
                        <td>${m.grade ?? "-"}</td>
                        <td>${m.graded ? "✔ Υποβλήθηκε" : "⏳ Εκκρεμεί"}</td>
                    </tr>
                `;
            });

            html += "</table>";

            if (allGraded) {
                html += `
                    <p style="margin-top:10px;color:green;font-weight:bold">
                        🎉 Όλοι οι καθηγητές βαθμολόγησαν
                    </p>
                `;
            }

            box.innerHTML = html;

            // ===== ΜΟΝΟ ΕΔΩ ο έλεγχος εμφάνισης βαθμού =====
            const me = list.find(m => m.is_me === true);

            if (gradeBox) {
                if (me && !me.graded) {
                    gradeBox.style.display = "block";
                } else {
                    gradeBox.style.display = "none";
                }
            }
        });
}


function mkBtn(parent,text,type,fn){
    const b=el("button",{class:`btn-${type} btn-small`,style:"margin-right:5px"},text);
    b.onclick=fn;
    parent.appendChild(b);
}

function updateStatus(status){
    swalConfirm("Σίγουρα;", () => {
    fetch("../backend/teachers/update_status.php",{
        method:"POST",
        headers:{"Content-Type":"application/json"},
        body:JSON.stringify({thesis_id:currentThesisId,status})
    })
    .then(r=>r.json())
    .then(()=>{
        swalSuccess("ΟΚ");
        loadTheses();
        showDetails(currentThesisId);
    });
});
}
/*---------------------------------------------------------
  2a) ΣΗΜΕΙΩΣΕΙΣ ΔΙΔΑΣΚΟΝΤΑ
  - Εμφανίζονται ΜΟΝΟ στον δημιουργό (φιλτράρισμα γίνεται στο PHP)
---------------------------------------------------------*/
function loadTeacherNotes(thesis_id) {
    fetch("../backend/teachers/get_notes.php?thesis_id=" + thesis_id)
    .then(r => r.json())
    .then(list => {
        const box = document.getElementById("teacher_notes_list");
        if (!box) return;

        box.innerHTML = "";

        if (!list || list.length === 0) {
            box.innerHTML = "<i>Δεν υπάρχουν σημειώσεις.</i>";
            return;
        }

        list.forEach(n => {
            const div = document.createElement("div");
            div.className = "note-box";
            div.style.background = "#f5f5f5";
            div.style.borderRadius = "6px";
            div.style.padding = "8px";
            div.style.marginBottom = "6px";

            div.innerHTML = `
                <div style="font-size:12px;color:#777;">${n.created_at}</div>
                <div>${n.note}</div>
            `;
            box.appendChild(div);
        });
    });
}

function saveTeacherNote(thesis_id, note) {
    fetch("../backend/teachers/save_note.php", {
        method:"POST",
        headers:{"Content-Type":"application/json"},
        body: JSON.stringify({ thesis_id, note })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            const input = document.getElementById("teacher_note_input");
            if (input) input.value = "";
            loadTeacherNotes(thesis_id);
        } else {
            swalError("Σφάλμα: " + (d.message || "Κατά την αποθήκευση σημείωσης."));
        }
    });
}

/*---------------------------------------------------------
  2b) DRAFTS ΦΟΙΤΗΤΗ (PDF + LINKS)
---------------------------------------------------------*/
function loadThesisDrafts(thesis_id) {
    fetch("../backend/teachers/get_drafts.php?thesis_id=" + thesis_id)
    .then(r => r.json())
    .then(list => {
        const box = document.getElementById("drafts_list");
        if (!box) return;

        box.innerHTML = "";

        if (!list || list.length === 0) {
            box.innerHTML = "<i>Δεν έχουν ανέβει drafts.</i>";
            return;
        }

        list.forEach(draft => {
            const div = document.createElement("div");
            div.style.background = "#f4f4f4";
            div.style.padding = "8px";
            div.style.marginBottom = "6px";
            div.style.borderRadius = "6px";

            let html = `<div style="font-size:12px;color:#777;">${draft.uploaded_at}</div>`;

            if (draft.file_name) {
                // Προσαρμόζεις το path αν τα ανεβάζεις αλλού
                html += `<a href="../uploads/${draft.file_name}" target="_blank">📄 ${draft.file_name}</a><br>`;
            }

            if (draft.link) {
                html += `<a href="${draft.link}" target="_blank">🔗 Σύνδεσμος</a><br>`;
            }

            div.innerHTML = html;
            box.appendChild(div);
        });
    });
}
function submitEditTopic() {
    document.getElementById("editTopicForm").dispatchEvent(
        new Event("submit", { cancelable: true })
    );
}

/*---------------------------------------------------------
  2c) ΥΠΟΒΟΛΗ ΒΑΘΜΟΥ (χρησιμοποιεί το gradeSection στο HTML)
---------------------------------------------------------*/
function submitGrade() {
    if (!currentThesisId) {
        swalError("Δεν έχει επιλεγεί πτυχιακή.");
        return;
    }
    const inp = document.getElementById("gradeInput");
    if (!inp) {
        swalError("Δεν βρέθηκε πεδίο βαθμού.");
        return;
    }
    const value = parseFloat(inp.value);
    if (isNaN(value) || value < 0 || value > 10) {
        swalError("Ο βαθμός πρέπει να είναι μεταξύ 0 και 10.");
        return;
    }

    fetch("../backend/teachers/save_grade.php",{
        method:"POST",
        headers:{"Content-Type":"application/json"},
        body: JSON.stringify({ thesis_id: currentThesisId, grade: value })
    })
    .then(r=>r.json())
   .then(d => {
    const status = document.getElementById("gradeStatus");

    if (d.success) {
        if (d.completed) {
            status.textContent = "✔ Όλοι βαθμολόγησαν. Τελικός βαθμός: " + d.final_grade;
        } else {
            status.textContent = "🕒 Ο βαθμός σας καταχωρήθηκε. Αναμονή βαθμών από τα υπόλοιπα μέλη.";
        }

        loadTheses();
        showDetails(currentThesisId);
    } else {
        status.textContent = "❌ " + d.message;
    }
});
}

/*---------------------------------------------------------
  3) ΠΡΟΤΕΙΝΟΜΕΝΑ ΘΕΜΑΤΑ ΚΑΘΗΓΗΤΗ (LOAD-DISPLAY-EDIT-DELETE)
---------------------------------------------------------*/
function loadTopics(){
    fetch("../backend/teachers/list_topics.php")
    .then(r=>r.json())
    .then(list=>{
        const ul=document.getElementById("topics_list");
        ul.innerHTML="";

        if(!list || list.length===0){
            ul.innerHTML="<li>Δεν έχετε προτείνει θέματα.</li>";
            return;
        }

        // μόνο διαθέσιμα
        list = list.filter(t => t.status === "available");

        list.forEach(t=>{
            const li = el("li",{class:"topic-card"},[
                el("strong",{},t.title+" "),
                el("div",{class:"topic-desc"},t.description)
            ]);

            // Επεξεργασία
            const editBtn = el("button",{class:"btn-small"},"✏ Επεξεργασία");
            editBtn.onclick = ()=>editTopic(t.id);
            li.appendChild(editBtn);

            // Διαγραφή
            const delBtn = el("button",{class:"btn-small btn-danger",style:"margin-left:6px"},"🗑 Διαγραφή");
            delBtn.onclick = ()=>deleteTopic(t.id);
            li.appendChild(delBtn);

            // Ανάθεση
            const assignBtn = el("button",{class:"btn-small",style:"margin-left:6px"},"👤 Ανάθεση");
            assignBtn.onclick = ()=>openAssignModal(t.id);
            li.appendChild(assignBtn);

            ul.appendChild(li);
        });
    });
}

/*---------------------------------------------------------
  Επεξεργασία Topic (Modal)
---------------------------------------------------------*/
function editTopic(id){
    document.getElementById("editTopicModal").style.display="flex";

    fetch("../backend/teachers/get_topic_details.php?id="+id)
    .then(r=>r.json())
    .then(topic=>{
        if(!topic){ swalError("Δεν βρέθηκε το θέμα"); return; }

        document.getElementById("edit_topic_id").value=topic.id;
        document.getElementById("edit_topic_title").value=topic.title;
        document.getElementById("edit_topic_desc").value=topic.description;

            document.getElementById("existing_pdf").innerHTML =
        topic.pdf_path
            ? `<a href="/web2025/${topic.pdf_path}" target="_blank">📄 PDF</a>`
            : "(κανένα PDF)";

    });
}

function closeEditModal(){
    document.getElementById("editTopicModal").style.display="none";
}

document.getElementById("editTopicForm").addEventListener("submit",e=>{
    e.preventDefault();
    let form=new FormData(editTopicForm);

    fetch("../backend/teachers/update_topic.php",{
        method:"POST",body:form
    }).then(r=>r.json()).then(()=>{
        swalSuccess("Αποθηκεύτηκε");
        closeEditModal();
        loadTopics();
    });
});

/*---------------------------------------------------------
  Δημιουργία νέου Topic με Upload PDF
---------------------------------------------------------*/
function initTopicForm() {
    const form = document.getElementById("topic_form");
    const msg  = document.getElementById("topic_msg");
    const btn  = document.getElementById("save_topic_btn");

    btn.addEventListener("click", () => {

        // validation (για required fields)
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const fd = new FormData(form);

        fetch("../backend/teachers/save_topic.php", {
            method: "POST",
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            msg.textContent = data.message;
            msg.style.color = data.success ? "green" : "red";

            if (data.success) {
                form.reset();
                loadTopics();
            }
        })
        .catch(err => {
            msg.textContent = "Σφάλμα αποθήκευσης";
            msg.style.color = "red";
            console.error(err);
        });
    });
}

/*---------------------------------------------------------
  DELETE TOPIC
---------------------------------------------------------*/
function deleteTopic(id){
    swalConfirm("Διαγραφή θέματος;", () => {
        fetch("../backend/teachers/delete_topic.php",{
            method:"POST",
            headers:{"Content-Type":"application/x-www-form-urlencoded"},
            body:"id="+id
        }).then(r=>r.text()).then(resp=>{
            if(resp==="ok") loadTopics();
        });
    });
}

/*---------------------------------------------------------
  ASSIGN STUDENT MODAL
---------------------------------------------------------*/
function openAssignModal(id){
  assignThesisId = id;

  document.getElementById("assign_topic_id").value = id;
  document.getElementById("assign_results").innerHTML = "";
  document.getElementById("assign_selected_name").innerHTML = "";
  document.getElementById("assign_search").value = "";

  selectedStudent = null;
  activeIndex = -1;

  document.getElementById("assignModal").style.display = "flex";

  fetch("../backend/teachers/list_students.php")
    .then(r => r.json())
    .then(list => {
      allStudents = list;
      renderStudents(allStudents);
      document.getElementById("assign_search").style.display = "block";
    });
}

function renderStudents(list){
  const div = document.getElementById("assign_results");
  div.innerHTML = "";
  activeIndex = -1;

  list.forEach((st, idx) => {
    const opt = document.createElement("div");
    opt.className = "student-option";
    opt.textContent = `${st.name} ${st.surname} (${st.student_number})`;

    opt.onclick = () => selectStudent(st);

    div.appendChild(opt);
  });
}
function selectStudent(student){
    selectedStudent = student;
    document.getElementById("assign_selected_name").innerHTML=
        `Επιλεγμένος: <b>${student.name} ${student.surname}</b>`;
}
function highlight(){
  document.querySelectorAll(".student-option")
    .forEach((el,i)=>el.classList.toggle("active", i === activeIndex));
}
function navigateAssignResults(direction){
    if(!allStudents || allStudents.length===0) return;
    activeIndex+=direction;
    if(activeIndex<0) activeIndex=allStudents.length-1;
    if(activeIndex>=allStudents.length) activeIndex=0;
    renderStudents(allStudents);
}
function selectActiveStudent(){
    if(!allStudents || allStudents.length===0) return;
    selectedStudent=allStudents[activeIndex];
    document.getElementById("assign_selected_name").innerHTML=
        `Επιλεγμένος: <b>${selectedStudent.name}</b>`;
}

function closeAssignModal(){
    document.getElementById("assignModal").style.display="none";
}


const searchInput = document.getElementById("assign_search");

searchInput.addEventListener("input", () => {
  const q = searchInput.value.toLowerCase();

  filteredStudents = allStudents.filter(st =>
    `${st.name} ${st.surname} ${st.student_number}`
      .toLowerCase()
      .includes(q)
  );

  activeIndex = -1;

  const results = document.getElementById("assign_results");
  results.style.display = "flex";

  renderStudents(filteredStudents);
});



searchInput.addEventListener("keydown", e => {
  if (!filteredStudents.length) return;

  if (e.key === "ArrowDown") {
    activeIndex = (activeIndex + 1) % filteredStudents.length;
    highlight();
    e.preventDefault();
  }

  if (e.key === "ArrowUp") {
    activeIndex =
      (activeIndex - 1 + filteredStudents.length) % filteredStudents.length;
    highlight();
    e.preventDefault();
  }

  if (e.key === "Enter" && activeIndex >= 0) {
    selectStudent(filteredStudents[activeIndex]);
    e.preventDefault();
  }
});

function highlight(){
  const items = document.querySelectorAll(".student-option");
  items.forEach((el,i)=>el.classList.toggle("active", i === activeIndex));
}


document.getElementById("assign_final_btn").onclick=()=>{
    if(!selectedStudent) return swalMsg("Επίλεξε φοιτητή!");
    assignTopicToStudent(assignThesisId,selectedStudent.id);
}

function assignTopicToStudent(topic_id, student_id){

    fetch("../backend/teachers/assign_topic.php", {
        method:"POST",
        headers:{"Content-Type":"application/json"},
        body: JSON.stringify({ topic_id, student_id })
    })
    .then(r=>r.json())
    .then(d=>{
        if(!d.success){
            swalError("❌ Σφάλμα ανάθεσης: " + d.message);
            return;
        }

        swalSuccess("✔ Το θέμα ανατέθηκε!");
        
        loadTheses();   // πάει πάνω στις ενεργές
        loadTopics();   // εξαφανίζεται από κάτω (status=assigned)
        closeAssignModal();
    });
}

/*---------------------------------------------------------
  ΠΡΟΣΚΛΗΣΕΙΣ ΕΠΙΤΡΟΠΗΣ
---------------------------------------------------------*/
function loadTeacherInvites(){
    fetch("../backend/teachers/get_invites.php")
        .then(r => r.json())
        .then(invites => {

            const ul = document.getElementById("teacher_invites");
            ul.innerHTML = "";

            if (!invites || !invites.length) {
                ul.innerHTML = "<li><i>Δεν υπάρχουν προσκλήσεις.</i></li>";
                return;
            }

            invites.forEach(inv => {

                // 🔧 ADAPTER (χωρίς αλλαγή backend)
                const status = inv.status ?? inv.invite_status ?? "pending";

                const li = document.createElement("li");

                li.innerHTML = `
                    <b>${inv.student_name}</b>
                    — Θέμα: <i>${inv.thesis_title}</i>
                    — <span style="color:${
                        status === 'accepted' ? 'green' :
                        status === 'rejected' ? 'red' : 'orange'
                    }">${status}</span>
                `;

                // 🔹 Προβολή
                const viewBtn = document.createElement("button");
                viewBtn.textContent = "Προβολή Πτυχιακής";
                viewBtn.style.marginLeft = "10px";
                viewBtn.onclick = async () => {
                    
                    openInvitePopup(inv.invite_id ?? inv.id);
                };
                li.appendChild(viewBtn);

                // 🔹 Accept / Reject ΜΟΝΟ αν pending
                if (status === "pending") {
                    console.log("INV OBJECT:", inv);


                    const accBtn = document.createElement("button");
                    accBtn.textContent = "Αποδοχή";
                    accBtn.style.marginLeft = "10px";
                    accBtn.onclick = () =>
                        respondInvite(inv.invite_id ?? inv.id, "accept");

                    const rejBtn = document.createElement("button");
                    rejBtn.textContent = "Απόρριψη";
                    rejBtn.style.marginLeft = "5px";
                    rejBtn.onclick = () =>
                        respondInvite(inv.invite_id ?? inv.id, "reject");

                    li.appendChild(accBtn);
                    li.appendChild(rejBtn);
                }

                ul.appendChild(li);
            });
        })
        .catch(err => {
            console.error("Σφάλμα φόρτωσης προσκλήσεων:", err);
        });
}

function openInvitePopup(inviteId) {
    fetch(`../backend/teachers/get_invite_details.php?id=${inviteId}`)
        .then(r => r.json())
        .then(inv => {
            if (inv.error) {
                alert("Invite not found");
                return;
            }
            renderInvitePopup(inv);
        });
}
function renderInvitePopup(inv) {

    let committeeHTML = "<i>Δεν υπάρχει επιτροπή.</i>";

    if (inv.committee && inv.committee.length) {
        committeeHTML = `
            <table style="width:100%;text-align:left">
                <tr>
                    <th>Διδάσκων</th><th>Ρόλος</th><th>Κατάσταση</th>
                    <th>Πρόσκληση</th><th>Αποδοχή</th><th>Απόρριψη</th>
                </tr>
                ${inv.committee.map(m => `
                    <tr>
                        <td>${m.name}</td>
                        <td>${m.role}</td>
                        <td>${m.status}</td>
                        <td>${m.sent_at ?? "-"}</td>
                        <td>${m.accepted_at ?? "-"}</td>
                        <td>${m.rejected_at ?? "-"}</td>
                    </tr>
                `).join("")}
            </table>
        `;
    }

    Swal.fire({
        title: "Πρόσκληση Επιτροπής",
        width: 750,
        html: `
            <p><b>Φοιτητής:</b> ${inv.student_name} ${inv.student_surname}</p>
            <p><b>Θέμα:</b> ${inv.thesis_title}</p>
            <p><b>Κατάσταση:</b> ${inv.thesis_status}</p>
            <hr>
            <p>${inv.abstract ?? "—"}</p>
            <hr>
            ${committeeHTML}
        `,
        showConfirmButton: inv.status === "pending",
        showDenyButton: inv.status === "pending",
        confirmButtonText: "Αποδοχή",
        denyButtonText: "Απόρριψη",
        showCancelButton: true
    }).then(r => {
        if (r.isConfirmed) respondInvite(inv.id, "accept");
        if (r.isDenied) respondInvite(inv.id, "reject");
    });
    console.log("INVITE OBJECT:", inv);

}


function respondInvite(id, action){
    console.log("=== RESPOND INVITE CLICK ===");
    console.log("ID που στέλνω:", id);
    console.log("ACTION:", action);

    fetch("../backend/teachers/answer_invite.php",{
        method:"POST",
        headers:{ "Content-Type": "application/x-www-form-urlencoded" },
        body:`invite_id=${id}&action=${action}`
    })
    .then(r => r.text())
    .then(txt => {
        console.log("RAW RESPONSE:", txt);

        let d;
        try {
            d = JSON.parse(txt);
        } catch (e) {
            console.error("❌ ΔΕΝ ΕΙΝΑΙ JSON");
            return;
        }

        console.log("PARSED RESPONSE:", d);

        if (d.error) {
            swalError(d.error);
            return;
        }

        swalSuccess("Ενημερώθηκε ✔");
        loadTeacherInvites();
        loadTheses();
    });
}


/*---------------------------------------------------------
  STATUS BADGE
---------------------------------------------------------*/
function statusBadge(status){
    const map = {
        pending: "Σε αναμονή",
        active: "Ενεργή",
        under_exam: "Υπό εξέταση",
        completed: "Ολοκληρωμένη",
        canceled: "Ακυρωμένη"
    };

    return `
        <span class="status-badge status-${status}">
            ${map[status] ?? status}
        </span>
    `;
}

function showExamInfoForTeacher(t) {

  const box = document.getElementById("exam_info_box");
  if (!box) return;

  box.style.display = "block";

  const setText = (id, val = "") => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  };

  const setDisplay = (id, display) => {
    const el = document.getElementById(id);
    if (el) el.style.display = display;
  };

  setText("ex-date", t.exam_date);
  setText("ex-time", t.exam_time);
  setText("ex-type", t.exam_type);

  if (t.exam_type === "online") {
    setDisplay("room_row", "none");
    setDisplay("link_row", "block");

    const link = document.getElementById("ex-link");
    if (link) {
      link.href = t.exam_link || "";
      link.textContent = t.exam_link || "";
    }

  } else {
    setDisplay("room_row", "block");
    setDisplay("link_row", "none");
    setText("ex-room", t.exam_room);
  }

  if (t.final_file) {
    const label = document.getElementById("final_file_label");
    const link = document.getElementById("final_file_link");

    if (label) label.textContent = t.final_file;
    if (link) {
      link.href = "../uploads/final/" + t.final_file;
      link.textContent = "📄 Προβολή Τελικού Αρχείου";
    }

  } else {
    setText("final_file_label", "Δεν έχει ανέβει");
    const link = document.getElementById("final_file_link");
    if (link) link.textContent = "";
  }


    // Grade
    document.getElementById("final_grade").textContent =
        t.final_grade !== null ? t.final_grade : "-";
}
function exportTheses(format) {
    window.location.href = `../backend/teachers/export_theses_${format}.php`;
}
function loadTeacherStatistics() {
    fetch("../backend/teachers/get_statistics.php")
        .then(r => r.json())
        .then(data => {
            renderStatsChart(data);
        });
}
function renderStatsChart(stats) {
    const ctx = document.getElementById("statsChart");

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: [
                "Σύνολο Πτυχιακών",
                "Μέσος Βαθμός",
                "Μέσος Χρόνος (ημέρες)"
            ],
            datasets: [{
                label: "Στατιστικά Καθηγητή",
                data: [
                    stats.total,
                    stats.avg_grade,
                    stats.avg_days
                ],
                backgroundColor: [
                    "#0d6efd",
                    "#198754",
                    "#ffc107"
                ]
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
function cancelThesisWithReason(thesisId){

    Swal.fire({
        title: "Ακύρωση Διπλωματικής",
        input: "textarea",
        inputLabel: "Λόγος ακύρωσης",
        inputPlaceholder: "Γράψτε τον λόγο ακύρωσης...",
        inputAttributes: {
            maxlength: 300
        },
        showCancelButton: true,
        confirmButtonText: "Ακύρωση Πτυχιακής",
        cancelButtonText: "Άκυρο",
        inputValidator: value => {
            if (!value) return "Ο λόγος είναι υποχρεωτικός";
        }
    }).then(result => {

        if (!result.isConfirmed) return;

        fetch("../backend/teachers/cancel_thesis_with_reason.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                thesis_id: thesisId,
                reason: result.value
            })
        })
        .then(r => r.json())
        .then(d => {
            if (!d.success) {
                swalError(d.message || "Σφάλμα");
                return;
            }

            swalSuccess("Η πτυχιακή ακυρώθηκε");
            loadTheses();
            document.getElementById("details_area").style.display = "none";
        });
    });
}


/*---------------------------------------------------------
  INIT
---------------------------------------------------------*/
document.addEventListener("DOMContentLoaded", () => {

    loadTheses();
    loadTopics();
    initTopicForm();
    loadTeacherInvites();
    loadTeacherStatistics();

    const statusFilter = document.getElementById("filter-status");
    const roleFilter   = document.getElementById("filter-role");

    if (statusFilter) {
        statusFilter.addEventListener("change", () => {
            console.log("STATUS FILTER:", statusFilter.value);
            applyThesisFilters();
        });
    }

    if (roleFilter) {
        roleFilter.addEventListener("change", () => {
            console.log("ROLE FILTER:", roleFilter.value);
            applyThesisFilters();
        });
    }
});
