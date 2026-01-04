// frontend/js/student_dashboard.js
    // 🌿 Helper για DOM elements
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
    // ================= End SweetAlert Helpers =================

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

    // ---------- Progress Mapping ----------
const STATUS_MAP = {
        "pending":     { pct: 20, label: "Αίτηση / Αναμονή" },
        "approved":    { pct: 40, label: "Εγκεκριμένη" },
        "active":      { pct: 60, label: "Σε εξέλιξη" },
        "under_exam":  { pct: 80, label: "Υπό εξέταση" },
        "completed":   { pct: 100, label: "Ολοκληρωμένη" }
};

    function updateProgress(status){
        const bar = document.getElementById("progress_bar");
        const label = document.getElementById("progress_label");

        const info = STATUS_MAP[status] || { pct: 0, label:"Άγνωστη κατάσταση"};
        bar.style.width = info.pct+"%";
        label.textContent = `${info.label} (${info.pct}%)`;
    }

    let currentThesisId = null;

    // =======================================================
    // Φόρτωση πτυχιακής φοιτητή
    // =======================================================
    function loadThesis(){
        fetch("../backend/students/get_thesis.php")
        .then(r=>r.json())
        .then(t=>{
            const area = document.getElementById("thesis_area");
            area.innerHTML = "";

            if(!t){
                area.innerHTML = "<p>Δεν έχεις καταχωρημένη διπλωματική.</p>";
                updateProgress(null);
                return;
            }

            currentThesisId = t.id;
            updateProgress(t.thesis_status);
            console.log("DEBUG thesis:", t);
            console.log("STATUS =", t.status, t.thesis_status);

            area.appendChild(el("p", {}, "Θέμα: "+t.title));
            area.appendChild(el("p", {}, "Περίληψη: "+(t.abstract || "-")));
            area.appendChild(el("p", {}, "Supervisor: "+(t.supervisor_name || "-")));
            area.appendChild(el("p", {}, "Κατάσταση: "+t.thesis_status));
            area.appendChild(el("p", {}, "Ημερομηνία Δημιουργίας: " + (t.created_at || "-")));
            area.appendChild(el("p", {}, "Έναρξη διπλωματικής: " + (t.accepted_at || "-"))
                );

            if(t.pdf_path){
                area.appendChild(el("p",{}, el("a", {href:"../"+t.pdf_path, target:"_blank"}, "📄 Προβολή PDF Θέματος")));
            }

            loadCommittee();
            loadCommentsStudent(t.id);

            document.getElementById("manage_status").textContent = t.thesis_status;

            if(t.assigned_at){
                const days = Math.floor((Date.now()- new Date(t.assigned_at)) / (1000*60*60*24));
                document.getElementById("days_count").textContent = days+" ημέρες";
            }

            if (t.resource_links) {
                let linksContainer = document.getElementById("links_box");
                linksContainer.innerHTML = "";
                let links = [];

                try { links = JSON.parse(t.resource_links); }
                catch { links = [t.resource_links]; }

                links.forEach(link=>{
                    linksContainer.innerHTML += `<div>🔗 <a href="${link}" target="_blank">${link}</a></div>`;
                });
            }
            const examBox = document.getElementById("exam_info_box");
            const decisionBox = document.getElementById("decision_box");
            decisionBox.innerHTML = "";
            if (t.thesis_status === "under_exam") {
                showExamInfo(t);
            }

            handleStageUI(t);
        });
}
    
    function answerInvite(inviteId, action) {

        Swal.fire({
            title: action === 'accept' ? 'Αποδοχή πρόσκλησης;' : 'Απόρριψη πρόσκλησης;',
            text: 'Η ενέργεια δεν αναιρείται',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ναι',
            cancelButtonText: 'Άκυρο'
        }).then(result => {

            if (!result.isConfirmed) return;

            fetch("../backend/students/answer_invite.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    invite_id: inviteId,
                    action: action
                })
            })
            .then(r => r.json())
            .then(d => {
                Swal.fire({
                    icon: 'success',
                    title: 'Ολοκληρώθηκε',
                    text: d.message
                }).then(() => {
                    loadInvites();
                    location.reload();
                });
            });

        });
    }


    // =======================================================
    // Στάδια UI
    // =======================================================
    function handleStageUI(t){
        const content = document.getElementById("manage_content");
        content.innerHTML = "";

        if(t.thesis_status === "pending"){
            showDecisionButtons(t.id);
            return;
        }

        if(t.thesis_status === "approved"){
            content.innerHTML = "Η εκπόνηση έχει εγκριθεί. Μπορείς να ανεβάσεις το πρόχειρο αρχείο σου.";
            showCommitteeSelection(t.id);
        }
        if(t.thesis_status === "active" ){
            content.innerHTML = "Η εκπόνηση είναι σε εξέλιξη.";

            const finalUpload = el("div", {}, [
                el("h3", {}, "📄 Ανέβασμα Τελικού Αρχείου"),
                el("input", {type:"file", id:"finalUpload", accept:".pdf"}),
                el("button", {id:"uploadFinalBtn", class:"primary", style:"margin-top:8px"}, "Ανέβασμα Τελικού PDF")
            ]);

            content.appendChild(finalUpload);
            return;
        }

      if (t.thesis_status === "under_exam") {

    content.innerHTML = "<p>Η πτυχιακή βρίσκεται υπό εξέταση.</p>";

    // ✅ ΑΝ ΥΠΑΡΧΕΙ ΝΗΜΕΡΤΗΣ → ΜΟΝΟ ΠΡΟΒΟΛΗ
    if (t.repository_url && t.repository_url.trim() !== "") {

        content.appendChild(
            el("p", {}, [
                "🔗 Νημερτής: ",
                el("a", {
                    href: t.repository_url,
                    target: "_blank"
                }, t.repository_url)
            ])
        );

    } 
    // ➕ ΑΛΛΙΩΣ → INPUT
    else {

        const input = el("input", {
            type: "url",
            id: "repoInput",
            placeholder: "https://nemertes.library.upatras.gr/…",
            style: "width:100%"
        });

        const btn = el("button", {
            class: "btn btn-primary",
            style: "margin-top:6px"
        }, "Αποθήκευση Νημερτή");

        btn.onclick = () => {
            fetch("../backend/students/save_repository.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    thesis_id: t.id,
                    url: input.value
                })
            })
            .then(r => r.json())
            .then(() => {
                swalSuccess("Ο σύνδεσμος Νημερτή αποθηκεύτηκε");
                loadThesis(); // 🔁 refresh → εξαφανίζεται το input
            });
        };

        content.appendChild(input);
        content.appendChild(btn);
    }

    return;
}



        if(t.thesis_status === "completed"){
            content.innerHTML = "Η πτυχιακή ολοκληρώθηκε.";
            showExamInfo(t);
            return;
        }
    }
    function showDecisionButtons(thesisId){
    const content = document.getElementById("decision_box");
    console.log("🔥 showDecisionButtons CALLED", thesisId);
    content.innerHTML = `
        <h3>Αποδοχή Ανάθεσης</h3>
        <button class="btn btn-success"
            onclick="respondDecision(${thesisId}, 'accept')">
            ✅ Αποδοχή
        </button>
        <button class="btn btn-danger"
            onclick="respondDecision(${thesisId}, 'reject')">
            ❌ Απόρριψη
        </button>
    `;
}

    function respondDecision(thesisId, action){
        fetch("../backend/students/decision.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                thesis_id: thesisId,
                action: action
            })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Ενημέρωση',
                    text: d.message
                });
                loadThesis(); // refresh
            }
            else {
                Swal.fire({
                    icon: 'error',
                    title: 'Σφάλμα',
                    text: d.message || "Σφάλμα"
                });
            }
        });
    }


    function showCommitteeSelection(thesisId) {
        const content = document.getElementById("manage_content");

        content.innerHTML = `
            <h4>👥 Επιλογή Επιτροπής</h4>
            <div id="teachers_list">Φόρτωση...</div>
        `;

        Promise.all([
            fetch("../backend/students/list_teachers.php").then(r => r.json()),
            fetch("../backend/students/get_invited.php").then(r => r.json())
        ]).then(([teachers, invited]) => {

            const invitedMap = {};
            invited.forEach(i => {
                invitedMap[i.teacher_id] = i; // key = teacher_id
            });

            const box = document.getElementById("teachers_list");
            box.innerHTML = "";

            teachers.forEach(t => {
                const inv = invitedMap[t.id];

                const row = document.createElement("div");
                row.className = "teacher-row";

                let actionHTML = "";

                if (inv) {
                    if (inv.status === "pending") {
                        actionHTML = `<span class="badge pending">⏳ Εκκρεμεί</span>`;
                    } else if (inv.status === "accepted") {
                        actionHTML = `<span class="badge accepted">✔ Αποδέχθηκε</span>`;
                    } else if (inv.status === "rejected") {
                        actionHTML = `<span class="badge rejected">❌ Απέρριψε</span>`;
                    }
                } else {
                    actionHTML = `
                        <button class="btn-small"
                            onclick="inviteTeacher(${thesisId}, ${t.id})">
                            ➕ Πρόσκληση
                        </button>
                    `;
                }

                row.innerHTML = `
                    <span>${t.name} </span>
                    ${actionHTML}
                `;

                box.appendChild(row);
            });
        });
    }

    // =======================================================
    // ΕΜΦΑΝΙΣΗ ΣΤΟΙΧΕΙΩΝ ΕΞΕΤΑΣΗΣ
    // =======================================================
    function showExamInfo(t){
        const area = document.getElementById("thesis_area");
        area.appendChild(el("hr"));
        area.appendChild(el("h3", {}, "Στοιχεία Εξέτασης"));

        area.appendChild(el("p", {}, "Ημερομηνία: " + (t.exam_date || "-")));
        area.appendChild(el("p", {}, "Ώρα: " + (t.exam_time || "-")));
        area.appendChild(el("p", {}, "Τρόπος: " + (t.exam_type || "-")));
        area.appendChild(el("p", {}, "Τελικό αρχείο: " + (t.final_file ? "Υποβλήθηκε" : "Δεν έχει υποβληθεί")));

        if(t.exam_type === "online"){
            area.appendChild(el("p", {}, el("a", {href:t.exam_link, target:"_blank"}, "🔗 Σύνδεση στην εξέταση")));
        } else {
            area.appendChild(el("p", {}, "Αίθουσα: " + (t.exam_room || "-")));
        }

        area.appendChild(el("p", {}, "Βαθμολογία: " + (t.final_grade !== null ? t.final_grade : "-")));

        if(t.final_file){
            area.appendChild(el("p", {}, el("a", {
                href: "../uploads/final/" + t.final_file,
                target: "_blank"
            }, "📄 Προβολή Τελικού Αρχείου")));
        }
    }

    // =======================================================
    // Upload Final File
    // =======================================================
    function uploadFinalFile() {
        let file = document.getElementById("finalUpload").files[0];

        if (!file) {
            Swal.fire({
                icon: 'error',
                title: 'Σφάλμα',
                text: "Επίλεξε PDF!"
            });
            return;
        }

        let data = new FormData();
        data.append("final", file);

        fetch("../backend/students/upload_final.php", {
        method: "POST",
        body: data,
        credentials: "include"
    })
    .then(res => res.json())
    .then(r => {
        if (r.success) {
            Swal.fire({
                icon: 'success',
                title: 'Επιτυχία',
                text: "✔ Το Τελικό PDF ανέβηκε!"
            });
            loadThesis();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Σφάλμα',
                text: "✘ Σφάλμα: " + r.error
            });
        }
    })
    .catch(err => {
        console.error("UPLOAD ERROR:", err);
        Swal.fire({
            icon: 'error',
            title: 'Σφάλμα',
            text: "Σφάλμα δικτύου"
        });
    });
    }

    // =======================================================
    // Επιτροπή / Σχόλια
    // =======================================================
    function loadCommittee(){
        fetch("../backend/students/get_committee.php")
        .then(r=>r.json())
        .then(members => {
            let html = "<strong>Τριμελής Επιτροπή:</strong><br>";

            if(members.length === 0){
                html = "<em>Δεν έχει οριστεί επιτροπή.</em>";
            } else {
                members.forEach(m => { html += "👤 "+m.fullname+"<br>"; });
            }

            document.getElementById("committee_area").innerHTML = html;
        });
    }

    function loadCommentsStudent(id){
        fetch("../backend/teachers/get_comments.php?thesis_id="+id)
        .then(r=>r.json())
        .then(list => {
            const box = document.getElementById("student_comments");
            box.innerHTML = "";

            if(!list.length){
                box.innerHTML = "<i>Δεν υπάρχουν σχόλια.</i>";
                return;
            }

            list.forEach(c => {
                const div = document.createElement("div");
                div.className = "comment-box";

                div.innerHTML = `
                    <p><b>${c.name}</b> — ${c.created_at}</p>
                    <div class="comment-text">${c.comment}</div>
                `;

                box.appendChild(div);
            });
        });
    }

    // =======================================================
    // Αποθήκευση Λεπτομερειών Εξέτασης
    // =======================================================
    function saveExamDetails() {
        const payload = {
            thesis_id: currentThesisId,
            exam_date: document.getElementById("exam_date").value,
            exam_time: document.getElementById("exam_time").value,
            exam_type: document.getElementById("exam_type").value,
            exam_room: document.getElementById("exam_room").value,
            exam_link: document.getElementById("exam_link").value

        };

        fetch("../backend/students/save_exam_details.php",{
            method:"POST",
            credentials: "include",
            headers:{ "Content-Type":"application/json" },
            body: JSON.stringify(payload)
        })
        .then(r=>r.json())
        .then(res=>{
            const msg=document.getElementById("examMsg");
            msg.textContent = res.success ? "✔ Αποθηκεύτηκαν!" : "✖ Σφάλμα";
            msg.style.color = res.success ? "green" : "red";
        });
    }

    // =======================================================
    // Toggle Exam Type
    // =======================================================
    function toggleExamType() {
        const type = document.getElementById("exam_type").value;

        document.getElementById("exam_room_box").style.display =
            type === "in_person" ? "block" : "none";

        document.getElementById("exam_link_box").style.display =
            type === "online" ? "block" : "none";
    }



    function inviteTeacher(thesisId, teacherId) {
        fetch("../backend/students/invite_teacher.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                thesis_id: thesisId,
                teacher_id: teacherId
            })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Ενημέρωση',
                    text: d.message
                });
                showCommitteeSelection(thesisId); // refresh
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Σφάλμα',
                    text: d.message || "Σφάλμα"
                });
            }
        });
    }
    function uploadDraft() {
        const fileInput = document.getElementById("draftUpload");
        const file = fileInput.files[0];

        if (!file) {
            Swal.fire({
                icon: 'error',
                title: 'Σφάλμα',
                text: "Επίλεξε αρχείο!"
            });
            return;
        }

        const data = new FormData();
        data.append("draft", file);

        fetch("../backend/students/upload_draft.php", {
            method: "POST",
            body: data,
            credentials: "include"
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Επιτυχία',
                    text: "✔ Το πρόχειρο ανέβηκε!"
                });
                document.getElementById("draftStatus").textContent = "✔ Το αρχείο ανέβηκε";
                loadThesis();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Σφάλμα',
                    text: "✘ Σφάλμα: " + res.error
                });
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'Σφάλμα',
                text: "Σφάλμα δικτύου"
            });
        });
    }

    // 🔥 ΚΑΝ’ ΤΟ GLOBAL
    window.uploadDraft = uploadDraft;


    function saveLinks() {
    const txt = document.getElementById("resource_links").value.trim();
    const msg = document.getElementById("links_msg");

    if (!txt) {
        msg.textContent = "⚠ Γράψε τουλάχιστον ένα link.";
        msg.style.color = "orange";
        return;
    }

    // πάρε links ανά γραμμή
    const linksArray = txt.split("\n").map(s => s.trim()).filter(Boolean);

    fetch("../backend/students/save_links.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({
            thesis_id: currentThesisId, 
            links: linksArray })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
        msg.textContent = "✔ Αποθηκεύτηκαν!";
        msg.style.color = "green";
        loadThesis();
        } else {
        msg.textContent = "✘ Σφάλμα: " + (res.error || "άγνωστο");
        msg.style.color = "red";
        }
    })
    .catch(() => {
        msg.textContent = "✘ Σφάλμα δικτύου";
        msg.style.color = "red";
    });
    }
function loadSentInvites() {
    fetch("../backend/students/get_invited.php")
        .then(r => r.json())
        .then(list => {
            const box = document.getElementById("decision_box");

            if (!list || list.length === 0) return;

            box.innerHTML += `<h4>📨 Προσκλήσεις Επιτροπής</h4>`;

            list.forEach(inv => {
                let badge = "⏳ Εκκρεμεί";
                let cls = "pending";

                if (inv.status === "accepted") {
                    badge = "✅ Αποδέχθηκε";
                    cls = "accepted";
                } else if (inv.status === "rejected") {
                    badge = "❌ Απέρριψε";
                    cls = "rejected";
                }

                box.innerHTML += `
                    <div class="invite-row ${cls}">
                        <span>${inv.name} ${inv.surname}</span>
                        <span class="invite-status">${badge}</span>
                    </div>
                `;
            });
        });
}


    function showSection(id, btn) {
    document.querySelectorAll('.dashboard-section').forEach(sec => {
        sec.style.display = 'none';
    });

    document.getElementById(id).style.display = 'block';

    document.querySelectorAll('.nav-btn').forEach(b => {
        b.classList.remove('active');
    });

    btn.classList.add('active');
}

    // =======================================================
    // Init
    // =======================================================
    document.addEventListener("DOMContentLoaded", () => {

        loadThesis();
        loadCommittee();
        loadSentInvites();

        const saveLinksBtn = document.getElementById("save_links_btn");
        if (saveLinksBtn) {
        saveLinksBtn.addEventListener("click", saveLinks);
        }
        document.addEventListener("click", e => {
            if (e.target.id === "uploadFinalBtn") uploadFinalFile();
            if (e.target.id === "saveExamDetailsBtn") saveExamDetails();
            if (e.target.id === "uploadDraftBtn") uploadDraft();
        });

        const examType = document.getElementById("exam_type");
        if (examType) examType.addEventListener("change", toggleExamType);
    });
