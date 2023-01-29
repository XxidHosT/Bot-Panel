<!DOCTYPE html>
<!--
This is an attempt to clone the google's Trex runner game but i couldnt create a trex map, so i use cat-->'
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cat Runner | Game</title>
</head>
<style>
body {
    margin:0;
    height:100vh;
    overflow:hidden;
}

#toolbar {
    position:absolute;
    width:100%;
    height:10%;
    background: none;
    display: flex;
    justify-content: center;
    top:25%;
}

#toolbar > input {
    align-self: center;
    display:block;
    margin:10px;
    padding:15px;
    color:lightgray;
    background-color: teal;
    border:2px solid lightgray;
    outline:none;
    border-radius:15px;
    font-family: monospace;
}


canvas {
    overflow:hidden;
}


</style>
<script>
class Abstract {
    constructor(ctx, src, x, y, areaWidth, areaHeight, row,
    column, startDimension){
        this.ctx = ctx;
        this.x = x;
        this.y = y;
        this.areaWidth = areaWidth;
        this.areaHeight = areaHeight;
        this.row = row;
        this.column = column;
        this.img = new Image();
        this.img.onload = () => {};
        this.img.src = src;
        this.pixelWidth = this.areaWidth / this.column;
        this.pixelHeight = this.areaHeight / this.row;
        this.startDimension = startDimension;
    }
}


class Obstacle extends Abstract {
    constructor(ctx, src, x, y, areaWidth, areaHeight, row,
     column, startDimension){
        super(ctx, src, x, y, areaWidth, areaHeight, row,
             column, startDimension);
        this.width = 30;
        this.height = 30;
    }

    draw(){
        this.ctx.drawImage(this.img, this.startDimension, 0, this.pixelWidth, 
        this.pixelHeight, this.x, this.y, this.width, this.height);
    }

    update(time){
        this.x -= (time < 100)?5:(time > 100 && time < 300)?12:(
        time > 300 < 550)?20:(time > 550 && time < 800)?33:35;
        this.draw();
    }
}


class Player extends Abstract {
    constructor(ctx, src, x, y, areaWidth, areaHeight, row, 
    column, startDimension, maxHeight, mainPos){
        super(ctx, src, x, y, areaWidth, areaHeight, row,
             column, startDimension);
        this.width = 60;
        this.height = 60;
        this.mainPosX = mainPos[0];
        this.mainPosY = mainPos[1];
        this.vY = 0;
        this.speed = -5;
        this.gravity = .3;
        this.isJumped = true;
        this.maxHeight = maxHeight;
        this.score = 0;
    }

    draw(){
        if(this.isJumped){
            this.ctx.drawImage(this.img, 0, 0, this.pixelWidth, this.pixelHeight,
            this.x, this.y, this.width, this.height);
        }else{
            this.ctx.drawImage(this.img, this.startDimension, this.pixelHeight*2, 
            this.pixelWidth, this.pixelHeight, this.x, this.y, this.width, this.height);
        }
    }

    jump(){
        if(this.y <= this.maxHeight){
            this.vY = Math.abs(this.speed)*2;
        }
        this.y += this.vY;
        if(this.vY === Math.abs(this.speed)*2 && !this.isJumped)
            this.vY = 0;
    }

    update(){
        if(this.mainPosY > this.y)this.isJumped = true;
        else if(this.mainPosY <= this.y)this.isJumped = false;
        this.draw();
    }
}


class Floor {
    constructor(ctx, x, y, width, height, color){
        this.ctx = ctx;
        this.x = x;
        this.y = y;
        this.width = width;
        this.height = height;
        this.color  = color;
        this.speed = 10;
    }

    draw(){
        this.ctx.beginPath();
        this.ctx.fillStyle = this.color;
        this.ctx.fillRect(this.x, this.y, 
            this.width, this.height);
        this.ctx.closePath();
    }

    update(time){
        this.x -= (time < 100)?10:(time > 100 && time < 300)?18:(
        time > 300 < 550)?25:(time > 550 && time < 800)?33:40;
        this.draw();
    }

}


const init = () => {
    // game area setup
    const cvs = document.querySelector("#cvs");
    const cw = cvs.width = innerWidth;
    const ch = cvs.height = innerHeight;
    cvs.style.backgroundColor = "white";

    let ctx = cvs.getContext("2d");

    let prompt = document.querySelector("#prompt");
    let startBtn  = document.querySelector("#startBtn");

    let isPlaying = false, mainAnimeId;

    // function to get random values between ```min``` and ```max```
    const MAX_MIN = (min, max) => Math.floor(Math.random()*
        (max - min + 1) + min);

    // Radial Gradient for sun
    let sunGradient = ctx.createRadialGradient(cw-100,
         ch/4, 20, cw-100, ch/4, 50);
    sunGradient.addColorStop(0, "yellow");
    sunGradient.addColorStop(.5, "#FFD42A");
    sunGradient.addColorStop(1, "#FFFFFF");

    // time counter 
    let time = 0;
    const updateTime = () => time++;
    setInterval(updateTime, 200);

    // create the main floor
    let floor = new Floor(ctx, 0, ch-40, cw, ch+40, "#803300");

    // create the moving floor particles
    let floorParticles = [];
    const pushFloorParticles = () => {
        let amount = MAX_MIN(2, 5);
        for(let _=0; _<amount; _++){
            let x = MAX_MIN(cw + 10, cw + 30);
            let y = MAX_MIN(floor.y, ch);
            let [width, height] = [MAX_MIN(30, 55), 5];
            floorParticles.push(new Floor(ctx, x, y, width,
                 height, "#552200"));
        }
    }
    setInterval(pushFloorParticles, 300);

    //Create obstacles
    let obsData ={
        src:"https://i.ibb.co/1X08n1Z/Chromium-T-Rex-obstacle-large-sprite.png",
        areaW: 300,
        areaH: 100,
        column: 6,
        row: 1
    };
    let obstacles = [];
    let obstacleStartDimension = [0, 50, 100, 150, 200, 250];
    const pushObstacles = () => {
        //let amount = Math.ceil(Math.random()*2);
        for(let i=0; i < 1; i++){
            let x = MAX_MIN(cw + 5, cw + 10);
            let y = floor.y - 30;
            sourceX = obstacleStartDimension[Math.floor(Math.random()
                *obstacleStartDimension.length)];
            obstacles.push(new Obstacle(ctx, obsData.src, x, y, obsData.areaW, 
                obsData.areaH, obsData.row, obsData.column, 0));
        }
    }
    setInterval(pushObstacles, 4000);

    // create the cat object
    let catData = {
        src:"https://i.ibb.co/180tsjk/black-cat-running-sprite-vector-5177265.jpg",
        areaW:1000,
        areaH:780,
        column:4,
        row:4,
        x:10,
        y:floor.y - 50,
        maxY:(floor.y - 150),
        width:this.areaW / this.column
    }

    let playerStartDimension = 0;
    const updateplayerStartDimension = () => {
        playerStartDimension += (1000/4);
        if(playerStartDimension >= catData.areaW)
            playerStartDimension = 0;
    }
    setInterval(updateplayerStartDimension, 70);

    let player = new Player(ctx, catData.src, catData.x, catData.maxY, 
        catData.areaW, catData.areaH, catData.row, catData.column,
        playerStartDimension, catData.maxY, [catData.x, catData.y]);

    
    
    const main = () => {
        // score and time
        ctx.fillStyle = "black";
        ctx.font = "bold 20px Arial";
        ctx.fillText(`Time:. ${time}`, 10, 20);
        ctx.fillText(`Score:. ${player.score}`, cw-100, 20);

        // draw the sun
        ctx.save();
        ctx.beginPath();
        ctx.fillStyle = sunGradient;
        ctx.arc(cw - 100, ch/4, 50, 0, 2*Math.PI, false);
        ctx.fill();
        ctx.closePath();
        ctx.restore();

        // animate obstacles 
        for(let i=0; i<obstacles.length; i++){
            obstacles[i].update(time);
            if(obstacles[i].x + 20 < player.x + player.width && 
                player.x + player.width > obstacles[i].x + 20 &&
                obstacles[i].y < player.y + (player.height - 14) &&
                player.y + (player.height - 14) > obstacles[i].y){
                isPlaying = false;
                document.getElementById("gameover").innerHTML = "Game Over";
                prompt.style.display = "block";
                cancelAnimationFrame(mainAnimeId);
            }
            else if(obstacles[i].x < -obstacles[i].width){
                obstacles.splice(i, 1);
                player.score += 1;
            }
        }

        ctx.save();
        ctx.globalCompositeOperation = "destination-over";
        player.startDimension = playerStartDimension;
        player.update();
        player.jump();
        ctx.restore();

        floor.draw();
        for(let _=0; _<floorParticles.length; _++){
            floorParticles[_].update(time);
            if(floorParticles[_].x < -floorParticles[_].width)
                floorParticles.splice(_, 1);
        }
       
    }

    // main animation function
    const animate = () => {
        if(isPlaying){
            prompt.style.display = "none";
            ctx.clearRect(0, 0, cw, ch);
            mainAnimeId = requestAnimationFrame(animate);
            main();
        }
        else{
            prompt.style.display = "block";
            main();
        }
    }
    animate();

    // function to start/reset game
    const startGame = () => {
        isPlaying = true;
        time = 0;
        floorParticles = [];
        obstacles = [];
        player.y = catData.maxY;
        playerStartDimension = 0;
        player.score = 0;
        player.vY = player.speed;
        animate();
    }
    startBtn.addEventListener("click", startGame, false);

    const jump = (e) => {
        if(!player.isJumped)player.vY = player.speed;
    }
    window.addEventListener("keydown", jump, false);
    cvs.addEventListener("touchstart", jump, false);

}

window.addEventListener("load", init, false);
</script>
<body>
<center>
    <font color="red">Lisa olivia</font>
</center>
    <div id="prompt">
        <div id="toolbar">
            <h1 style="color:red" id="gameover"></h1><br>
            <input type="button" id="startBtn" value="🐈 PLAY">
        </div>
    </div>
    <canvas id="cvs"></canvas>
</body>
</html>