/* ===== AURA MOUSE ===== */
const aura = document.getElementById("aura");
document.addEventListener("mousemove", e => {
    aura.style.left = e.clientX + "px";
    aura.style.top = e.clientY + "px";
});

/* ===== STARS ===== */
const canvas = document.getElementById("stars");
const ctx = canvas.getContext("2d");

canvas.width = window.innerWidth;
canvas.height = window.innerHeight;

let stars = [];

for (let i = 0; i < 1020; i++) {
    stars.push({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        r: Math.random() * 1.2,
        s: Math.random() * 0.4 + 0.1,
        a: Math.random()
    });
}

function animate() {
    ctx.clearRect(0,0,canvas.width,canvas.height);

    for (let s of stars) {
        ctx.beginPath();
        ctx.arc(s.x, s.y, s.r, 0, Math.PI*2);
        ctx.fillStyle = `rgba(255,255,255,${s.a})`;
        ctx.fill();

        s.y -= s.s;

        if (s.y < 0) {
            s.y = canvas.height;
            s.x = Math.random() * canvas.width;
        }
    }

    requestAnimationFrame(animate);
}

animate();

window.addEventListener("resize", () => {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
});