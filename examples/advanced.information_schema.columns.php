<?php

$buttons      = array();
$columns      = array();
$filter       = array();
$createBtn    = '';
$listName     = 'info_columns';
$table           = '';
$type           = '';
$pageTitle    = 'Information Schema Columns';

if (array_key_exists('switch_grouping',$_GET) && $_GET['switch_grouping'] == 'Tables')
{
   $count             = 'COUNT(1) as cnt,';
   $groupBy         = 'GROUP BY name';
   $columns['tbl'] = 'Table Name';
   $columns['cnt'] = 'Total Columns';
   $columns['features'] = 'Blah!!!';
}
else
{
   $count             = '';
   $groupBy         = '';
   $columns['tbl'] = 'Table Name';
   $columns['column_name'] = 'Column Name';
   $columns['data_type'] = 'Data Type';
   $columns['column_type'] = 'Column Type';
   $columns['column_key'] = 'Column Key';
   $columns['column_comment'] = 'Comments!';
   $columns['features'] = 'Actions';
}
$view           = "(SELECT
                                     CONCAT('<a href=\"index.php?table=',TABLE_NAME,'\">',TABLE_NAME,'</a>') as tbl,
                                     TABLE_NAME as name,
                                     {$count}
                                     COLUMN_NAME,
                                     DATA_TYPE,
                                     ".WidgetList::BuildDrillDownLinkColumn($listName,'size',"COLUMN_TYPE","COLUMN_TYPE")."
                                     COLUMN_TYPE as col_type,
                                     COLUMN_KEY,
                                     COLUMN_COMMENT
                          FROM
                                     information_schema.COLUMNS {$groupBy}) a";

if(array_key_exists('table', $_GET) && ! empty($_GET['table']))
{
   $table  = $_GET['table'];

   $pageTitle .= ' (Filtered by '.$table.')';

   $filter[] = "name = ':TABLE_NAME'";
}

list($drillDown,$filterValue) =  WidgetList::GetFilterAndDrillDown('info_columns');

switch($drillDown)
{
   case 'size':
      $type   = $filterValue;
      $filter[] = "col_type = ':DATA_TYPE'";
      break;
}


$buttons['button_edit'] = array('page'       => 'edit.php',
                                'text'       => 'Edit',
                                'function'   => 'Redirect',
                                //pass tags to pull from each
                                'tags'       => array('TABLE_NAME' => 'TABLE_NAME','COLUMN_NAME'=>'COLUMN_NAME'));
$buttons['button_delete'] = array('page'       => 'delete.php',
                                  'text'       => 'Delete',
                                  'function'   => 'alert',
                                  'innerClass' => 'danger');

$createBtn = WidgetButton('Add New TEST Button', array('page' => 'add.php'));

$bindVars = array('TABLE_NAME'   => $table,
                  'DATA_TYPE'    => $type,
                  'DELETED'    => 0,
                  'ACTIVE'     => 1,
                  'YES'        => '<div style=\'color:#1e7b17;\'>Yes</div>',
                  'NO'         => '<div style=\'color:#b70a0a;\'>No</div>');

$template = array();

//List Page Search Template for the drop down form
//
$template['page_list_search_template'] = <<< TPL
<div id="advanced-search-container">
   <div class="widget-search-drilldown-close" onclick="<!--BUTTON_CLOSE-->">X</div>
   <ul class="advanced-search-container-inline" id="search_columns">
      <li>
         <div>Search Comments</div>
         <!--COMMENTS-->
      </li>

      <li>
         <div>By Type</div>
         <!--TYPE-->
      </li>
   </ul>
   <br/>
   <div style="text-align:right;width:100%;height:30px;" class="advanced-search-container-buttons"><!--BUTTON_RESET--><!--BUTTON_SEARCH--></div>
</div>
TPL;

//Search Form
//
$INPUTS = array();

$INPUTS['comments']['id']         = 'comments';
$INPUTS['comments']['name']       = 'comments';
$INPUTS['comments']['width']      = '170';
$INPUTS['comments']['max_length'] = '500';
$INPUTS['comments']['input_class'] = 'info-input';
$INPUTS['comments']['title']       = 'Optional CSV list';

/**
* Search Button
*/
$BUTTONS['pieces']['search']['innerClass'] = "success btn-submit";
$BUTTONS['pieces']['search']['onclick']    = "alert('This would search, but is not coded')";

$inputPieces = array();
$inputPieces['<!--COMMENTS-->']             = WidgetInput($INPUTS['comments']);
$inputPieces['<!--TYPE-->']                         = WidgetSelect("SELECT distinct DATA_TYPE as id, DATA_TYPE as value FROM information_schema.columns");
$inputPieces['<!--BUTTON_SEARCH-->']       = WidgetButton('Search', $BUTTONS['pieces']['search']);
$inputPieces['<!--BUTTON_CLOSE-->']        = "HideAdvancedSearch(this)";

$template['page_list_search_template'] = str_replace(array_keys($inputPieces), array_values($inputPieces), $template['page_list_search_template']);

$config = array('title'         => $pageTitle,
                'groupByItems'        =>  array('All Records','Tables'),
                'list_search_form'        => $template['page_list_search_template'] ,
                'pageId'        => 'index.php',
                'view'          => $view,
                'noDataMessage' => 'No Columns exist at this time.',
                'allowHTML'     => 1,
                'buttonVal'     => 'info_columns',
                'fields'           => $columns,
                'buttons'       => array('features' => $buttons),
                'filter'        => $filter,
                'function'      => array('features' => "'' features"),
                'bindVars'      => $bindVars);

$list = new WidgetList($listName, $config);


if(array_key_exists('BUTTON_VALUE', $_GET) && ( $_GET['BUTTON_VALUE'] == 'info_columns'))
{
   $return = array();

   $return['list']     = str_replace(array('<!--CUSTOM_CONTENT-->'), array($createBtn), $list->Render());
   $return['list_id']  = $listName;
   $return['callback'] = 'ListSearchAheadResponse';

   JsonEncode($return);
   die;
}
else
{
   $listPieces['<!--LIST2-->' ] =  $list->Render();
   $listPieces['<!--CUSTOM_CONTENT-->' ] =  $createBtn;
}

?>