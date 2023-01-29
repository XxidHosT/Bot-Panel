<html>
    
    <head>
            <meta name="viewport" charset="UTF-8" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
            <title>3D Flappy Bird</title>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/92/three.js"></script>
            <script src="https://dl.dropbox.com/s/nn9wz6aimz6vzoi/OBJLoader.js"></script>
            <script src="https://dl.dropbox.com/s/2821pj5wkc2b8pe/MTLLoader.js"></script>
            <script src="https://dl.dropbox.com/s/qxgrvxduynnst3u/OrbitControls.js"></script>
                    
    </head>
    <script>
       window.addEventListener('load', function(){

/*3D Flappy Bird by Joakim Nyland

----Future plans----
- Improve pipe design?

----Updated in v5.0----
- Improved audio a lot which removed all lagg!

----Updated in v4.3----
- Smoother camera positioning
- Bug fixes

----Updated in v4.2----
- Audio improvements
- Code improvements
- Css improvements

----Updated in v4.1----
- Added start button
- Code improvements

----Updated in v4.0----
- Added best score
- Added game-over menu
- Added cylinder-crash animation
- Save best score (iOS only)
- Pipes (and trees) do not disappear upon game over

----Updated in v3.1----
- Improved code a lot by using functions
- Reposition trees instead of despawning/spawning to massivly decrease lagg
- Vertical movement is now timedependent and not fps dependent
- Horizontal movement is now time dependent not fps

----Updated in v3.0----
- Change opacity instead of wireframe
- Added trees
- Added fog
- Added shadow to bird
- Added pipe shadow to make pipes look more realistic
- Added touchevent instead of button

----Updated in v2.0----
- Added moving ground
- Css improvements

----Updated in v1.1----
- Added wireframe after passing pipes so that next pipe becomes more visible from certain angles
- Some audio fixes
- Some bug fixes

*/
// ********** Setting parameters **********
  console.log=function(){};
const SCALE = 27;
const PI = Math.PI;
var audioPlayed = 0;
var posNeg=0;
var loopVar=0;
var accVariable = 0;
var timerVar = 0;
var nowVar = 0;
var flightTime=0;
var storedZ = 0;
var jumpVariable = 1;
const maxHeight = 45;
var hitVar = 0;
var birdSpeed = (2/10)*58;
var startVar = 0;
var onMenu=1;
var pointVar = 0;
var transp1=0;
var transp2=0;
// Audio
var audioVar=0;
var bestScore = 0;
var flapCount = 0;
var scoreId = document.getElementById( 'myScoreId');
var gameOverMenu = document.getElementById( 'gameOver');
var myPlayerScore = document.getElementById( 'playerScore2');
var myBestScore = document.getElementById( 'bestScore2');
var myPlayButton = document.getElementById( 'playButton');
var playedSmack=0;
var metalSmack = 0;
var startUp = [document.getElementById("gameOverText"),document.getElementById("playerScoreText"),document.getElementById("bestScoreText")];

startUp[0].innerHTML= "FLAPTAP";
startUp[0].style.color = "yellow";
startUp[1].style.display = "none";
startUp[2].style.bottom = "60%";
myBestScore.style.bottom="40%";
myPlayerScore.style.display = "none";

// Saving user progress:
var ua = navigator.userAgent.toLowerCase();
var isAndroid = ua.indexOf("android") > -1;

if(isAndroid==0) {
// Saving user progress:
//localStorage.clear();

bestScore = parseInt(localStorage.getItem("bestScoreFlap"));
if (isNaN(bestScore) == true){
                  bestScore = 0;
            }
            else{
                myBestScore.innerHTML=bestScore;
            }
}

//Camera values
const FOV = 45;
const ASPECT = window.innerWidth/window.innerHeight;
const NEAR = 0.1;
const FAR = 2000;

var cloudGeometry = new THREE.DodecahedronGeometry(6,1)
var cloudMaterial = new THREE.MeshLambertMaterial( {
                                                color:0x00ff00,
                                                transparent:true,
                                                opacity:1
                                                } );

// ********** Creating the scene: **********
var renderer = new THREE.WebGLRenderer({ antialias: true }); //Creates a WebGL renderer using threejs library
renderer.setPixelRatio( window.devicePixelRatio ); //Prevents blurry output
renderer.setSize( window.innerWidth,window.innerHeight ); //Sets renderer size to the size of the window
renderer.setClearColor(0xA9F5F2, 1); //Makes the background color of the scene blue
renderer.shadowMapEnabled = true;
renderer.shadowMapSoft = true;
document.body.appendChild( renderer.domElement ); //Attaches renderer to DOM (initializes renderer)

var scene = new THREE.Scene(); //Creates an empty scene where we are going to add our objects

var camera = new THREE.PerspectiveCamera( FOV,ASPECT,NEAR,FAR ); //Creates a camera
camera.position.set( 30/SCALE , -200/SCALE, 40/SCALE ); //Positions the camera
camera.up.set( 0,0,1); //Sets the camera the correct direction
camera.rotation.x=-PI/2;
scene.add( camera ); //Adds the camera to the scene

var controls = new THREE.OrbitControls( camera, renderer.domElement ); //OrbitControls allows camera to be controlled and orbit around a target
controls.minDistance = 800/SCALE; //Sets the minimum distance one can pan into the scene
controls.enabled=false;

// ********** Adding light and Shadow**********
var ambientLight = new THREE.AmbientLight(0xffffff, 0.4);
scene.add(ambientLight); //Adding ambient light
var light = new THREE.DirectionalLight( 0xffffff, 1.1 );
light.position.set(0, -5, 20);
light.castShadow = true;
var d = 50;
light.shadowCameraLeft = -d;
light.shadowCameraRight = d;
light.shadowCameraTop = d;
light.shadowCameraBottom = -d;
scene.add(light);

//Fog
var fogColor = new THREE.Color(0xA9F5F2);
scene.background = fogColor;
scene.fog = new THREE.Fog(fogColor, 50, 85);


//controls
window.addEventListener("touchstart", function(e){
  if (startVar != 2 && onMenu==0){
  startVar = 1;
  storedZ = bird.position.z;
  jumpVariable=0;
  timerVar = new Date().getTime();
  hitVar=1; //Activates bird-ground hitbox
  flapp(); //flap sound
  if (bird.rotation.z<=PI/4)
  {
  bird.rotation.z+=0.4;
  }
  }
  });
window.addEventListener("touchend", function(e){
  e.preventDefault();
                      });
// ********** Loading the bird **********
var bird = new THREE.Object3D(); //Creates a new threejs 3D-object variable named bird
var mtlLoader = new THREE.MTLLoader(); //Creates an mtlLoader (to apply texture to 3d objects)
mtlLoader.load( 'https://dl.dropbox.com/s/fgerosah15v3zey/bird.mtl', function( materials ) //Prepare to set color
             {
             var objLoader = new THREE.OBJLoader(); //Creates an object loader (to load 3d objects)
             objLoader.setMaterials( materials ); //Sets color to the bird
             objLoader.load( 'https://dl.dropbox.com/s/3fc8x4znz7ejs4n/bird.obj', function ( object ) //Loads bird
                            {
                            object.traverse( function ( child )
                                            {
                                            if ( child instanceof THREE.Mesh )
                                            {
                                            child.castShadow = true; //Enables shadow
                                            }
                                            });
                            bird.add( object ); //Adds the object with material to the bird variable
                            });
             });
scene.add( bird ); //Adds bird to the scene
//Some positioning and scaling
bird.position.z= 0.6; //Positions bird onto ground
bird.position.x=0;
bird.position.y=-71/10;
bird.rotation.x=PI/2;
bird.rotation.y=PI/2;
bird.scale.x = 0.3;
bird.scale.y = 0.3;
bird.scale.z = 0.3;

//******** Functions to make stuff happen ********
//Bird motion

function jump()
{
nowVar = new Date().getTime();
flightTime = (nowVar - timerVar);

if (jumpVariable==0)
{
bird.position.z+=0.1*58*delta;
if (bird.rotation.z<=PI/4)
{
bird.rotation.z+=0.1*58*delta;
}
}

if( (bird.position.z-storedZ)>=1 )
{
accVariable = bird.position.z;
bird.rotation.z-=0.03*58*delta;
jumpVariable=1;
}

if (flightTime>=350)
{
jumpVariable=2;
}
};

function descend()
{
if (jumpVariable==2 && bird.position.z>0.3)
{
if( (accVariable-bird.position.z)<=0.5 )
{
bird.position.z-=0.05*58*delta;
if (bird.rotation.z>=-PI/2.2)
{
bird.rotation.z-=0.02*58*delta;
}
}
else if( (accVariable-bird.position.z)>0.5 )
{
bird.position.z-=0.15*58*delta;
if (bird.rotation.z>=-PI/2.2)
{
bird.rotation.z-=0.1*58*delta;
}
}
}
};

//Cylinders
var cylinderMaterial1 = new THREE.MeshLambertMaterial({
                                                    color : 0x00b300,
                                                    side: THREE.DoubleSide,
                                                    transparent:true,
                                                    opacity:1
                                                    });

var cylinderMaterial2 = new THREE.MeshLambertMaterial({
                                                    color : 0x00b300,
                                                    side: THREE.DoubleSide,
                                                    transparent:true,
                                                    opacity:1
                                                    });

function cylinderSpawn(pipeMat,yPos)
{
height = Math.floor(Math.random() * 8) + 2;

var geometry = new THREE.CylinderGeometry( 1, 1, height+4, 32,5,true );
var cyl1 = new THREE.Mesh( geometry, pipeMat );
scene.add( cyl1 );
cyl1.rotation.x=PI/2;
cyl1.position.z=(-2+(height/2));
cyl1.position.y=yPos;

var geometry = new THREE.CylinderGeometry( 1, 1, maxHeight, 32,5,true );
var cyl2 = new THREE.Mesh( geometry, pipeMat );
scene.add( cyl2 );
cyl2.rotation.x=PI/2;
cyl2.position.z=(height+(maxHeight/2)+2.5);
cyl2.position.y=yPos;

cyl1.castShadow=true;
cyl2.castShadow=true;

// y,x,z
spawnCloud(30,-14,10,cyl1);
spawnCloud(20,-35,13,cyl1);
spawnCloud(10,-14,12,cyl1);
spawnCloud(0,-35,14,cyl1);
spawnCloud(-10,-14,10,cyl1);
spawnCloud(-20,-35,12,cyl1);

spawnCloud(30,16.5,12,cyl1);
spawnCloud(0,16.5,12,cyl1);

return [cyl1,cyl2, height];
};

function spawnCloud(zValue,xValue,yValue,cylinderNum){
var cloud = new THREE.Mesh( cloudGeometry, cloudMaterial );
cloud.receiveShadow=true;

cloud.position.z=zValue;
cloud.position.x=xValue;
cloud.position.y=yValue;
cylinderNum.add(cloud);

var treeGeometry = new THREE.CylinderGeometry( 1.5, 1.5, 45,32);
var treeMaterial = new THREE.MeshBasicMaterial( {color:0x4d2600} );
var tree= new THREE.Mesh( treeGeometry, treeMaterial );

tree.receiveShadow=true;
cloud.castShadow=true;
cloud.add(tree);
tree.position.y=-45/2;
}

var pipes1 = cylinderSpawn(cylinderMaterial1,80);
var pipes2 = cylinderSpawn(cylinderMaterial2,80+55);
var cylinder1 = pipes1[0];
var cylinder2 = pipes1[1];
var heightCylinder1 = pipes1[2];
var cylinder3 = pipes2[0];
var cylinder4 = pipes2[1];
var heightCylinder2 = pipes2[2];

function cylinderRemove(cyl1,cyl2)
{
if( cyl1.position.y<=-30 )
{
swap(cyl1,cyl2);
pointVar=0; //Set this to 0 so passing through pipe registers point
}
};

function cylinderTranslate(cyl1,cyl2)
{
cyl1.position.y-=birdSpeed*delta;
cyl2.position.y-=birdSpeed*delta;
};

function cylinderCollision(cyl1,cyl2,height)
{
if ( cyl1.position.y<=-70/10 && cyl1.position.y>=-79/10)
{
if ((bird.position.z-0.2)<=height)
{
die(true);
}
else if( (bird.position.z+0.2)>=(cyl2.position.z-(maxHeight/2)))
{
die(true);
}
}
};

function birdCollision()
{
if (hitVar==1 && bird.position.z <= 0.305)
{
die(false);

soundHit.play();

}
};

function die(cylinderCrash)
{
if (cylinderCrash)
{
startVar = 2;
}
else{
playedSmack=0;
metalSmack=0;
startVar=0; //Pause game
onMenu=1;
gameOverMenu.style.display="inline";
myPlayerScore.innerHTML=loopVar;
scoreId.style.fontSize="15px";
//reset
bird.position.z=0.6; //Reset bird
hitVar=0;  //Deactivate ground hitbox

bird.rotation.x=PI/2;

if (loopVar > bestScore){
bestScore = loopVar;
myBestScore.innerHTML=bestScore;
localStorage.setItem("bestScoreFlap",bestScore);

}
loopVar=0; //reset score
pointVar = 0; //Set this to 0 so passing through pipe registers point

}
};

function score(cyl1){
scoreId.innerHTML = loopVar;
if( (cyl1.position.y<=bird.position.y-1) && cyl1.position.y>(bird.position.y-1.8) && pointVar==0 )
{
loopVar+=1; //Player score
pointVar = 1; //Gives +1 point, then this if-function becomes false

if (cyl1 == cylinder1){
transp1=1; //transparency
}
else{
transp2=1; //transparency
}

soundScore.play();

}

//Change bird speed if score is high
if (loopVar<10)
{
birdSpeed=(2/10)*58;
}
else if (loopVar<20)
{
birdSpeed = (0.25)*58;
}
else
{
birdSpeed = (0.3)*58;
}
};

function loadAudio()
{
//Threejs Audio

var listener = new THREE.AudioListener();
camera.add( listener );
window.flySound = new THREE.Audio( listener );
var audioLoader = new THREE.AudioLoader();
audioLoader.load( 'https://dl.dropbox.com/s/w25uvq7a7wo1uyi/sfx_wing.mp3', function( buffer ) {
    flySound.setBuffer( buffer );
    flySound.setLoop( false );
    flySound.setVolume( 1 );
    //flySound.play();
});


window.flySound2 = new THREE.Audio( listener );
audioLoader.load( 'https://dl.dropbox.com/s/w25uvq7a7wo1uyi/sfx_wing.mp3', function( buffer ) {
    flySound2.setBuffer( buffer );
    flySound2.setLoop( false );
    flySound2.setVolume( 1 );
    //flySound2.play();
});

window.flySound3 = new THREE.Audio( listener );
audioLoader.load( 'https://dl.dropbox.com/s/w25uvq7a7wo1uyi/sfx_wing.mp3', function( buffer ) {
    flySound3.setBuffer( buffer );
    flySound3.setLoop( false );
    flySound3.setVolume( 1 );
    //flySound3.play();
});

window.soundHit = new THREE.Audio( listener );
audioLoader.load( 'https://dl.dropbox.com/s/a15jq547hnt10e4/sfx_hit.mp3', function( buffer ) {
    soundHit.setBuffer( buffer );
    soundHit.setLoop( false );
    soundHit.setVolume( 1 );
    //soundHit.play();
});

window.metalSound = new THREE.Audio( listener );
audioLoader.load( 'https://dl.dropbox.com/s/8hi8u4jxxeol0an/metal.mp3?dl=0', function( buffer ) {
    metalSound.setBuffer( buffer );
    metalSound.setLoop( false );
    metalSound.setVolume( 1 );
    //metalSound.play();
});
                  
window.soundScore = new THREE.Audio( listener );
audioLoader.load( 'https://dl.dropbox.com/s/yuhbesjvnuwxevl/sfx_point.mp3', function( buffer ) {
    soundScore.setBuffer( buffer );
    soundScore.setLoop( false );
    soundScore.setVolume( 1 );
    //soundScore.play();
});
                            
audioVar=1;

};

//Ground
var texture;
texture = new THREE.TextureLoader().load( "https://dl.dropbox.com/s/f8exr3zow9nqsol/grass.jpg?dl=0" );
var groundmat = new THREE.MeshLambertMaterial({ //Sets color and material attributes for plane
                                            color: 0xcc6600,
                                            map:texture,
                                            side: THREE.DoubleSide //Ground visible from both sides
                                            });
var geometry = new THREE.SphereGeometry(3000,100,100);
var ballGround = new THREE.Mesh( geometry, groundmat );
scene.add( ballGround );
ballGround.receiveShadow=true;
ballGround.position.z=-2999.6;

//******* POV ******
var povZvar=2;
function enablePOV()
{
camera.position.set( 15, -25, 5 ); //Positions the camera

if (povZvar<bird.position.z-0.15){
    povZvar+=0.1*58*delta;
}
else if(povZvar>bird.position.z+0.15){
    povZvar-=0.1*58*delta;
}

camera.lookAt(bird.position.x,bird.position.y+10,3);
};

function swap(cyl1,cyl2)
{
if (cyl1 == cylinder1)
{
heightCylinder1 = Math.floor(Math.random() * 8) + 2;
var height=heightCylinder1;
}
else{
heightCylinder2 = Math.floor(Math.random() * 8) + 2;
var height=heightCylinder2;
}

var geometry = new THREE.CylinderGeometry( 1, 1, height+4, 32,5,true );

cyl1.geometry.dispose();
cyl1.geometry = geometry.clone();

cyl1.position.z=(-2+(height/2));
cyl1.position.y=80;

cyl2.position.z=(height+(maxHeight/2)+2.5);
cyl2.position.y=80;

cyl1.material.opacity=1;
cyl2.material.opacity=1;

}

function pipeOpacity(transp,cyl1,cyl2)
{
if (transp==1 && cyl1.position.y<=bird.position.y)
{
if (cyl1.material.opacity>0.4)
{
cyl1.material.opacity-=0.025;
cyl2.material.opacity-=0.025;
}
}
}

function cylCrash()
{
bird.rotation.x+=0.2*58*delta;
if (metalSmack==0){
    metalSound.play();
    metalSmack=1;
}
                  
if (bird.position.z<=0.6 && playedSmack==0){

soundHit.play();

playedSmack=1;
}
                  
if (bird.position.z>=0.6){
bird.position.z-=0.2*58*delta;
}
else if (cylinder1.position.y>13 && cylinder3.position.y>cylinder1.position.y){
die(false);
}
else if(cylinder3.position.y>13 && cylinder1.position.y>cylinder3.position.y){
die(false);
}
                
                  
cylinder1.position.y+=0.3*58*delta;
cylinder2.position.y+=0.3*58*delta;
cylinder3.position.y+=0.3*58*delta;
cylinder4.position.y+=0.3*58*delta;

cylinder1.material.opacity = 1;
cylinder2.material.opacity = 1;
cylinder3.material.opacity = 1;
cylinder4.material.opacity = 1;
transp1 = 0;
transp2 = 0;

}
                  
myPlayButton.addEventListener("touchend",function(){
    if (audioVar == 0){
        loadAudio();
        
        startUp[0].innerHTML= "GAME OVER";
        startUp[0].style.color = "red";
        startUp[1].style.display = "inline";
        startUp[2].style.bottom = "45%";
        myBestScore.style.bottom="25%";
        myPlayerScore.style.display = "inline";
    }
    gameOverMenu.style.display="none";
    onMenu=0;
})

function flapp(){

if (flapCount == 0){
    flySound.play();
}
else if (flapCount == 1){
    flySound2.play();
}  
else if (flapCount == 2){
    flySound3.play();
}  

flapCount+=1;

if (flapCount==3){
    flapCount=0;
}
}

var clock = new THREE.Clock();
var delta=0;
clock.start(); //makes rendering timedependent

//********** Render function **********
var render = function ()
{
//Make movement time-dependent and not fps-dependent

delta = clock.getDelta();

if (onMenu==1){
scoreId.style.display="none";
}
else{
scoreId.style.display="inline";
}
                  
if(startVar==0){
scoreId.innerHTML="JUMP TO START";
scoreId.style.fontSize="15px";
}
else if (startVar==1) //If game started, do all the functions inside
{
scoreId.style.fontSize="25px";
//Bird fly function
jump();

//Gravity function
descend();

//Ground movement
ballGround.rotation.x+=0.00009;

//Change "old" pipe back with new height
cylinderRemove(cylinder1,cylinder2);
cylinderRemove(cylinder3,cylinder4);

//Makes pipes translate towards bird
cylinderTranslate(cylinder1,cylinder2);
cylinderTranslate(cylinder3,cylinder4);

//Obstacle collision
cylinderCollision(cylinder1,cylinder2,heightCylinder1 );
cylinderCollision(cylinder3,cylinder4,heightCylinder2 );
birdCollision();

//Registers score
score(cylinder1);
score(cylinder3);

//opacity
pipeOpacity(transp1,cylinder1,cylinder2);
pipeOpacity(transp2,cylinder3,cylinder4);
}
else if(startVar == 2)    {
cylCrash();
}
//camera position
enablePOV();

requestAnimationFrame(render); //render function is called every frame!
renderer.render(scene, camera); //We need this in the loop to perform the rendering
};
render();
});

//Made by Joakim Nyland


    </script>
    <style>
        body{
    position:fixed;
}

@font-face {
    font-family: 'pixeledregular';
    src: url('https://dl.dropbox.com/s/csi7ncfsymeqsca/pixeled-webfont.woff2?dl=0') format('woff2');
    font-weight: normal;
    font-style: normal;
}

.myTexts{
    position: fixed;
    left: 50%;
    transform: translate(-50%, 50%);
    text-align:center;
    font-weight:bold;
    font-family: 'pixeledregular';
    
            -moz-user-select: none;
    -webkit-user-drag: none;
    -webkit-user-select: none;
    -ms-user-select: none;
}

#myScoreId {
    color: white;
    width:80%;
    opacity:0.8;
    bottom: 20%;
    margin-top: -25px;
    font-size: 15px;
    text-shadow:1px 1px 0px black;
}

#gameOver {
    float:left;
    position: fixed;
    left: 50%;
    background-color:#ffe680;
    border:1px solid black;
    border-radius:10%;
    opacity:1;
    width:70%;
    height:40%;
    bottom: 50%;
    margin-top: -25px;
    transform: translate(-50%, 50%);
    display:inline;
}

#gameOverText{
    bottom:120%;
    width:120%;
    color:#ff0000;
    font-size: 25px;
    text-shadow:1px 1px 0px black; 
}


#playerScoreText{
        color: #ff9900;
        bottom: 90%;
        font-size: 20px;
        text-shadow:1px 1px 0px white;
}


#playerScore2{
    color: white;
    bottom: 70%;
    font-size: 25px;
    text-shadow:1px 1px 0px black;
}

#bestScoreText{
    color: #ff9900;
    bottom: 45%;
    font-size: 20px;
    text-shadow:1px 1px 0px white;
}


#bestScore2{
    color: white;
    bottom: 25%;
    font-size: 25px;
    text-shadow:1px 1px 0px black;
}

.button {
    text-align: center;
    display: inline-block;
    position: fixed;
    transform: translate(-50%, 50%);
    font-family: "Verdana", Times, serif;
    border: 2px solid black;
    letter-spacing: 2px;
    border-radius: 15%;
    color:#000000;
    
        -moz-user-select: none;
    -webkit-user-drag: none;
    -webkit-user-select: none;
    -ms-user-select: none;
}

#playButton {
    left: 50%;
    font-size: 16px;
    width:60%;
    height:45%;
    bottom: -30%;
    background: linear-gradient(to bottom, white 50%,  #e6e6e6 50%);
    opacity:1;
    border: 2px solid #000000;
}

.arrow-right {
  width: 0; 
  height: 0; 
  border-top: 30px solid transparent;
  border-bottom: 30px solid transparent;
  
  border-left: 50px solid green;
}

#right{
position:absolute;
    left:50%;
    bottom:50%;
    transform: translate(-50%, 50%);
}    </style>
<body style="margin: 0px;">
        
        <div id="gameOver">
        
        <div class="myTexts" id="gameOverText">GAME OVER</div>
        
        <div class="myTexts" id="playerScoreText">SCORE</div>
    <div class="myTexts" id="playerScore2">0</div>
    
            <div class="myTexts" id="bestScoreText">BEST</div>
    <div class="myTexts" id="bestScore2">0</div>
    
    <button class="button" id="playButton">

<div class="arrow-right" id="right"></div>

</button>
        
        </div>
        <div class="myTexts" id="myScoreId">JUMP TO START</div>
        
    </body>
    
</html>