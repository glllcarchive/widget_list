<?php
   $parms = array();
   $showFullPage = false;
   $parms['name']          = 'info_tables';
   $parms['render_full_page']          = $showFullPage;
   $parms['view']          = '(SELECT \'\' as checkbox,a.* FROM information_schema.TABLES a ) a';
   $parms['noDataMessage'] = 'No Tables Found';

   $parms['fields']['checkbox']      = 'checkbox_header';

   $parms['fields']['TABLE_SCHEMA']   = 'TABLE_SCHEMA';
   $parms['fields']['TABLE_NAME']        = 'TABLE_NAME';
   $parms['fields']['AVG_ROW_LENGTH'] = 'AVG_ROW_LENGTH';
   $parms['fields']['DATA_LENGTH'] = 'DATA_LENGTH';
   $parms['fields']['MAX_DATA_LENGTH'] = 'MAX_DATA_LENGTH';
   $parms['fields']['INDEX_LENGTH'] = 'INDEX_LENGTH';
   $parms['fields']['TABLE_COMMENT'] = 'TABLE_COMMENT';

   $parms['inputs']['checkbox']['type']                          = 'checkbox';
   $parms['inputs']['checkbox']['items']['name']            = 'visible_checks[]';
   $parms['inputs']['checkbox']['items']['value']             = 'table_name';
   $parms['inputs']['checkbox']['items']['class_handle'] = 'info_tables';

   $parms['inputs']['checkbox_header']['type']                  = 'checkbox';
   $parms['inputs']['checkbox_header']['items']['check_all']    = true;
   $parms['inputs']['checkbox_header']['items']['class_handle'] = 'info_tables';
   $parms['inputs']['checkbox_header']['items']['id']           = 'info_tables_check_all';

   $parms['title']         = 'Information Schema Tables';

   /*
    * page.php abstracts some of the common parameters and AJAX flow for you
    */
   include("page.php");

   if ($showFullPage)
   {
      echo $pageHTML;
      exit;
   }
   else
   {
      $listPieces['<!--LIST1-->' ] =  $pageHTML;
   }
?>