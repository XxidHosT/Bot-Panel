<!-- 
    TURN ON THE LIGHT
    CREATED BY: VITRUVIAN
    APRIL 05, 2018
-->
<!DOCTYPE HTML>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta charset="utf-8">
        <link href="https://fonts.googleapis.com/css?family=Oswald:700" rel="stylesheet">
        <title>Turn On the Light</title>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <script src="https://hammerjs.github.io/dist/hammer.js"></script>
    </head>
    <style>
/*
    TURN ON THE LIGHT
    CREATED BY: VITRUVIAN
    APRIL 05, 2018
*/
body {
    font-family: 'Oswald', sans-serif;
    font-size:25px;
    font-weight:bold;
    margin:0 0 0 0;
    background-color:#f0f0f0;
}
* {
    -webkit-touch-callout:none;
    -webkit-user-select:none;
    -moz-user-select:none;
    -ms-user-select:none;
    user-select:none;
}
#game {
    position:relative;
    margin:auto;
    width:360px;
    height:460px;
}
circle,polyline,path,line {
    display:none;
}
#paths path,#paths line,#paths circle {
    display:block;
}
#complete circle,#complete path {
    display:block;
}
.blocks {
    position:absolute;
    width:72px;
    height:72px;
    display:none;
    z-index:400;
}
#complete {
    position:absolute;
    top:0;
    left:0;
    width:360px;
    height:460px;
    z-index:200;
    display:none;
}
#lighton {
    position:absolute;
    top:0;
    right:0;
    width:120px;
    height:173px;
    background-image:url(https://i.imgur.com/tTabsv6.png);
}
.b_levels {
    position:absolute;
    width:56px;
    height:47px;
    border:2px solid;
    background-color:#e0e0e0;
    border-radius:10px;
    font-size:15px;
    cursor:pointer;
    z-index:400;
}
.b_levels div {
    position:absolute;
    top:15px;
    left:0;
    width:100%;
    font-size:21px;
}
.btn {
    position:absolute;
    top: 127px;
    border:2px solid;
    background-color:#e0e0e0;
    border-radius:10px;
    font-size:15px;
    cursor:pointer;
    padding:5px 10px 5px 10px;
    display:none;
    z-index:200;
}

    </style>
    <script>
/*
    TURN ON THE LIGHT
    CREATED BY: VITRUVIAN
    APRIL 05, 2018
*/
var playing = true;
// levels.length = 12 (number of levels)
// each element of levels array is composed of an array
// The game board is a 5x4 matrix
// I define values for the sides of
// each cell of the 5x4 matrix:
// 1 = top
// 2 = right
// 3 = bottom
// 4 = left
// each element of this sub array defines
// the piece (.blocks) of the game starting top-left
// each of the values define what to show,
// example: 141_90
    // 14 indicates that the curved line
    // will go from top (1) to left (4)
    // the third caharacter is a 1
    // this indicates where to draw the arrow
    // as this 3rd character is 1,
    // the arrow will be shown at top
    // the underscore separates the arrow
    // definition from the initial rotation (deg)
    // this deg values can be 0 (no rotation al all)
    // is deg===0 then the brick background is shown
// there are some special values for this elements:
    // x = brick with a cross
    // y = brick with top-right & bottom-left lines
    // z = brick with top-left & bottom-right lines
var levels = [
    ["122_0","242_90","242_0","242_-90","141_0"],
    ["122_180","343_0","","232_0","141_90","","122_90","242_-90","141_-90"],
    ["x","343_90","232_-90","244_180","131_90","122_-90","242_-180","y","z","141_90","","122_-90","122_-90","141_90"],
    ["133_90","343_-90","232_180","242_90","141_-90","122_90","y","121_90","x","344_-90","233_90","122_-90","343_90","z","141_180","121_90","242_0","122_180","141_90"],
    ["122_90","343_180","232_90","242_180","141_-90","233_180","z","x","y","344_180","133_90","y","z","z","141_90","122_180","242_-90","141_180","233_180","121_-90"],
    ["y","242_90","343_-90","232_180","z","232_90","242_180","x","141_180","141_180","131_90","122_180","x","244_-90","344_90","122_0","244_180","144_90","122_90","144_0"],
    ["122_90","343_-90","232_180","242_-90","141_-90","233_180","z","z","242_90","343_90","133_180","121_180","x","344_90","133_-90","122_-90","242_90","141_180","121_180","144_90"],
    ["133_-90","232_90","343_180","232_180","141_90","122_0","z","y","y","344_-90","232_180","z","y","x","141_90","121_-90","144_180","121_90","144_90","144_-90"],
    ["122_90","242_-90","343_90","344_180","131_-90","233_90","244_90","144_180","z","141_180","133_-90","121_-90","z","y","344_90","122_-90","242_180","141_-90","122_180","141_90"],
    ["133_-90","233_180","344_180","232_90","141_-90","122_-90","y","x","z","343_90","233_180","z","x","141_-90","133_90","122_180","141_-90","121_90","244_180","144_-90"],
    ["122_180","343_-90","232_90","343_-90","131_90","233_180","144_90","131_-90","133_180","131_-90","133_90","232_00","z","z","141_180","122_90","141_-90","122_-90","141_180"],
    ["133_180","232_180","242_-90","343_90","131_90","122_180","x","343_90","122_-90","141_180","233_90","x","x","244_180","344_-90","122_-90","141_180","122_90","242_180","141_-90"]
];
// toverify array contain the matrix elements to be verify,
// when rotation degree for each elements of toverify iz zero
// then the level is complete - see verify() function
var toverify = [
    [1,3],
    [0,4,6,7,8],
    [4,5,6,9,12,13],
    [0,2,3,4,5,7,9,11,12,14,17,18],
    [0,1,2,3,4,5,10,15,16,17],
    [1,2,3,5,6,8,10,16,17],
    [0,1,2,3,4,5,8,9,10,11,13,14,15,16,17,18,19],
    [0,1,2,3,4,5,9,10,14,15,16,17,18],
    [0,1,2,4,5,6,7,9,10,14,15,16,17,18,19],
    [0,1,2,3,4,5,9,10,13,14,15,16,17,18,19],
    [0,1,2,3,4,5,6,7,8,9,10,11,14,15,16,17,18,19],
    [0,1,2,3,4,5,7,8,9,10,13,14,15,16,17,18,19]
];
// completed_paths is an array with SVG paths to show
// the animation when a level is completed
var completed_paths = [
    '<path id="motionpath" fill="none" stroke="#000000" stroke-width="4" d="M36 68l0 104c0,20 16,36 36,36l216 0c20,0 36,-16 36,-36"/>',
    '<path id="motionpath" fill="none" stroke="#000000" stroke-width="4" d="M36 68l0 104c0,20 16,36 36,36 20,0 36,16 36,36 0,20 16,36 36,36l72 0c20,0 36,-16 36,-36 0,-20 16,-36 36,-36 20,0 36,-16 36,-36"/>',
    '<path id="motionpath" fill="none" stroke="#000000" stroke-width="4" d="M36 68l0 104 0 72c0,20 16,36 36,36l72 0c20,0 36,16 36,36 0,20 16,36 36,36 20,0 36,-16 36,-36m0 0c0,-20 16,-36 36,-36 20,0 36,-16 36,-36l0 -72"/>',
    '<path id="motionpath" fill="none" stroke="#000000" stroke-width="4" d="M36 68l0 104 0 72c0,20 16,36 36,36 20,0 36,16 36,36 0,20 16,36 36,36 20,0 36,16 36,36 0,20 16,36 36,36 20,0 36,-16 36,-36 0,-20 16,-36 36,-36 20,0 36,-16 36,-36 0,-20 -16,-36 -36,-36l-72 0c-20,0 -36,-16 -36,-36 0,-20 16,-36 36,-36l72 0c20,0 36,-16 36,-36"/>',
    '<path id="motionpath" fill="none" stroke="#000000" stroke-width="4" d="M36 68l0 104c0,20 16,36 36,36 20,0 36,16 36,36 0,20 -16,36 -36,36 -20,0 -36,16 -36,36l0 72c0,20 16,36 36,36l72 0c20,0 36,-16 36,-36 0,-20 16,-36 36,-36 20,0 36,-16 36,-36 0,-20 -16,-36 -36,-36l-72 0c-20,0 -36,16 -36,36 0,20 16,36 36,36 20,0 36,-16 36,-36l0 -72c0,-20 16,-36 36,-36l72 0c20,0 36,-16 36,-36"/>',
    '<path id="motionpath" fill="none" stroke="#000000" stroke-width="4" d="M36 68l0 104c0,20 16,36 36,36l72 0c20,0 36,16 36,36l0 72 0 72c0,20 -16,36 -36,36l-72 0c-20,0 -36,-16 -36,-36l0 -72c0,-20 16,-36 36,-36l72 0 72 0c20,0 36,-16 36,-36 0,-20 16,-36 36,-36 20,0 36,-16 36,-36"/>',
    '<path id="motionpath" fill="none" stroke="#000000" stroke-width="4" d="M36 68l0 104c0,20 16,36 36,36 20,0 36,16 36,36 0,20 -16,36 -36,36 -20,0 -36,16 -36,36l0 72c0,20 16,36 36,36l72 0c20,0 36,-16 36,-36l0 -72c0,-20 16,-36 36,-36l72 0c20,0 36,16 36,36l0 72c0,20 -16,36 -36,36 -20,0 -36,-16 -36,-36 0,-20 -16,-36 -36,-36l-72 0c-20,0 -36,-16 -36,-36 0,-20 16,-36 36,-36 20,0 36,-16 36,-36 0,-20 16,-36 36,-36l72 0c20,0 36,-16 36,-36"/>',
    '<path id="motionpath" fill="none" stroke="#000000" stroke-width="4" d="M36 68l0 104 0 72c0,20 16,36 36,36 20,0 36,-16 36,-36 0,-20 16,-36 36,-36 20,0 36,16 36,36 0,20 16,36 36,36 20,0 36,16 36,36l0 72c0,20 -16,36 -36,36 -20,0 -36,-16 -36,-36 0,-20 -16,-36 -36,-36 -20,0 -36,16 -36,36 0,20 -16,36 -36,36 -20,0 -36,-16 -36,-36 0,-20 16,-36 36,-36 20,0 36,-16 36,-36 0,-20 16,-36 36,-36 20,0 36,16 36,36 0,20 16,36 36,36l72 0c20,0 36,-16 36,-36 0,-20 -16,-36 -36,-36 -20,0 -36,-16 -36,-36 0,-20 16,-36 36,-36 20,0 36,-16 36,-36"/>',
    '<path id="motionpath" fill="none" stroke="#000000" stroke-width="4" d="M36 68l0 104c0,20 16,36 36,36l72 0c20,0 36,16 36,36 0,20 -16,36 -36,36l-72 0c-20,0 -36,16 -36,36l0 72c0,20 16,36 36,36l72 0c20,0 36,-16 36,-36 0,-20 16,-36 36,-36 20,0 36,16 36,36 0,20 16,36 36,36 20,0 36,-16 36,-36 0,-20 -16,-36 -36,-36 -20,0 -36,-16 -36,-36 0,-20 16,-36 36,-36 20,0 36,-16 36,-36l0 -72"/>',
    '<path id="motionpath" fill="none" stroke="#000000" stroke-width="4" d="M36 68l0 104 0 72c0,20 16,36 36,36 20,0 36,16 36,36 0,20 -16,36 -36,36 -20,0 -36,16 -36,36 0,20 16,36 36,36 20,0 36,-16 36,-36 0,-20 16,-36 36,-36l72 0c20,0 36,-16 36,-36 0,-20 16,-36 36,-36 20,0 36,16 36,36l0 72c0,20 -16,36 -36,36l-72 0c-20,0 -36,-16 -36,-36l0 -72 0 -72c0,-20 -16,-36 -36,-36 -20,0 -36,16 -36,36 0,20 16,36 36,36l72 0c20,0 36,-16 36,-36 0,-20 16,-36 36,-36 20,0 36,-16 36,-36"/>',
    '<path id="motionpath" fill="none" stroke="#000000" stroke-width="4" d="M36 68l0 104c0,20 16,36 36,36 20,0 36,16 36,36 0,20 -16,36 -36,36 -20,0 -36,16 -36,36l0 72c0,20 16,36 36,36 20,0 36,-16 36,-36 0,-20 16,-36 36,-36 20,0 36,-16 36,-36l0 -72c0,-20 16,-36 36,-36 20,0 36,16 36,36l0 72c0,20 -16,36 -36,36 -20,0 -36,16 -36,36 0,20 16,36 36,36 20,0 36,-16 36,-36 0,-20 16,-36 36,-36 20,0 36,-16 36,-36l0 -72 0 -72"/>',
    '<path id="motionpath" fill="none" stroke="#000000" stroke-width="4" d="M36 68l0 104 0 72c0,20 16,36 36,36l72 0c20,0 36,16 36,36l0 72c0,20 16,36 36,36l72 0c20,0 36,-16 36,-36 0,-20 -16,-36 -36,-36l-72 0 -72 0 -72 0c-20,0 -36,16 -36,36 0,20 16,36 36,36 20,0 36,-16 36,-36l0 -72 0 -72c0,-20 16,-36 36,-36l72 0c20,0 36,16 36,36 0,20 16,36 36,36 20,0 36,-16 36,-36l0 -72"/>'
];
$(document).ready(function() {
    $("body").delegate("#b_next","click",function() {
        var level = parseInt($("#level span").html());
        show_level(level);
    });
    $("body").delegate("#b_menu","click",function() {
        $("body").css("background-color","#f0f0f0");
        $("#complete").html("");
        $("#b_menu,#b_next,#level,#moves").hide();
        $("#info,#mainpath,#maincircle,#div_levels,#lighton").show();
        $(".blocks").remove();
    });
    $("body").delegate(".b_levels","click",function() {
        var level = parseInt($(this).children("div").html());
        $("#info,#div_levels").hide();
        $("#mainpath,#maincircle").hide();
        show_level(level-1);
    });
    $("body").delegate(".blocks","click",function() {
        // rotates the block 90 deg clockwise
        if (playing) {
            blocked = parseInt($(this).data("blocked"));
            if (blocked===0) {
                $(".blocks").css("z-index",100);
                angle = parseInt($(this).data("angle"))+90;
                $(this).css({
                    "transition":"200ms linear all",
                    "z-index":"101",
                    "transform:":"rotate("+angle+"deg)",
                    "-moz-transform":"rotate("+angle+"deg)",
                    "-webkit-transform":"rotate("+angle+"deg)",
                    "-o-transform":"rotate("+angle+"deg)",
                    "-ms-transform":"rotate("+angle+"deg)",
                    "-khtml-transform":"rotate("+angle+"deg)"
                });
                $(this).data("angle",angle);
                moves = parseInt($("#moves span").html());
                $("#moves span").html(moves+1);
                verify();
            }
        }
    });
    show_level_buttons();
});
function show_level_buttons() {
    var level = 1;
    for (var i=0;i<3;i++) {
        for (var j=0;j<4;j++) {
            bhtml = '<div class="b_levels" style="top:'+(i*55)+'px;left:'+(2+j*65)+'px;">LEVEL<div>'+level+'</div></div>';
            $("#div_levels").append(bhtml);
            level++;
        }
    }
}
function verify() {
    // see if all block's rotation angle is zero
    // using javascript mod
    complete = true;
    var level = parseInt($("#level span").html());
    var verify_paths = toverify[level-1];
    for (var i=0;i<verify_paths.length;i++) {
        angle = parseInt($("#block_"+verify_paths[i]).data("angle"));
        mod = angle%360;
        if (mod!=0) {
            complete = false;
        }
    }
    if (complete) {
        playing = false;
        completed();
    }
}
function completed() {
    // show the level completed_paths
    $(".a1,.a2,.a3,.a4").hide();
    $(".blocks circle,.blocks path,.blocks line").hide();
    $(".blocks").css({
        "cursor":"default",
        "background-image":"url()"
    });
    var level = parseInt($("#level span").html());
    $("#complete path:eq("+(level-1)+")").show();
    var completed_path = completed_paths[level-1];
    html = completed_path;
    html += '<circle id="mover" fill="#fffd56" cx="0" cy="0" r="7">';
    html += '</circle>'
    $("#complete").html(html).show();
    var myPath = document.getElementById("motionpath");
    var length = myPath.getTotalLength();
    var speed = length/400;
    html = '<animateMotion dur="'+speed+'s" calcmode="linear" repeatCount="indefinite">';
    html += '<mpath xlink:href="#motionpath"/>';
    $("#complete circle").html(html);
    var level = parseInt($("#level span").html());
    $(".b_levels:eq("+(level-1)+")").css({
        "border":"2px solid #006100",
        "color":"#006100",
        "background-color":"#ffffff"
    });
    if (level<12) {
        $("#b_next").show();
    }
    $("body").css("background-color","#f0f0f0");
    $("#lighton").show();
}
function show_blocks() {
    var paths = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="72px" height="72px" viewBox="0 0 72 72" E="xMidYMid meet">';

    paths += '<circle fill="#9a9a9a" cx="36" cy="36" r="36"/>';

    paths += '<polyline fill="none" class="a1" stroke="#000000" stroke-width="4" stroke-linecap="round" points="49,13 36,0 23,13 "/>';
    paths += '<polyline fill="none" class="a2" stroke="#000000" stroke-width="4" stroke-linecap="round" points="59,23 72,36 59,49 "/>';
    paths += '<polyline fill="none" class="a3" stroke="#000000" stroke-width="4" stroke-linecap="round" points="49,59 36,72 23,59 "/>';
    paths += '<polyline fill="none" class="a4" stroke="#000000" stroke-width="4" stroke-linecap="round" points="13,23 0,36 13,49 "/>';

    paths += '<path fill="none" class="p12" stroke="#000000" stroke-width="4" d="M36 0c0,20 16,36 36,36"/>';
    paths += '<path fill="none" class="p23" stroke="#000000" stroke-width="4" d="M36 72c0,-20 16,-36 36,-36"/>';
    paths += '<path fill="none" class="p34" stroke="#000000" stroke-width="4" d="M36 72c0,-20 -16,-36 -36,-36"/>';
    paths += '<path fill="none" class="p14" stroke="#000000" stroke-width="4" d="M36 0c0,20 -16,36 -36,36"/>';

    paths += '<line fill="none" class="p13" stroke="#000000" stroke-width="4" x1="36" y1="0" x2="36" y2= "72" />';
    paths += '<line fill="none" class="p24" stroke="#000000" stroke-width="4" x1="72" y1="36" x2="0" y2= "36" />';
    paths += '';
    paths += '</svg>';
    var c = 0;
    for (var i=0;i<4;i++) {
        for (var j=0;j<5;j++) {
            $("#game").append("<div class='blocks' id='block_"+c+"' data-angle='0' style='top:"+(172+i*72)+"px;left:"+(j*72)+"px;'>"+paths+"</div>");
            c++;
        }
    }
}
function show_level(level) {
    // show blocks according level
    $("body").css("background-color","#c0c0c0");
    playing = true;
    $("#complete").html("").hide();
    $(".blocks").remove();
    show_blocks();
    $("#level span").html(level+1);
    $("#moves span").html(0);
    $("#level,#moves,#b_menu").show();
    $("#lighton,#b_next").hide();
    level_paths = levels[level];
    $(".blocks").css({
        "background-image":"url(https://i.imgur.com/0Fc0TuZ.png)",
        "cursor":"default"
    }).data("angle","0").data("blocked","1").show();
    $(".blocks circle,.blocks polyline,.blocks path,.blocks line").hide();
    for (var i=0;i<level_paths.length;i++) {
        path = level_paths[i];
        switch (path) {
            case "":
                break;
            case "x":
                $(".blocks:eq("+i+") .p13").show();
                $(".blocks:eq("+i+") .p24").show();
                break;
            case "y":
                $(".blocks:eq("+i+") .p12").show();
                $(".blocks:eq("+i+") .p34").show();
                break;
            case "z":
                $(".blocks:eq("+i+") .p23").show();
                $(".blocks:eq("+i+") .p14").show();
                break;
            default:
                arrow = path.substr(2,1);
                angle = parseInt(path.substr(4));
                path = path.substr(0,2);
                $(".blocks:eq("+i+") .p"+path).show();
                bgi = ""
                if (angle!==0) {
                    $(".blocks:eq("+i+") .a"+arrow).show();
                    $(".blocks:eq("+i+") circle").show();
                    $(".blocks:eq("+i+")").css({
                        "background-image":"url()",
                        "cursor":"pointer"
                    }).data("angle",angle).data("blocked",0);
                    rotate_block(i,angle);
                }
                break;
        }
    }
}
function rotate_block(n,angle) {
    $(".blocks:eq("+n+")").css({
        "transform:":"rotate("+angle+"deg)",
        "-moz-transform":"rotate("+angle+"deg)",
        "-webkit-transform":"rotate("+angle+"deg)",
        "-o-transform":"rotate("+angle+"deg)",
        "-ms-transform":"rotate("+angle+"deg)",
        "-khtml-transform":"rotate("+angle+"deg)",
    }).data("angle",angle);
}

    </script>
    <body>
        <div id="game">
            <svg id="paths" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="360px" height="460px" viewBox="0 0 360 460" E="xMidYMid meet">
                <path fill="#000000" d="M21 41l7 0 0 -14c0,-1 1,-2 2,-2 1,0 2,1 2,2l0 14 8 0 0 -14c0,-1 1,-2 2,-2 1,0 2,1 2,2l0 14 7 0c1,0 2,0 2,2l0 3c0,2 -1,3 -2,3l-1 0 0 6c0,5 -4,9 -8,9l0 3c0,1 -1,2 -2,2l-8 0c-1,0 -2,-1 -2,-2l0 -3c-4,0 -8,-4 -8,-9l0 -6 -1 0c-1,0 -2,-1 -2,-3l0 -3c0,-2 1,-2 2,-2z"/>
                <line fill="none" stroke="#000000" stroke-width="4" x1="36" y1="68" x2="36" y2= "172" />
                <path fill="none" stroke="#000000" stroke-width="4" stroke-linecap="round" d="M324 172l0 -16c0,-10 -9,-18 -18,-17 -3,1 -7,3 -9,2 -4,-2 -5,-9 -2,-9 2,0 16,6 14,2 0,-2 -21,-8 -19,-11 3,-3 24,8 23,3 -1,-4 -24,-7 -23,-12 1,-4 26,9 27,4 0,-4 -22,-9 -25,-11 -6,-5 -3,-13 -4,-17 -1,-3 -4,-8 -5,-10 -1,-1 -5,-4 -7,-12 -2,-9 -4,-18 4,-28 8,-10 24,-14 34,-11 10,2 25,14 23,33 -1,9 -7,15 -12,20 -12,10 -6,23 -12,24 -2,1 -4,1 -7,1 -6,-2 -3,-18 -4,-25 -1,-6 -5,-7 -8,-11 -2,-3 1,-5 5,-2 2,3 2,-1 4,-4 3,-4 6,-1 6,1 0,3 1,7 4,1 2,-4 7,-1 5,4 -3,6 -5,6 -10,9"/>
                <path id="mainpath" fill="none" stroke="#000000" stroke-width="4" d="M36 68l0 104 0 216c0,20 16,36 36,36l216 0c20,0 36,-16 36,-36l0 -216"/>
                <circle id="maincircle" fill="#fffd56" cx="0" cy="0" r="7">
                    <animateMotion dur="1.5s" calcmode="linear" repeatCount="indefinite">
                    <mpath xlink:href="#mainpath"/>
                </circle>
            </svg>
            <div style="position:absolute;top:15px;left:0;width:320px;text-align:center;">TURN ON</div>
            <div style="position:absolute;top:40px;left:0;width:320px;text-align:center;">THE LIGHT</div>
            <div id="level" style="position:absolute;top:70px;left:0;width:320px;text-align:center;font-size:21px;display:none;">Level: <span>0</span></div>
            <div id="moves" style="position:absolute;top:95px;left:0;width:320px;text-align:center;font-size:21px;display:none;">Moves: <span>0</span></div>
            <svg id="complete" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="360px" height="460px" viewBox="0 0 360 460" E="xMidYMid meet">
            </svg>
            <div id="lighton"></div>
            <div id="info" style="position:absolute;top:90px;left:50px;width:220px;height:auto;font-size:18px;text-align:center;">
                Click on cable<br>
                segments to rotate<br>
                clockwise until all<br>
                the segments are in<br>
                the correct position.
            </div>
            <div id="div_levels" style="position:absolute;top:248px;left:52px;width:260px;height:165px;font-size:18px;text-align:center;"></div>
            <div class="btn" id="b_menu" style="left:85px;">MENU</div>
            <div class="btn" id="b_next" style="left:150px;">NEXT LEVEL</div>
        </div>
    </body>
</html>
