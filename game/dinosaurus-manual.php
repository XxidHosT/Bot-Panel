<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>T-rex Runner Game</title>
</head>
<style>
@import url('https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap');
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    user-select: none;
    font-family: "Press Start 2P",
    Sans-Serif;
}
body {
    display: grid;
    place-items: center;
    width: 100vw;
    min-height: 100vh;
}
#gameOverMsg {
    display: none;
    flex-direction: column;
    align-items: center;
    position: absolute;
    width: 100%;
    top: calc(50% - 30px);
}
#gameOverMsg.show {
    display: flex;
}
#gameOverMsg #msg {
    color: #495359;
}
#restartBtn {
    width: 40px;
    height: 40px;
    border: none;
    outline: none;
    background: url("https://raw.githubusercontent.com/AvinashProgrammer/T-rex-Runner-Game/main/img/restart.png");
    background-size: 100% 100%;
    margin-top: 10px;
}
#scoreContainer {
    display: flex;
    justify-content: space-between;
    position: absolute;
    top: calc(50% - 80px);
    right: 10px;
    color: #495359;
    font-size: 12px;
}
#scoreContainer > * {
    margin: 5px;
}
#loader {
    position: fixed;
    top: 0;
    left: 0;
    display: grid;
    place-items: center;
    width: 100vw;
    height: 100vh;
    background: #fff;
    z-index: 10000;
}
#loader #loader-text {
    animation: blink 1s linear infinite;
}
@keyframes blink {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0;
    }
}
</style>
<body>
    <div id="loader">
        <div id="loader-text">
            Loading...
        </div>
    </div>
    <canvas></canvas>
    <div id="gameOverMsg">
        <p id="msg">
            Game Over
        </p>
        <button id="restartBtn"></button>
    </div>
    <div id="scoreContainer">
        <div>
            HI <span id="hiscore"></span>
        </div>
        <div id="score"></div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.1/howler.min.js"></script>
    <script>
    
        const $ = s => document.querySelector(s);
        const c = t => console.log(t);
        const canvas = $("canvas");
        const ctx = canvas.getContext("2d");
        canvas.width = innerWidth;
        canvas.height = 200;

        // Game related variables
        let animationType, playerImg, groundImg, enemyImg, jumpSound, gameOverSound, scoreSound, cloudImg, sunImg;
        let jump = false;
        let gravity = 1;
        let jumpForce = 15;
        let enemySX = [(208/6)*0, (208/6)*1, (208/6)*3, (208/6)*4, (208/6)*5, (208/6)*0];
        let gameOver = false;

        // Loading Assests
        playerImg = new Image();
        playerImg.src = "https://raw.githubusercontent.com/AvinashProgrammer/T-rex-Runner-Game/main/img/player.png";
        groundImg = new Image();
        groundImg.src = "https://raw.githubusercontent.com/AvinashProgrammer/T-rex-Runner-Game/main/img/sprite1.png";
        enemyImg = new Image();
        enemyImg.src = "https://raw.githubusercontent.com/AvinashProgrammer/T-rex-Runner-Game/main/img/enemy.png";
        cloudImg = new Image();
        cloudImg.src = "https://raw.githubusercontent.com/AvinashProgrammer/T-rex-Runner-Game/main/img/cloud.png";
        sunImg = new Image();
        sunImg.src = "https://raw.githubusercontent.com/AvinashProgrammer/T-rex-Runner-Game/main/img/sun.png";
        jumpSound = new Howl({
            src: ["https://raw.githubusercontent.com/AvinashProgrammer/T-rex-Runner-Game/main/audio/jump.mp3"]
        });
        gameOverSound = new Howl({
            src: ["https://raw.githubusercontent.com/AvinashProgrammer/T-rex-Runner-Game/main/audio/gameover.mp3"]
        });
        scoreSound = new Howl({
            src: ["https://raw.githubusercontent.com/AvinashProgrammer/T-rex-Runner-Game/main/audio/score.mp3"]
        });

        function game() {

            // Score and High score
            let scoreElm = $("#score");
            let hiscoreElm = $("#hiscore");
            let score = 0;
            let hiscore = 0;


            // Classes for creating objects
            class Component {
                constructor(sprite, sx, sy, w, h, x, y, cw, ch) {
                    this.sprite = sprite;
                    this.sx = sx;
                    this.sy = sy;
                    this.w = w;
                    this.h = h;
                    this.x = x;
                    this.y = y;
                    this.cw = cw;
                    this.ch = ch;
                    this.velY = 0;
                }
                draw() {
                    ctx.drawImage(this.sprite, this.sx, this.sy, this.w, this.h, this.x, this.y, this.cw, this.ch);
                }
                update() {
                    this.draw();
                }
            }


            // creating player, ground and enemy array
            let enemyArray = [];
            let cloudArray = [];
            const player = new Component(playerImg, 0, 0, 351/4, 99, 20, canvas.height - 60, 50, 50);
            const ground = new Component(groundImg, 0, 100, 2440/7, 30, 0, canvas.height - 40, canvas.width, 30);
            const sun = new Component(sunImg, 0, 0, 79, 85, canvas.width/2+50, 50, 10, 10);

            // Function for animation
            function animate() {
                animationType = requestAnimationFrame(animate);
                ctx.fillStyle = "rgb(255,255,255)";
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // Sun
                sun.update();
                sun.x -= 0.2;
                if (sun.x < -sun.w) {
                    sun.x = canvas.width;
                }

                // cloud
                cloudArray.forEach(cloud => {
                    cloud.update();
                    cloud.x -= 1;
                })

                // Ground animation
                if (score < 100) {
                    if (ground.sx < 2440 - ground.w) {
                        ground.sx += 6;
                    } else {
                        ground.sx = 0;
                    }
                } else if (score > 100 && score < 250) {
                    if (ground.sx < 2440 - ground.w) {
                        ground.sx += 7;
                    } else {
                        ground.sx = 0;
                    }
                } else if (score > 250 && score < 500) {
                    if (ground.sx < 2440 - ground.w) {
                        ground.sx += 8;
                    } else {
                        ground.sx = 0;
                    }
                } else if (score > 500 && score < 1000) {
                    if (ground.sx < 2440 - ground.w) {
                        ground.sx += 9;
                    } else {
                        ground.sx = 0;
                    }
                } else {
                    if (ground.sx < 2440 - ground.w) {
                        ground.sx += 10;
                    } else {
                        ground.sx = 0;
                    }
                }
                ground.update()


                // Player Jump
                if (player.y < canvas.height - 60) {
                    player.velY += gravity;
                } else {
                    if (jump) {
                        player.velY = -jumpForce;
                        jumpSound.play();
                        jump = false;
                    }
                    if (player.velY > 0) {
                        player.velY = 0;
                    }
                }

                player.y += player.velY;
                player.update();

                // Drawing Enemy
                enemyArray.forEach((enemy, index) => {
                    enemy.update();
                    if (score < 100) {
                        enemy.x -= 6;
                    } else if (score > 100 && score < 250) {
                        enemy.x -= 7;
                    } else if (score > 250 && score < 500) {
                        enemy.x -= 8;
                    } else if (score > 500 && score < 1000) {
                        enemy.x -= 9;
                    } else {
                        enemy.x -= 10;
                    }

                    if (enemy.x < -enemy.w) {
                        enemyArray.splice(index, 1);
                    }

                    //Game over
                    let distance = checkCollision(player, enemy);
                    if (distance < 40) {
                    cancelAnimationFrame(animationType);
                        // navigator.vibrate(200);
                        $("#gameOverMsg").classList.add("show");
                        gameOver = true;
                        gameOverSound.play();
       /*
          Instructions for automating game:-
          1. Uncomment line no. 210.
          2. Comment line no. 199, 201, 202, 203
          3. Change line no. 198 if(distance < 40) to if(distance < 100)
       */
                        //jump = true;
                    }
                })

                // High score
                if (hiscore < score) {
                    hiscore = score;
                    hiscoreElm.innerHTML = `${scoreElm.innerHTML}`;
                }
                if (gameOver) {
                    hiscore = hiscoreElm.innerHTML;
                }

                // score sound
                if (score%100 == 0 && score != 0) {
                    scoreSound.play();
                }



            }
            animate()

            // Function to check collision
            function checkCollision(obj1, obj2) {
                return Math.hypot(obj1.x - obj2.x, obj1.y - obj2.y);
            }

            // Function to change Player Frame
            function changePlayerFrame() {
                if (player.sx < 351 - player.w && player.velY == 0) {
                    player.sx += player.w;
                } else if (player.velY < 0) {
                    player.sx = 0;
                } else {
                    player.sx = 0;
                }
            }
            setInterval(changePlayerFrame, 100)

            // Function to generate Enemy
            function generateEnemy() {
                let enemyHeight,
                y;
                if (Math.random() < 0.5) {
                    enemyHeight = 70;
                    y = canvas.height - 80;
                } else {
                    enemyHeight = 50;
                    y = canvas.height - 60;
                }
                enemyArray.push(
                    new Component(
                        enemyImg,
                        enemySX[Math.floor(Math.random() * enemySX.length)],
                        0,
                        208/6,
                        75,
                        canvas.width + 20,
                        y,
                        30,
                        enemyHeight
                    )
                );
            }
            setInterval(generateEnemy, 1200)

            function generateCloud() {
                let cloudY = 30 + (Math.random()*70);
                cloudArray.push(new Component(cloudImg, 0, 0, 94, 50, canvas.width+60, cloudY, 50, 30))
            }
            generateCloud()
            setInterval(generateCloud, 2000)

            // Score
            setInterval(() => {
                if (!gameOver) {
                    let modScore;
                    if (score < 10-1) {
                        modScore = `0000${score += 1}`;
                    } else if (score < 100-1) {
                        modScore = `000${score += 1}`;
                    } else if (score <= 1000-1) {
                        modScore = `00${score += 1}`;
                    } else if (score <= 10000-1) {
                        modScore = `0${score += 1}`;
                    } else {
                        modScore = "You are hacker";
                    }
                    scoreElm.innerHTML = `${modScore}`;
                } else {
                    score = 0;
                }
            },
                200)

        }
        $("#restartBtn").addEventListener("click", ()=> {
            game()
            $("#gameOverMsg").classList.remove("show");
            gameOver = false;
        })
        addEventListener("load", () => {
            $("#loader").style.display = "none";
            game()
        })
        addEventListener("touchstart", ()=> {
            jump = true;
        })
        addEventListener("touchend", ()=> {
            jump = false;
        })

    </script>
</body>
</html>