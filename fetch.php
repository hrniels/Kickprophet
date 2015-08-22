#!/usr/bin/env php
<?php
if($argc != 4)
    exit("Usage: ".$argv[0]." <user> <nick> <team>\n");

$opts = array(
    'http' => array(
        'method' => "GET",
        'header' => "Accept-language: de\r\n" .
                "Cookie: kpLogin_userID=".$argv[1]."; kpLogin_userNick=".$argv[2]."\r\n"
    )
);
$context = stream_context_create($opts);

$html = file_get_contents('http://kickprophet.com/m/de/tippgemeinschaften/'.$argv[3].'/bestenliste', false, $context);

preg_match_all(
    '#<div class=\'uRank\'>(.*?)\.</div>.*?'
   .'<div class=\'uName\'>(.*?)</div>.*?'
   .'<div class=\'uPoints\'>(.*?)</div>#',
   $html,
   $m
);

echo '<?php'."\n";
echo 'return array('."\n";
foreach($m[0] as $k => $v) {
    echo '    array('."\n";
    echo '        "name" => "'.$m[2][$k].'",'."\n";
    echo '        "points" => '.(int)($m[3][$k]).','."\n";
    echo '        "rank" => '.(int)($m[1][$k])."\n";
    echo '    ),'."\n";
}
echo ');'."\n";
echo '?>'."\n";
?>
