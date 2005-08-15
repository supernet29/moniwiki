<?php
// Copyright 2005 Won-Kyu Park <wkpark at kldp.org>
// All rights reserved. Distributable under GPL see COPYING
// a Antispam filter plugin for the MoniWiki
//
// $Id$

function filter_antispam($formatter,$value,$options) {
    $blacklist_pages=array('BadContent','LocalBadContent');
    $whitelist_pages=array('GoodContent','LocalGoodContent');
    if (! in_array($formatter->page->name,$blacklist_pages) and
        ! in_array($formatter->page->name,$whitelist_pages)) {

        foreach ($blacklist_pages as $list) {
            $p=new WikiPage($list);
            if ($p->exists()) $badcontent.=$p->get_raw_body();
        }
        $badcontents=explode("\n",$badcontent);
        $pattern[0]='';
        $i=0;
        foreach ($badcontents as $line) {
            if ($line[0]=='#') continue;
            $line=preg_replace('/[ ]*#.*$/','',$line);
            $test=@preg_match("/$line/i","");
            if ($test === false) $line=preg_quote($line,'/');
            if ($line) $pattern[$i].=$line.'|';
            if (strlen($pattern[$i])>4000) {
                $i++;
                $pattern[$i]='';
            }
        }
        for ($k=0;$k<=$i;$k++)
            $pattern[$k]='/'.substr($pattern[$k],0,-1).'/i';

        #foreach ($whitelist_pages as $list) {
        #    $p=new WikiPage($list);
        #    if ($p->exists()) $goodcontent.=$p->get_raw_body();
        #}
        #$goodcontents=explode("\n",$goodcontent);

        return preg_replace($pattern,"",$value);
    }
    return $value;
}
// vim:et:sts=4:
?>