<!--
    NAME : Game fingerprints
    DATE : May 21, 2020 
    AUTHOR : lolo
-->
<!DOCTYPE html>
<html>
    <head>
    <meta name="viewport" content="width=device-width, initial-scale=1" >
        <title>Lisa Olivia-Game fingerprints</title>
        <meta Name="Description"Content="Tekan Secara Bersamaan">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css" integrity="sha384-oS3vJWv+0UjzBfQzYUhtDYW+Pj2yciDJxpsK1OYPAYjqT085Qq/1cq5FLXAZQ7Ay" crossorigin="anonymous">
        <link href="https://fonts.googleapis.com/css2?family=Permanent+Marker&display=swap" rel="stylesheet">
        <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css"
  />
    </head>
    <style>
body {
    background-color:black;
    user-select: none;
    -webkit-user-select : none;
    overflow:hidden;
}
#nlevel{
    position:absolute;
    display:flex;
    justify-content:center;
    align-items:center;
    top:0;
    left:0;
    width:100%;
    height:40px;
    text-align:center;
    color:white;
    background-color:rgba(255,0,0,.7);
    box-shadow:0 2px 2px white;
    font-size:1.5em;
    font-family: 'Permanent Marker', cursive;
    z-index:3;
}
#output{
    height:100%;
    width:100%;
    z-index:2;
}
#progress{
    position:absolute;
    top:43px;
    left:0;
    background-color:#33cc33;
    box-shadow:inset 0 2px 3px black,inset 0 -1px 3px white;
    border-radius:0 5px 5px 0;
    width:0%;
    height:10px;
    z-index:1;
}
button{
    position:absolute;
    display:flex;
    justify-content:center;
    align-items:center;
    font-size:0.6em;
    color:white;
    background-color:transparent;
    box-shadow:inset 0 0 10px black;
    border:none;
    z-index:3;
}

    </style>
    <script>
let W, H;
let btns;
let level = 1;
let level_MAX
let lastD;
let stateTime = false;
let posBtn = [];

const random = (max=1, min=0) => Math.random() * (max - min) + min;

const btnPressed = (btn) => {
    btn.style.color = 'red';
    btn.style.border = '1.5px solid white';
    btn.setAttribute('state', 'BTN_PRESSED');
    check();
};

const btnRelease = (btn) => {
    btn.style.color = 'white';
    btn.style.border = 'none';
    btn.setAttribute('state', '');
    stateTime = false;
    progress.style.width = 0 + 'px';
};

const nextLevel = () => {
    posBtn = [];
    output.innerHTML = "";
    level++;
    stateTime = false;
};

const checkTime = () => {
    if(stateTime){
        let dateNow = Date.now();
        progress.style.width = 100-100*(lastD+2000-dateNow)/2000 + "%";
        if(Date.now()>=lastD+2000){
            if(level<level_MAX){
                progress.style.width = 0;
                nextLevel();
                createLevel();
            }
            else finish();
        }
    }
};
window.setInterval(checkTime,10);

const check = () => {
    let cpt = 0;
    for(let i=0; i<btns.length; i++){
       if(btns[i].getAttribute("state")==='BTN_PRESSED')cpt++;
    }
    if(cpt===btns.length){
        lastD = Date.now();
        stateTime = true;
    }
};


const newEvents = () => {
    nlevel.innerHTML = "LEVEL " + level;
    btns = output.getElementsByTagName('button');
    for(let i=0; i<btns.length; i++){
        btns[i].addEventListener("touchstart", () => btnPressed(btns[i]) );
        btns[i].addEventListener("touchend", () => btnRelease(btns[i]) );
    }
};

const createLevel = () => {
    for(let i=0; i<level; i++){
        let x, y , dist;
        let w = 50;
        do{
            x = random(W-w,20);
            y = random(H-w,90);
            dist = false;
            if(posBtn.length > 0){
                for(let a=0; a<posBtn.length; a++){
                    if(Math.abs(posBtn[a].x - x) < w+20 && Math.abs(posBtn[a].y - y) < w+20)dist = true;
                }
            }
        }while(dist);
        posBtn.push(new Point(x, y));
        createButton(x, y, w);
    }
    newEvents();
};
class Point {
    constructor(x, y) {
        this.x = x;
        this.y = y;
    }
}
const finish = () => {
    stateTime = false;
    Swal.fire({
        title: 'CONGRATULATIONS !',
        text: 'Thanks for playing ',
        imageUrl: 'https://image.flaticon.com/icons/svg/1642/1642322.svg',
        imageWidth: 300,
        imageHeight: 200,
        imageAlt: 'Custom image',
    })
    .then(() => {
        output.innerHTML = "";
        posBtn = [];
        level = 1;
        createLevel();
    });
};


const createButton = (x, y, w) => {
        let div = document.createElement("button");
        div.setAttribute('class', 'btn');
        div.style.left = x  + "px";
        div.style.top = y + "px";
        div.style.width = w + "px";
        div.style.height = w + "px";
        div.style.borderRadius = "50%";
        div.innerHTML  = `<i class="fas fa-fingerprint fa-4x"></i>`;
        output.appendChild(div);
};


const boxAlert = () => {
    Swal.fire({
        title: 
            'Lisa Olivia',
            showClass: {popup: 'animate__animated animate__fadeInDown'},
            hideClass: {popup: 'animate__animated animate__fadeOutUp'},
            text: 'Cara Bermain:Tekan Semua Fingerprint.Pilih Level:',
            input: 'radio',
            inputOptions: {
                4: 'Easy',
                6: 'Medium',
                10: 'Hard'
            },
            inputValidator: (value) => {
                if (!value) return 'You need to choose a difficulty !'
                else level_MAX = value;
            }
    })
};

const init = () => {
    W = innerWidth;
    H = innerHeight;
    boxAlert();
    createLevel();
};

onload = init;
    </script>
    <body>

        <div id="progress"></div>
        <div id="output"></div>
        <div id="nlevel">LEVEL 1</div>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>
    </body>
</html>