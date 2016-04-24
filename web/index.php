<?php
error_reporting(E_ALL | E_NOTICE | E_STRICT);
ini_set('display_errors',1);

define('YAXIS_ALIGN', 10);
define('YAXIS_STEPS', 10);

$colors = array(
    'red', 'blue', 'brown', 'green', 'orange', 'black'
);

function print_players($players, $link = false, $cur = '') {
    global $colors;
    $i = 0;
    foreach(array_keys($players) as $name) {
        if($link)
            echo '<a href=?player='.$name.'>';
        if($name == $cur)
            echo '<b>';
        echo '<span style="color: '.$colors[$i].';">'.$name.'</span>';
        if($name == $cur)
            echo '</b>';
        if($link)
            echo '</a>';
        echo '&nbsp;&nbsp;'."\n    ";
        $i++;
    }
    echo '<br />'."\n";
}

function print_data($players, $column) {
    global $colors;
    $i = 0;
    foreach($players as $name => $vals) {
        echo "        {\n";
        echo '            label: "'.$name.'"'.",\n";
        echo '            data: ['.implode(', ',$vals[$column]).'],'."\n";
        echo '            fillColor: "rgba(0,0,0,0)",'."\n";
        echo '            strokeColor: "'.$colors[$i].'",'."\n";
        echo '            pointColor: "'.$colors[$i].'",'."\n";
        echo '            pointHighlightStroke: "'.$colors[$i].'",'."\n";
        echo '            pointStrokeColor: "#fff",'."\n";
        echo '            pointHighlightFill: "#fff",'."\n";
        echo "        },\n";
        $i++;
    }
}

function print_graph($players, $graphs, $days, $name) {
    echo 'var data_'.$name.' = {'."\n";
    echo '    labels: ['.implode(', ',$days).'],'."\n";
    echo '    datasets: ['."\n";
    print_data($players, $name);
    echo '    ]'."\n";
    echo '};'."\n";

    echo "\n";
    echo 'var ctx_'.$name.' = document.getElementById("chart_'.$name.'").getContext("2d");'."\n";
    echo 'new Chart(ctx_'.$name.').Line(data_'.$name.', {'."\n";
    if($name == 'ranks')
        echo '    scaleLabel : "<%=-value%>",'."\n";
    else {
        $graphs[$name]['min'] -= YAXIS_ALIGN - 1;
        $graphs[$name]['max'] += YAXIS_ALIGN - 1;
        $graphs[$name]['min'] -= $graphs[$name]['min'] % YAXIS_ALIGN;
        $graphs[$name]['max'] -= $graphs[$name]['max'] % YAXIS_ALIGN;

        $graphs[$name]['step'] = round(($graphs[$name]['max'] - $graphs[$name]['min']) / YAXIS_STEPS);
        $graphs[$name]['step'] -= $graphs[$name]['step'] % YAXIS_ALIGN;
        $steps = (($graphs[$name]['max'] - $graphs[$name]['min']) / $graphs[$name]['step']);

        echo '    scaleOverride : true,'."\n";
        echo '    scaleSteps : '.$steps.','."\n";
        echo '    scaleStepWidth : '.$graphs[$name]['step'].','."\n";
        echo '    scaleStartValue : '.$graphs[$name]['min']."\n";
    }
    echo '});'."\n";
    echo "\n";
}

$curplayer = '';
if(isset($_GET['player']))
    $curplayer = $_GET['player'];

$days = array();
$players = array();
$graphs = array(
    'diff' => array(
        'step' => 0,
        'min' => PHP_INT_MAX,
        'max' => PHP_INT_MIN,
    ),
    'ranks' => array(
        'step' => 1,
        'min' => PHP_INT_MAX,
        'max' => PHP_INT_MIN,
    ),
    'points' => array(
        'step' => 0,
        'min' => PHP_INT_MAX,
        'max' => PHP_INT_MIN,
    )
);

for($i = 1; $i <= 34; $i++) {
    if(!is_file('data/'.$i.'.php'))
        break;

    $days[] = $i;
    $day = include('data/'.$i.'.php');
    if($curplayer != '') {
        foreach($day as $p) {
            if($p['name'] == $curplayer) {
                $ref = $p['points'];
                break;
            }
        }
    }
    else {
        $ref = 0;
        foreach($day as $p) {
            if($p['points'] > $ref)
                $ref = $p['points'];
        }
    }

    foreach($day as $p) {
        if(!isset($players[$p['name']])) {
            $players[$p['name']] = array(
                'diff' => array(),
                'ranks' => array(),
                'points' => array()
            );
        }

        $chg = array(
            'diff' => $p['points'] - $ref,
            'ranks' => -$p['rank'],
            'points' => $p['points']
        );
        foreach($chg as $name => $val) {
            $players[$p['name']][$name][] = $val;
            $graphs[$name]['min'] = min($graphs[$name]['min'],$val);
            $graphs[$name]['max'] = max($graphs[$name]['max'],$val);
        }
    }
}
?>
<!doctype html>
<html>
<head>
    <title>Kickprophet Bundesliga 2015/2016</title>
    <link rel="stylesheet" type="text/css" href="style.css"/>
    <script src="Chart.min.js"></script>
</head>
<body>

<h1><a href="?">Kickprophet Bundesliga 2015/2016</a></h1>

<div align="center">
    <h2>R&auml;nge nach Spieltagen</h2>
    <?php print_players($players); ?>
    <div id="ylabel_ranks" class="vertical-text">Rang</div>
    <canvas id="chart_ranks"></canvas>
    <div>Spieltag</div>

    <h2>Punktdifferenz nach Spieltagen</h2>
    <?php print_players($players, true, $curplayer); ?>
    <div id="ylabel_diff" class="vertical-text">
    Punktdifferenz <?php if($curplayer != '') echo "zu ".$curplayer; else echo "zum 1. Platz"; ?>
    </div>
    <canvas id="chart_diff"></canvas>
    <div>Spieltag</div>

    <h2>Punkte nach Spieltagen</h2>
    <?php print_players($players); ?>
    <div id="ylabel_points" class="vertical-text">Punkte</div>
    <canvas id="chart_points"></canvas>
    <div>Spieltag</div>
</div>

<script type="text/javascript">
function pad(n, width, z) {
    z = z || ' ';
    n = n + '';
    return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
}

Chart.defaults.global.scaleLabel = "<%=value%>";
Chart.defaults.global.tooltipFontFamily = 'monospace';
Chart.defaults.global.tooltipTemplate = "<%if (label){%><%=label%>: <%}%><%= value %>";
Chart.defaults.global.multiTooltipTemplate = "<%if(value < 0){%><%=pad(-value, 3)%><%}else{%><%=pad(value, 3)%><%}%> : <%= datasetLabel %>";
Chart.defaults.global.tooltipTitleTemplate = "<%= label %>. Spieltag";

<?php
print_graph($players, $graphs, $days, 'ranks');
print_graph($players, $graphs, $days, 'diff');
print_graph($players, $graphs, $days, 'points');
?>
</script>

</body>
</html>
