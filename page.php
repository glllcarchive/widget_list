<?php
$developmentMachine = (PHP_OS == "WINNT");

// -- first setup defaults
if (!isset($parms['render_full_page']))
{
   $parms['render_full_page'] = true;
}

if ($parms['render_full_page'])
{
   $template['page_wrapper'] = file_get_contents('index.tpl');
}
else
{
   $template['page_wrapper'] = '<!--CONTENT-->';
}

// -- validate parameters else die() in windows - sorry matt

if (!is_array($parms) && $developmentMachine)
{
   die("Pass \$parms variable with configuration for list view");
}

$requiredParameters = array('name','view','noDataMessage');

foreach ($requiredParameters as $req)
{
   if (!array_key_exists($req,$parms) && $developmentMachine)
   {
      die("Please pass \$parms['".$req."']");
   }
}

if (!array_key_exists('orderBy',$parms))
{
   $parms['orderBy'] = '';
}


$SQL = "
   SELECT
      *
   FROM
      {$parms['view']}";

// -- always pass these common bind vars mixed with your bind vars
$bindVars = array('DELETED'     => 0,
                  'YES'         => '<div style=\'color:#1e7b17;\'>Yes</div>',
                  'NO'          => '<div style=\'color:#b70a0a;\'>No</div>');

if (!empty($parms['bindVars']))
{
   foreach ($parms['bindVars'] as $k=>$v)
   {
      $bindVars[$k] = $v;
   }
}

$list = array(
              'title'               => $parms['title'],
              'pageId'              => (array_key_exists('pageId',$parms)) ? $parms['pageId'] : basename($_SERVER['PHP_SELF']),
              'orderBy'             => $parms['orderBy'],
              'noDataMessage'       => $parms['noDataMessage'],
              'sql'                 => $SQL,
              'bindVars'            => $bindVars);

foreach ($parms as $miscItemKey=>$value)
{
   if (!array_key_exists($miscItemKey,$list) && $miscItemKey != 'list_buttons')
   {
      $list[$miscItemKey] = $value;
   }
}

if (array_key_exists('pk_column',$parms) && array_key_exists($parms['pk_column'],$_GET))
{
   $list['filter'] = array("`{$parms['pk_column']}` = '{$_GET[$parms['pk_column']]}'");
}

$buttons = array_key_exists('list_buttons',$parms) ? $parms['list_buttons'] : '';

if (function_exists('page_overrides') && !array_key_exists('page_override_function',$parms))
{
   page_overrides($list,$parms['name']);
}
elseif (array_key_exists('page_override_function',$parms) && function_exists($parms['page_override_function']))
{
   $parms['page_override_function']($list,$parms['name']);
}

// -- Display the Notifications page or AJAX JSON
if(array_key_exists('BUTTON_VALUE', $_GET) && ! empty($_GET['BUTTON_VALUE']) && $_GET['LIST_NAME'] == $parms['name'])
{
   $templateList = new WidgetList( (!empty($_GET['LIST_NAME'])) ?  $_GET['LIST_NAME'] : $parms['name'] , $list );
   // -- Only fall into here when the list is equal to the list name of the page (just in case two lists use page.php)
   $return['list']     = str_replace(array('<!--CUSTOM_CONTENT-->'), array($buttons), $templateList->Render());
   $return['list_id']  = (!empty($_GET['LIST_NAME'])) ?  $_GET['LIST_NAME'] : $parms['name'];
   $return['callback'] = 'ListSearchAheadResponse';
   JsonEncode($return);
   exit;
}
elseif (!array_key_exists('BUTTON_VALUE', $_GET))
{
   $templateList = new WidgetList( (!empty($_GET['LIST_NAME'])) ?  $_GET['LIST_NAME'] : $parms['name'] , $list );
   $pagePieces = array(
                       '<!--CONTENT-->'      =>  $templateList->Render(),
                       '<!--CUSTOM_CONTENT-->' => $buttons
                       );
   $pageHTML = Fill($pagePieces, $template['page_wrapper']);
}

// -- clear out parms if used in another list view on the page
$parms = array();

?>
