document.getElementById("submitBtn").addEventListener("click", function () {

    const title = document.getElementById("title").value.trim();
    const abstract = document.getElementById("abstract").value.trim();
    const pdf = document.getElementById("pdf").files[0];

    if (!title || !abstract) {
        document.getElementById("msg").innerText = "Συμπλήρωσε όλα τα πεδία.";
        return;
    }

    let fd = new FormData();
    fd.append("title", title);
    fd.append("abstract", abstract);
    if (pdf) fd.append("pdf", pdf);

    fetch("../backend/students/save_thesis.php", {
        method: "POST",
        body: fd
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById("msg").innerText = data.message;

        if (data.success) {
            document.getElementById("msg").style.color = "lightgreen";
        }
    });
});
