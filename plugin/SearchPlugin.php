<?php
// Copyright 2003-2005 Won-Kyu Park <wkpark at kldp.org>
// All rights reserved. Distributable under GPL see COPYING
//
// Firefox/Mozilla Search Plugin for MoniWiki by iolo@hellocity.net
//
// Usage: [[SearchPlugin(<name>)]]
//
// <name> is a string identifier. if not specified, sitename will be used.
//
// It generates/uses following links:
// - http://.../wiki.php/?action=SearchPlugin&name=<name>.src for descriptor
// - http://.../wiki.php/?action=SearchPlugin&name=<name>.png for icon
//
// FIXME:
// filename parts of update and icon url must be identical!
// and mozilla doesn't use the filename specified by disposition header,
// but use query string like IE! :@
// so, i'd append dummy parameter ends with '.src' and '.png' to deceive mozilla :(
//
// $Id$

function macro_SearchPlugin($formatter,$value,$options='') {
  global $DBInfo;

  if ($value) {
    $name = $value;
  } else {
    $name = $DBInfo->sitename;
  }
  $update_url = qualifiedUrl($formatter->link_url("?action=SearchPlugin&name="));
  //$update_url = "http://hellocity.net/~iolo/moniwiki/";

  return <<<EOS
<div id="addSearchPlugin">
<script type="text/javascript">
<!--
function addSearchPlugin(update_url, name)
{
  if ((typeof window.sidebar == "object") && (typeof window.sidebar.addSearchEngine == "function")) {
    window.sidebar.addSearchEngine(
      update_url + name + ".src",
      update_url + name + ".png",
      "MoniWiki - " + name,
      "General");// FIXME: what's the valid category?
  }
  else {
    alert("Firefox, Mozilla or Compatible Browser is needed to install a search plugin");
  }
}
//-->
</script>
<a href="javascript:addSearchPlugin('$update_url', '$name')">Add Search Plugin </a>
</div>
EOS;
}

function do_SearchPlugin($formatter,$options) {
  global $DBInfo;

  if ($options['name']) {
    $name = $options['name'];
  } else {
    $name = $DBInfo->sitename;
  }
  if (strpos($name, ".png") != false) {
    header("Content-Type: image/png\r\n");
    header("Content-Disposition: inline; filename=\"$name\"" );
    #header("Content-Disposition: attachment; filename=\"$filename\"" );
    header("Content-Description: MoniWiki Search Plugin Descriptor" );
    Header("Pragma: no-cache");
    Header("Expires: 0");
    $fp = readfile("imgs/interwiki/moniwiki-16.png");
    return;
  } if (strpos($name, ".src") == false) {
    // error! invalid options
    // name=xxx.[src|png]
    print "invalid option!";
    return;
  }

  $update_url = qualifiedUrl($formatter->link_url("?action=SearchPlugin&name="));
  //$update_url = "http://hellocity.net/~iolo/moniwiki/";

  // remove file extension part(should be ".src") from url
  // it's just a dummy to deceive stupid mozilla :@
  $name = substr($name, 0, strlen($name) - 4);

  // FIXME: what's the valid way to get http://.../wiki.php ?
  // alternative: $_SERVER["PHP_SELF"]
  $base_url=qualifiedUrl($formatter->link_url(""));
  // FIXME: what's the valid search page name for all moniwiki sites?
  $form_url=qualifiedUrl($formatter->link_url("FindPage"));

  #header("Content-Type: application/x-wais-source\r\n");
  header("Content-Type: text/plain\r\n");
  header("Content-Disposition: inline; filename=\"$name.src\"" );
  #header("Content-Disposition: attachment; filename=\"$file\"" );
  header("Content-Description: MoniWiki Search Plugin Descriptor" );
  Header("Pragma: no-cache");
  Header("Expires: 0");

  print <<<EOS
# Firefox/Mozilla Search Plugin for MoniWiki by iolo@hellocity.net
<search
  version="7.1"
  name="MoniWiki - $name"
  description="MoniWiki Search Plugin for $name"
  method="GET"
  action="$base_url"
  searchForm="$form_url"
  queryEncoding="utf-8"
  queryCharset="utf-8"
  routeType="internet"
>

<input name="sourceid" value="mozilla-search">
#<inputnext name="start" factor="10">
#<inputprev name="start" factor="10">

<input name="value" user>

# TODO: support various search actions and parameters
#<input name="action" value="titlesearch">
<input name="action" value="fullsearch">
<input name="context" value="20">
<input name="backlinks" value="0">
<input name="case" value="0">
#<input name="ie" value="utf-8">
#<input name="oe" value="utf-8">

<interpret
  browserResultType="result"
  charset = "UTF-8"
  resultListStart="<!-- RESULT LIST START -->"
  resultListEnd="<!-- RESULT LIST END -->"
  resultItemStart="<!-- RESULT ITEM START -->"
  resultItemEnd="<!-- RESULT ITEM END -->"
>
</search>

<browser
  update="$update_url$name.src"
  updateIcon="$update_url$name.png"
  updateCheckDays=1
>
EOS;
}
?>