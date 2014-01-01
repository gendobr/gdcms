#!/bin/sh
#/usr/local/bin/wget -q -O /home/dobro/sites.zsu.zp.ua/cms/scripts/site/spider.log.txt http://sites.zsu.zp.ua/cms/index.php?action=site/spider
/usr/local/bin/curl --silent --output /home/dobro/tmp/spiderlog.txt "http://sites.znu.edu.ua:8000/cms/index.php?action=site/spider"
