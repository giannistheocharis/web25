swalMsg = function(msg) {
    Swal.fire({
        icon: 'warning',
        title: 'Προσοχή',
        text: msg,
        confirmButtonText: 'OK'
    });
}
swalError = function(message){
    Swal.fire({
        title: 'Σφάλμα',
        text: message,
        icon: 'error',
        confirmButtonText: 'OK'
    });
}
swalConfirmation = function(message, onConfirm){
    Swal.fire({
        title: 'Επιβεβαίωση',
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ναι',
        cancelButtonText: 'Όχι'
    }).then((result) => {
        if (result.isConfirmed) onConfirm();
    });

}
swalSuccess = function(msg) {
    Swal.fire({
        icon: 'success',
        title: 'Επιτυχία',
        text: msg,
        confirmButtonText: 'OK'
    });
}



let currentThesisId = null;
console.log("SECRETARY JS LOADED");


function loadTheses() {
    fetch("../backend/secretary/list_theses.php")
        .then(r => r.json())
        .then(list => {
            const tbody = document.querySelector("#theses_table tbody");
            tbody.innerHTML = "";

            if (!list.length) {
                tbody.innerHTML =
                    `<tr><td colspan="5">Δεν υπάρχουν διπλωματικές.</td></tr>`;
                return;
            }

            list.forEach(t => {
                const tr = document.createElement("tr");

                tr.innerHTML = `
                    <td>${t.student}</td>
                    <td>${t.title}</td>
                    <td>${t.supervisor}</td>
                    <td>${t.thesis_status}</td>
                    <td>
                        <button onclick="showDetails(${t.id})">Προβολή</button>
                    </td>
                `;

                tbody.appendChild(tr);
            });
        });
}
function timeSince(dateString) {
    const start = new Date(dateString);
    const now = new Date();

    let years = now.getFullYear() - start.getFullYear();
    let months = now.getMonth() - start.getMonth();
    let days = now.getDate() - start.getDate();

    if (days < 0) {
        months--;
        days += new Date(now.getFullYear(), now.getMonth(), 0).getDate();
    }
    if (months < 0) {
        years--;
        months += 12;
    }

    let parts = [];
    if (years > 0) parts.push(`${years} έτη`);
    if (months > 0) parts.push(`${months} μήνες`);
    if (days > 0) parts.push(`${days} ημέρες`);

    return parts.length ? parts.join(', ') : 'Σήμερα';
}

function showDetails(id) {
    currentThesisId = id;

    fetch(`../backend/secretary/get_thesis_details.php?id=${id}`)
        .then(r => r.json())
        .then(t => {
            const box = document.getElementById("details_area");

            box.innerHTML = `
                <p><b>Θέμα:</b> ${t.title}</p>
                <p><b>Φοιτητής:</b> ${t.student_name}</p>
                <p><b>Καθηγητής:</b> ${t.supervisor_name}</p>
                <p><b>Κατάσταση:</b> ${t.thesis_status}</p>
                <p><b>Περιγραφή:</b> ${t.abstract}</p>
                <p><b>Αριθμός φοιτητή:</b> ${t.student_am}</p>
                <p><b>Σύνδεσμος Νημερτή:</b> ${t.repository_url}</p>
                <p><b>Μέλη Επιτροπής:</b> ${t.committee_members.join(", ")}</p>
                <p>
                <b>Χρόνος από ανάθεση:</b>
                ${t.accepted_at ? timeSince(t.accepted_at) : '—'}
                </p>
                <div id="actions"></div>
            `;

            const actions = document.getElementById("actions");

            // ACTIVE → GS + Ακύρωση
            if (t.thesis_status === "active") {
                actions.innerHTML = `
                    <h4>Καταχώρηση ΓΣ</h4>
                    <input id="gs_number" placeholder="Αρ. ΓΣ">
                    <input id="gs_year" placeholder="Έτος">
                    <button onclick="saveGS()">Αποθήκευση</button>

                                        <h4>Ακύρωση Ανάθεσης</h4>
                    <button onclick="cancelThesis()">Ακύρωση</button>

                `;
            }

            // UNDER_EXAM → Ολοκλήρωση
            if (t.thesis_status === "under_exam") {
                actions.innerHTML = `
                    <button id="completeThesisBtn" onclick="completeThesis()">
                        Ολοκλήρωση Διπλωματικής
                    </button>
                `;
                const btn = document.getElementById('completeThesisBtn');
                if (btn) {
                    checkCompletion(id);
            }
        }
        });
}

/* ================= ACTIONS ================= */

function saveGS() {
    fetch("../backend/secretary/save_gs.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({
            thesis_id: currentThesisId,
            gs_number: document.getElementById("gs_number").value,
            gs_year: document.getElementById("gs_year").value
        })
    })
    .then(r => r.json())
.then(d => {
    if (!d.success) {
        swalError(d.message || "Αποτυχία καταχώρησης");
        return;
    }

    swalMsg("✔ Καταχωρήθηκε");

    // Προαιρετικό αλλά σωστό UX
    document.getElementById("gs_number").disabled = true;
    document.getElementById("gs_year").disabled = true;

    loadTheses();
});
}
function cancelThesis() {
    Swal.fire({
        title: 'Ακύρωση Ανάθεσης',
        input: 'textarea',
        inputLabel: 'Λόγος ακύρωσης',
        inputPlaceholder: 'π.χ. Ακυρώθηκε λόγω κύρωσης με απόφαση ΓΣ 12/2024',
        showCancelButton: true,
        confirmButtonText: 'Ακύρωση',
        cancelButtonText: 'Πίσω',
        inputValidator: (value) => {
            if (!value) {
                return 'Ο λόγος ακύρωσης είναι υποχρεωτικός';
            }
        }
    }).then((result) => {
        if (!result.isConfirmed) return;

        fetch("../backend/secretary/cancel_thesis.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                thesis_id: currentThesisId,
                reason: result.value
            })
        })
        .then(r => r.json())
        .then(d => {
            if (!d.success) {
                swalError(d.message || "Αποτυχία ακύρωσης");
                return;
            }

            Swal.fire({
                icon: 'success',
                title: 'Ακυρώθηκε',
                text: 'Η ανάθεση ακυρώθηκε επιτυχώς'
            });

            loadTheses();
            document.getElementById("details_area").innerHTML = "";
        });
    });
}

function completeThesis() {
    fetch("../backend/secretary/complete_thesis.php", {
        method: "POST",
        body: new URLSearchParams({ thesis_id: currentThesisId })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) {
            swalError(d.message || "Δεν επιτρέπεται");
            return;
        }
        swalConfirm("✔ Ολοκληρώθηκε");
        loadTheses();
        document.getElementById("details_area").innerHTML = "";
    });
}

function checkCompletion(thesisId) {
  fetch(`../backend/secretary/check_thesis_completion.php?thesis_id=${thesisId}`)
    .then(res => res.json())
    .then(data => {
      const btn = document.getElementById('completeThesisBtn');

      btn.disabled = !data.can_complete;

      if (!data.can_complete) {
        btn.title = "Απαιτούνται 3 βαθμοί και σύνδεσμος Νημερτή";
      } else {
        btn.title = "";
      }
    });
}
document.getElementById('jsonUploadForm').addEventListener('submit', e => {
  e.preventDefault();

  const formData = new FormData(e.target);

  fetch('../backend/secretary/import_json.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(d => alert(d.message));
});



function logout() {
    window.location.href = "../backend/logout.php";
}

document.addEventListener("DOMContentLoaded", loadTheses);
