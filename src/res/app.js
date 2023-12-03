
let tg = window.Telegram.WebApp;
tg.expand();

let uid = 0;
let lang = "";
let first_name = "";
let last_name = "";
let username = "";
let photo_url = "";

if (typeof tg.initDataUnsafe.user === "object") {
    uid = tg.initDataUnsafe.user.id;
    lang = tg.initDataUnsafe.user.language_code;
    first_name = tg.initDataUnsafe.user.first_name;
    if (typeof tg.initDataUnsafe.user.last_name === "string")
        last_name = tg.initDataUnsafe.user.last_name;
    if (typeof tg.initDataUnsafe.user.username === "string")
        username = tg.initDataUnsafe.user.username;
    if (typeof tg.initDataUnsafe.user.photo_url === "string")
        photo_url = tg.initDataUnsafe.user.photo_url;
}

const loader = document.createElement("img");
loader.style = "display:block;opacity: 0.5;border-radius: 16px;";
loader.src = "img/loader.gif";

const userpic = document.createElement("img");
userpic.style = "display:block";
userpic.src = "photo.php?uid=" + uid
        + "&first_name=" + first_name
        + "&last_name=" + last_name
        + "&username=" + username
        + "&photo_url=" + photo_url;

addEventListener("DOMContentLoaded", (e) => {
    page("default");
});

let current_page = null;
let upd_interval = null;
let msg_timeout = null;
let vars = [];

function page(m, s = null) {
    if (s !== null) {
        s.onclick = null;
        s.classList.add("disabled");
    }
    clearInterval(upd_interval);
    current_page = m;
    var param = "";
    var pos = m.indexOf("&");
    if (pos >= 0) {
        current_page = m.substring(0, pos);
        param = m.substring(pos);
    }
    var pin = document.getElementById("driver_pin");
    if (pin) {
        param += "&pin=" + pin.value;
    }
    get_data("page_" + current_page + ".php?uid=" + uid + "&lang=" + lang + param, function (data) {
        document.body.innerHTML = data;
        var p1 = data.indexOf("<!--# ");
        var p2 = data.indexOf(" #-->");
        if ((p1 >= 0) && (p2 >= 0)) {
            var obj = JSON.parse(data.substring(p1 + 6, p2));
            if (typeof obj.vars === "object") {
                for (const [key, value] of Object.entries(obj.vars)) {
                    vars[key] = value;
                }
            }

            if (typeof obj.times === "object") {
                obj.times.forEach((tm) => {
                    timeadd(tm);
                });
            }

            if (typeof obj.run === "object") {
                obj.run.forEach(function (fn) {
                    switch (fn) {
                        case "drawseats":
                            drawseats();
                            break;
                        case "seatscfg":
                            seatscfg();
                            break;
                        case "draw_pass":
                            draw_pass();
                            break;
                    }
                });
            }
        }
        upd_interval = setInterval(function () {
            get_data("pass.php", function (data) {
                data = JSON.parse(data);
                if (typeof data.pass !== "object")
                    return;
                vars["pass"] = data.pass;
                draw_pass();
            });
        }, 10000);
    });
}

function drawseats() {
    if (typeof vars["buscfg"] !== "object")
        return;
    let b = document.getElementById("bus");
    if (b === null)
        return;
    b.innerHTML = "";
    for (let y = 0; y < vars["buscfg"].height; y++) {
        if (y > 0) {
            let s = document.createElement("div");
            s.classList.add("divider");
            b.appendChild(s);
        }
        for (let x = 0; x < vars["buscfg"].width; x++) {
            let sid = y + ":" + x;
            let s = document.createElement("div");
            s.id = sid;
            s.classList.add("seat");
            if (vars["buscfg"].driver === sid)
                s.classList.add("driver");
            if (vars["buscfg"].exclude.includes(sid))
                s.classList.add("exclude");
            s.onclick = function () {
                stoggle(s);
            };
            b.appendChild(s);
        }
    }
}

function btn_del_click(btnd) {
    if (btnd.classList.contains("yes"))
        btnd.classList.remove("yes");
    else
        btnd.classList.add("yes");
}

function get_data(url, cb, data = null) {
    fetch(url, {
        method: ((data === null) ? "GET" : "POST"),
        body: ((data === null) ? null : JSON.stringify(data)),
        headers: {
            "Content-type": "application/json; charset=UTF-8"
        }
    })
            .then(response => response.text())
            .then(data => {
                cb(data);
            });
}

function sidl() {
    return "0:0";
}

function sidr() {
    return "0:" + (vars["buscfg"].width - 1);
}

var btnl;
var btnr;
var seath;
var seatw;

function seatscfg() {

    var b = document.getElementById("bus_name");
    b.value = vars["buscfg"].bus_name;
    b.readOnly = (vars["buscfg"].bus_name !== "");

    /* driver config */

    let seatcfg = document.getElementById("seatcfg");
    if (seatcfg === null)
        return;

    var b = document.createElement("div");
    b.classList.add("seatcfg");
    seatcfg.appendChild(b);

    btnl = document.createElement("div");
    btnl.classList.add("cfgitem");
    btnl.classList.add("drvl");
    if (vars["buscfg"].driver === sidl())
        btnl.classList.add("selected");

    btnr = document.createElement("div");
    btnr.classList.add("cfgitem");
    btnr.classList.add("drvr");
    if (vars["buscfg"].driver === sidr())
        btnr.classList.add("selected");

    btnl.onclick = function () {
        btnr.classList.remove("selected");
        btnl.classList.add("selected");
        drawbus();
    };

    btnr.onclick = function () {
        btnl.classList.remove("selected");
        btnr.classList.add("selected");
        drawbus();
    };

    b.appendChild(btnl);
    b.appendChild(btnr);

    /* size config*/

    var b = document.createElement("div");
    b.classList.add("seatcfg");
    seatcfg.appendChild(b);

    /* height config*/

    var d = document.createElement("div");
    d.classList.add("cfgitem");
    d.classList.add("ver");
    b.appendChild(d);

    var btnhm = document.createElement("div");
    btnhm.classList.add("cfgitem");
    btnhm.classList.add("btnm");
    btnhm.onclick = function () {
        vars["buscfg"].height--;
        if (vars["buscfg"].height < 1)
            vars["buscfg"].height = 1;
        drawbus();
    };
    b.appendChild(btnhm);

    seath = document.createElement("div");
    seath.classList.add("cfgval");
    seath.innerHTML = vars["buscfg"].height;
    b.appendChild(seath);

    var btnhp = document.createElement("div");
    btnhp.classList.add("cfgitem");
    btnhp.classList.add("btnp");
    btnhp.onclick = function () {
        vars["buscfg"].height++;
        if (vars["buscfg"].height > 20)
            vars["buscfg"].height = 20;
        drawbus();
    };
    b.appendChild(btnhp);

    /* width config */

    var d = document.createElement("div");
    d.classList.add("cfgitem");
    d.classList.add("hor");
    b.appendChild(d);

    var btnwm = document.createElement("div");
    btnwm.classList.add("cfgitem");
    btnwm.classList.add("btnm");
    btnwm.onclick = function () {
        vars["buscfg"].width--;
        if (vars["buscfg"].width < 1)
            vars["buscfg"].width = 1;
        drawbus();
    };
    b.appendChild(btnwm);

    seatw = document.createElement("div");
    seatw.classList.add("cfgval");
    seatw.innerHTML = vars["buscfg"].width;
    b.appendChild(seatw);

    var btnwp = document.createElement("div");
    btnwp.classList.add("cfgitem");
    btnwp.classList.add("btnp");
    btnwp.onclick = function () {
        vars["buscfg"].width++;
        if (vars["buscfg"].width > 5)
            vars["buscfg"].width = 5;
        drawbus();
    };
    b.appendChild(btnwp);

    /* save block */

    var b = document.createElement("div");
    b.classList.add("seatcfg");
    seatcfg.appendChild(b);

    /* save button */

    let btns = document.createElement("div");
    btns.id = "btn_save";
    btns.classList.add("cfgitem");
    btns.classList.add("save");
    btns.onclick = function () {
        savebus();
    };
    b.appendChild(btns);

    /* delete button */

    let btnd = document.createElement("div");
    btnd.id = "btn_del";
    btnd.classList.add("cfgitem");
    btnd.classList.add("del");
    btnd.onclick = function () {
        btn_del_click(btnd);
    };
    b.appendChild(btnd);
}

function driverseat() {
    seatw.innerHTML = vars["buscfg"].width;
    seath.innerHTML = vars["buscfg"].height;
    if (btnl.classList.contains("selected"))
        vars["buscfg"].driver = sidl();
    if (btnr.classList.contains("selected"))
        vars["buscfg"].driver = sidr();
    let di = vars["buscfg"].exclude.indexOf(vars["buscfg"].driver);
    if (di >= 0)
        vars["buscfg"].exclude.splice(di, 1);
}

function excludechk() {
    vars["buscfg"].exclude.forEach(function (e, i) {
        let s = e.split(":");
        if ((parseInt(s[0]) >= vars["buscfg"].height)
                || (parseInt(s[1]) >= vars["buscfg"].width))
            vars["buscfg"].exclude.splice(i, 1);
    });
}

function drawbus() {
    excludechk();
    driverseat();
    drawseats();
}

function savebus() {
    var n = document.getElementById("bus_name");
    vars["buscfg"].bus_name = n.value;
    var btns = document.getElementById("btn_save");
    btns.classList.add("disabled");
    var btnd = document.getElementById("btn_del");
    vars["buscfg"].delete = btnd.classList.contains("yes");
    get_data("bus.php", function (data) {
        btns.classList.remove("disabled");
        if (data === "ok")
            document.getElementById("btn0").click();
        n.classList.remove("error");
        if (data === "bus_name")
            n.classList.add("error");
    }, vars["buscfg"]);
}

function saveroute() {
    var data = {
        route_name: document.getElementById("route_name").value,
        bus_name: document.getElementById("bus_name").value,
        times: [],
        delete: document.getElementById("btn_del").classList.contains("yes")
    };
    var error = false;
    document.getElementById("times").childNodes.forEach((node) => {
        if (node.id !== "timeitem")
            return;
        var time = node.querySelector("input");
        time.classList.remove("error");
        if (time.value === "") {
            time.classList.add("error");
            error = true;
        }
        data.times.push({
            day: parseInt(node.querySelector("select").value),
            time: time.value
        });
    });
    if (error)
        return;
    var btns = document.getElementById("btn_save");
    btns.classList.add("disabled");
    get_data("route.php", function (data) {
        btns.classList.remove("disabled");
        if (data === "ok")
            document.getElementById("btn0").click();
        var n = document.getElementById("bus_name");
        n.classList.remove("error");
        if (data === "bus_name")
            n.classList.add("error");
        var n = document.getElementById("route_name");
        n.classList.remove("error");
        if (data === "route_name")
            n.classList.add("error");
        var n = document.getElementById("timeadd");
        if (data === "times")
            n.classList.add("error");
    }, data);
}

function timeadd(tm = null) {
    if (tm === null) {
        var n = document.getElementById("timeadd");
        n.classList.remove("error");
    }
    var d = document.createElement("div");
    d.className = "timeitem";
    d.id = "timeitem";
    var s = document.createElement("select");
    var nd = 0;
    vars["day_names"].forEach((d) => {
        var o = document.createElement("option");
        o.value = nd++;
        o.innerHTML = d;
        s.appendChild(o);
    });
    if (tm !== null)
        s.getElementsByTagName('option')[tm.day].selected = 'selected';
    d.appendChild(s);
    var t = document.createElement("input");
    t.setAttribute("type", "time");
    if (tm !== null)
        t.value = tm.time;
    d.appendChild(t);
    var r = document.createElement("div");
    r.className = "cfgitem rem";
    d.appendChild(r);
    var e = document.getElementById("timeadd");
    e.parentNode.insertBefore(d, e);
    r.onclick = function () {
        e.parentNode.removeChild(d);
    };
}

function img(s, id) {
    s.innerHTML = "";
    s.appendChild(loader.cloneNode());
    var img = new Image();
    img.onload = function () {
        s.innerHTML = "";
        s.appendChild(img);
    };
    img.style = "display: block";
    img.src = "photo.php?uid=" + id;
}

function draw_pass() {
    for (let y = 0; y < vars["buscfg"].height; y++) {
        for (let x = 0; x < vars["buscfg"].width; x++) {
            document.getElementById(y + ":" + x).innerHTML = "";
        }
    }
    vars["pass"].forEach(function (s) {
        img(document.getElementById(s.seat), s.uid);
    });
    document.getElementById("book_label").innerHTML = vars["TAP_TO_BOOKING"];
}

function stoggle(s) {
    switch (current_page) {
        case "bus":
            bus_stoggle(s);
            break;
        case "book":
            book_stoggle(s);
            break;
    }
}

function bus_stoggle(s) {
    if (vars["buscfg"].driver === s.id)
        return;
    let si = vars["buscfg"].exclude.indexOf(s.id);
    if (si < 0) {
        s.classList.add("exclude");
        vars["buscfg"].exclude.push(s.id);
    } else {
        s.classList.remove("exclude");
        vars["buscfg"].exclude.splice(si, 1);
    }
}

function book_stoggle(s) {
    if ((vars["buscfg"].driver === s.id) ||
            (vars["buscfg"].exclude.indexOf(s.id) >= 0))
        return;
    var book_label = document.getElementById("book_label");
    book_label.innerHTML = vars["STR_PROCESS"];
    var f = s.onclick;
    s.onclick = null;
    s.innerHTML = "";
    s.appendChild(loader.cloneNode());
    clearTimeout(msg_timeout);
    get_data("book.php", function (data) {
        s.innerHTML = "";
        s.onclick = f;
        book_label.innerHTML = vars["STR_DONE"];
        if (data === "accept") {
            s.appendChild(userpic.cloneNode());
        }
        if (data.startsWith("busy")) {
            img(s, parseInt(data.substring(5)));
            book_label.innerHTML = vars["STR_BUSY"];
        }
        msg_timeout = setTimeout(function () {
            book_label.innerHTML = vars["TAP_TO_BOOKING"];
        }, 5000);
    }, {
        sid: s.id
    });
}
