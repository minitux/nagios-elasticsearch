<?php
require 'vendor/autoload.php';

if (count($argv) < 2) { usage();}
if ($argv[1] == '-h') { usage();} 

if (!isset($argv[2]) && !isset($argv[3]) && !isset($argv[4])) { usage();}
$ratio = "off";
if (isset($argv[5])) { $ratio = "on"; }

// CONFIG 
$domain = $argv[1];
$httpcode = $argv[2];
$warningState  = $argv[3];
$criticalState = $argv[4];
$interval = "-10 min";
$fromdate = date('Y-m-d\TH:i:sP',strtotime($interval));
$todate = date('Y-m-d\TH:i:sP');
$indexdate = date('Y.m.d');
$elasticsearchHost = "< MY HOST >:9200";
$logtype = 'LOG TYPE';

// Search Options 
$params['hosts'] = array();
$params['hosts'] = array($elasticsearchHost);
$search['type'] = $logtype;
$search['index'] = "logstash-$indexdate";
$search['size'] = "50";
$search['from'] = "0";
$search['body']['sort']['@timestamp']['order'] = "desc";

// Queries Options
$query = array();
$query['query_string']['query'] = " response:\"$httpcode\" AND domain:\"$domain\"";
$filter = array();
$filter['range']['@timestamp'] = array( "from" => "$fromdate", "to" => "$todate");
$search['body']['query']['filtered'] = array("filter" => $filter,"query"  => $query);

$emptyquery = array();
$emptyquery['query_string']['query'] = "domain:\"$domain\"";
$globalsearch['body']['query']['filtered'] = array("filter" => $filter,"query"  => $emptyquery);


$client = new Elasticsearch\Client($params);

$results = $client->search($search);
$globalcount =  $client->search($globalsearch);

$error_count = $results['hits']['total'];
$total_line =  $globalcount['hits']['total'];

$request = array();
// TODO set $search['size'] if > $error_count 
if ( $search['size'] >= $error_count ) { $nbline = $error_count; } else { $nbline = $search['size']; } 

for ($i = 0; $i < $nbline; $i++) {
  $request[] .=  $results['hits']['hits'][$i]['_source']['request']."\n";
}
$maxCountUrl = array_count_values($request);
arsort($maxCountUrl);
$url=array_keys($maxCountUrl);

if ($ratio == "on") {
 $error_count =  round($error_count / $total_line * 100, 2); 
 $nagiosMessage = "$error_count % error(s) on http code $httpcode \n"; 

} else {
 $nagiosMessage = "$error_count http code $httpcode on $total_line lines \n";
}
if ($error_count < $warningState) {
   print "OK : $nagiosMessage";
   exit(0); 
} elseif ($error_count >= $warningState && $error_count < $criticalState) {
   print "Warning : $nagiosMessage";
   print "max count url : http://$domain$url[0]";
   exit(1);
} elseif ($error_count >= $criticalState) {
   print "Critical : $nagiosMessage";
   print "max count url : http://$domain$url[0]";
   exit(2);
} else {
   print "Unknown : unknown error"; 
   exit(3);
}

function usage() {
 print "Usage : php elasticlog.php domain httpCode warningState criticalState ratio(optinal)\n";
 print " domain        : domain name www.example.net\n";
 print " httpCode      : http status code 200, 301, 404 ...\n";
 print " warningState  : warning state in number or percent\n";
 print " criticalState : critical state in number or percent\n";
 print " ratio         : turn on the ratio mode\n";
 exit(0); 
}
?>
