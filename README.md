nagios-elasticsearch
====================

Plugin to check data in elasticsearch database 

If you have a Logstash / Elasticsearch / Kibana solution for indexing log 
you may want to monitor with nagios some states

1/ elasticHttpLog.php 

You need to install php elasticsearch with composer 

    curl -s http://getcomposer.org/installer | php
    php composer.phar install

You may change some parameters in files like $elasticsearchHost or $logtype(if you set it)

    php elasticHttpLog.php www.mydomain.com httpcode warn critical ratio(optinal) 

    ex : Warning : 1 http code 500 on 36188 lines 
    ex : Critical : 19.31 % error(s) on http code 404 


