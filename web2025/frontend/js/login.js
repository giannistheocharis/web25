console.log("login.js loaded");

document.getElementById("loginBtn").addEventListener("click", function () {

    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;

    fetch("../backend/login.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ username, password })
    })
    .then(res => res.json())
    .then(data => {

        if (data.success) {
            if (data.role === "student")
                window.location.href = "student_dashboard.html";
            else if (data.role === "teacher")
                window.location.href = "teacher_dashboard.html";
            else
                window.location.href = "secretary_dashboard.html";
        }
        else {
            document.getElementById("error").innerText = data.message;
        }

    })
    .catch(err => console.error("Server error:", err));
});
