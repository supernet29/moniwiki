<?php
// from http://www.heddley.com/edd/php/search.html
// code itself http://www.heddley.com/edd/php/indexer.tar.gz
//
// Copyright 2003-2010 Won-Kyu Park <wkpark at kldp.org>
// All rights reserved. Distributable under GPL see COPYING
//
// indexer.tar.gz is modified to adopt under MoniWiki
// the indexer engine is a perl program, slightly modified by wkpark
// the lookup script also imported and modified.
//
// a FasetSearch plugin using a index.db for the MoniWiki
//
// Usage: [[FastSearch(string)]]
//
// $Id$

include_once('lib/search.DBA.php');

function macro_FastSearch($formatter,$value="",&$opts) {
  global $DBInfo;

  if ($value === true) {
    $needle = $value = $formatter->page->name;
  } else {
    # for MoinMoin compatibility with [[FullSearch("blah blah")]]
    $needle = $value = preg_replace("/^('|\")([^\\1]*)\\1/","\\2",$value);
  }

  $needle=_preg_search_escape($needle);
  $pattern = '/'.$needle.'/i';
  $fneedle=str_replace('"',"&#34;",$needle); # XXX
  $url=$formatter->link_url($formatter->page->urlname);

  $msg = _("Fast search");
  $msg2 = _("Display context of search results");
  $form= <<<EOF
<form method='get' action='$url'>
   <input type='hidden' name='action' value='fastsearch' />
   <input name='value' size='30' value='$fneedle' />
   <span class='button'><input type='submit' class='button' value='$msg' /></span><br />
   <input type='checkbox' name='context' value='20' checked='checked' />$msg2<br />
   </form>
EOF;

  if (!$needle) { # or blah blah
     $opts['msg'] = _("No search text");
     return $form;
  }

  $DB=new IndexDB_dba('fullsearch',"r",$DBInfo->dba_type);
  if ($DB->db==null) {
    $opts['msg']=_("Couldn't open search database, sorry.");
    $opts['hits']= 0;
    $opts['all']= 0;
    return '';
  }

  $words=explode(' ', strtolower($value));
  $keys='';
  $idx=array();
  foreach ($words as $word) {
    $idx=array_merge($idx,$DB->_fetchValues($word));
  }

  arsort($idx);

  $pages=array();
  foreach ($idx as $id) {
    $key= $DB->_fetch($id);
    $pages[]=$key;
    #print $key.'<br />';
  }
  $DB->close();

  $hits=array();

  $context = !empty($opts['context']) ? $opts['context'] : 0;
  $contexts = array();

  foreach ($pages as $key) {
    $page_name= $DBInfo->keyToPagename($key);
    $p = new WikiPage($page_name);
    if (!$p->exists()) continue;

    $body= $p->_get_raw_body();
    $count = preg_match_all($pattern, $body,$matches);
    if ($count) {
      $hits[$page_name] = $count;
      # search matching contexts
      $contexts[$page_name] = find_needle($body,$needle,'',$context);
    }
  }

  arsort($hits);

  $out = "<ul>";
  reset($hits);
  $idx=1;
  while (list($page_name, $count) = each($hits)) {
    $out.= '<li>'.$formatter->link_tag(_rawurlencode($page_name),
          "?action=highlight&amp;value="._urlencode($needle),
          $page_name,"tabindex='$idx'");
    $out.= ' . . . . ' . $count . (($count == 1) ? ' match' : ' matches');
    $out.= $contexts[$page_name];
    $out.= "</li>\n";
    $idx++;
  }
  $out.= "</ul>\n";

  $opts['hits']= count($hits);
  $opts['all']= count($pages);
  return $out;
}

function do_fastsearch($formatter,$options) {

  $ret=$options;

  $title= sprintf(_("Full text search for \"%s\""), $options['value']);
  $out= macro_FastSearch($formatter,$options['value'],$ret);
  $options['msg']=!empty($ret['msg']) ? $ret['msg'] : '';
  $formatter->send_header("",$options);
  $formatter->send_title($title,$formatter->link_url("FindPage"),$options);

  print $out;

  if ($options['value'])
    printf(_("Found %s matching %s out of %s total pages")."<br />",
         $ret['hits'],
        ($ret['hits'] == 1) ? _("page") : _("pages"),
         $ret['all']);
  $args['noaction']=1;
  $formatter->send_footer($args,$options);
}

?>
