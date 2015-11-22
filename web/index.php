<?php
error_reporting(E_ALL | E_NOTICE | E_STRICT);
ini_set('display_errors',1);

$colors = array(
    'red', 'blue', 'brown', 'green', 'orange', 'black'
);

function print_players($players) {
    global $colors;
    $i = 0;
    foreach(array_keys($players) as $name) {
        echo '<span style="color: '.$colors[$i].';">'.$name.'</span>&nbsp;&nbsp;'."\n    ";
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

function print_graph($players, $days, $name) {
    echo 'var data_'.$name.' = {'."\n";
    echo '    labels: ['.implode(', ',$days).'],'."\n";
    echo '    datasets: ['."\n";
    print_data($players, $name);
    echo '    ]'."\n";
    echo '};'."\n";

    echo "\n";
    echo 'var ctx_'.$name.' = document.getElementById("chart_'.$name.'").getContext("2d");'."\n";
    echo 'new Chart(ctx_'.$name.').Line(data_'.$name.', {});'."\n";
    echo "\n";
}

$days = array();
$players = array();
for($i = 1; $i <= 34; $i++) {
    if(!is_file('data/'.$i.'.php'))
        break;

    $days[] = $i;
    $day = include('data/'.$i.'.php');

    $max = 0;
    foreach($day as $p) {
        if($p['points'] > $max)
            $max = $p['points'];
    }

    foreach($day as $p) {
        if(!isset($players[$p['name']]))
            $players[$p['name']] = array('diff' => array(), 'ranks' => array());
        $players[$p['name']]['diff'][] = $p['points'] - $max;
        $players[$p['name']]['ranks'][] = -$p['rank'];
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

<h1>Kickprophet Bundesliga 2015/2016</h1>

<div align="center">
    <h2>R&auml;nge nach Spieltagen</h2>
    <?php print_players($players); ?>
    <div id="ranks_ylabel" class="vertical-text">Rang</div>
    <canvas id="chart_ranks"></canvas>
    <div>Spieltag</div>

    <h2>Punkte nach Spieltagen</h2>
    <?php print_players($players); ?>
    <div id="diff_ylabel" class="vertical-text">Punktdifferenz zum 1. Platz</div>
    <canvas id="chart_diff"></canvas>
    <div>Spieltag</div>
</div>

<script type="text/javascript">
function pad(n, width, z) {
    z = z || ' ';
    n = n + '';
    return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
}

Chart.defaults.global.scaleLabel = "<%if(value < 0){%><%=-value%><%}else{%><%=value%><%}%>";
Chart.defaults.global.tooltipFontFamily = 'monospace';
Chart.defaults.global.tooltipTemplate = "<%if (label){%><%=label%>: <%}%><%= value %>";
Chart.defaults.global.multiTooltipTemplate = "<%if(value < 0){%><%=pad(-value, 3)%><%}else{%><%=pad(value, 3)%><%}%> : <%= datasetLabel %>";
Chart.defaults.global.tooltipTitleTemplate = "<%= label %>. Spieltag";

<?php
print_graph($players, $days, 'ranks');
print_graph($players, $days, 'diff');
?>
</script>

</body>
</html>
