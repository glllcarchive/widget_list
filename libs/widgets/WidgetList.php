<?php

/**
 * WidgetList
 *
 * @author  funtson 1/2011
 * @author sesson 1/2011
 *
 * @todo list validation on the required ones
 * @todo make default values configurable
 * @todo make new filter flag so that the page number can be set back to 1 if a new filter occurs.
 * @todo handle expeptions
 * @todo allow implicit column names derived from column name in Render()
 * @todo keep track of column widths and write and algorithm to control them based on content type
 */
class WidgetList
{
   /**
    * Default Attributes
    *
    * @var array $items
    */
   public $items;

   /**
    * Default Page
    *
    * @var int $sequence
    */
   public $sequence;

   /**
    * Total rows by query
    *
    * populated in GetTotalRecords()
    *
    * @var int $totalRows
    */
   public $totalRows;

   /**
    * Total Pages
    *
    * @var int $totalPages
    */
   public $totalPages;

   /**
    * Filter
    * @var array $filter
    */
   public $filter;

   /**
   * @var string $listSortNext
   */
   public $listSortNext;

   /**
   * @var string $listFilter
   */
   public $listFilter;

   /**
   * @var string $sqlHash
   * unique query used to keep track of sorting session filters
   */
   public $sqlHash;

   /**
   * @var array $fieldList
   */
   public $fieldList;

   /**
   * @see Render()
   * @var array $templateFill
   */
   public $templateFill;

   /**
   * @example >0 non_passive <=0 passive
   * @var bool $mode
   */
   public $mode;

   /**
   * @var bool $results
   */
   public $results;

   /**
   * @var bool $isJumpingList
   */
   public $isJumpingList;

   public $totalRowCount;

   public $totalResultCount;

   public $fixHtmlLinksReplace;

   public $DATABASE;

   public $debug = false;

   public function __construct($name = '', $list = '')
   {
      global $G_DEBUG, $DATABASE;
      if ($G_DEBUG == 1 || $G_DEBUG == true)
      {
         $this->debug = true;
      }
      $this->DATABASE = &$DATABASE;


      $this->items = array('title'               => '',// will add <h1> title and horizontal rule
                           'listDescription'     => '',// will add grey header box
                           'name'                => $name,
                           'pageId'              => '',
                           'sql'                 => '',
                           'view'                => '',
                           'data'                => array(),
                           'mode'                => 'passive',
                           'collClass'           => '',
                           'collAlign'           => '',
                           'fields'              => array(),
                           'columns'             => array(),
                           'bindVars'            => array(),
                           'links'               => array(),
                           'results'             => array(),
                           'buttons'             => array(),
                           'inputs'              => array(),
                           'filter'              => '',
                           'rowStart'            => 0,
                           'rowLimit'            => 10,
                           'orderBy'             => '',
                           'allowHTML'           => true,
                           'strlength'           => 30,
                           'searchClear'         => false, // build a custom conditional for each page to clear session
                           'searchClearAll'      => false, // build a custom conditional for each page to clear session
                           'showPagination'      => true,
                           'searchSession'       => true,//on list render use last filter
                           'databaseRef'         => false,

                           /**
                           * Custom count query
                           */
                           'force_count_sql'     => '',
                           'force_query_sql'     => '',

                           /**
                           * Counts
                           */
                           'count_cache_time'    => 180, //
                           'cachedCount'         => -1,  //pass the count

                           /**
                           * Ajax
                           */
                           'ajax_action'         => '',
                           'ajax_function'       => 'ListJumpMin',
                           'ajax_search_function'=> 'ListJumpMin',

                           /**
                           * Search
                           */
                           'showSearch'          => true,
                           'searchOnkeyup'       => '',
                           'searchOnclick'       => "",
                           'searchIdCol'         => 'id',
                           'searchInputLegacyCSS'=> false,
                           'searchBtnName'       => 'Search by Id or a list of Ids and more',
                           'searchTitle'         => '',
                           'searchFieldsIn'      => array(),  //White list of fields to include in a search
                           'searchFieldsOut'     => array(),  //Black list of fields to include in a search


                           /**
                           * Group By Box
                           */
                           'groupByItems'        => array(),    //array of strings (each a new "select option")
                           'groupBySelected'     => false,      //initially selected grouping - defaults to first in list if not
                           'groupByClick'        => "ListChangeGrouping('".$name."')",         //after selecting something from the drop down what JS function do you want to call
                           'groupByLabel'        => 'Group By',


                           /**
                           * Advanced searching
                           */
                           'list_search_form'    => '',      //The HTML form used for the advanced search drop down
                           'list_search_attribs' => array(), //widgetinput "search_ahead" attributes

                           /**
                           * Column Specific
                           */
                           'columnStyle'         => array(),
                           'columnClass'         => array(),
                           'columnPopupTitle'    => array(),
                           'columnSort'          => array(),
                           'columnWidth'         => array(),
                           'columnNoSort'        => array(),
                           'columnFilter'        => array(),

                           /**
                           * Row specifics
                           */
                           'rowColor'            => '#FFFFFF',
                           'rowClass'            => '',
                           'rowColorByStatus'    => array(),
                           'rowStylesByStatus'   => array(),
                           'offsetRows'          => true,
                           'rowOffsets'          => array('FFFFFF','FFFFFF'),

                           'class'               => 'listContainerPassive',
                           'tableclass'          => '',
                           'noDataMessage'       => 'Currently no data.',
                           'useSort'             => true,
                           'headerClass'         => true,
                           'groupBy'             => '',
                           'function'            => array(),
                           'buttonVal'           => 'templateListJump',
                           'linkFunction'        => 'ButtonLinkPost',
                           'template'            => '',
                           'templateFilter'      => '',
                           'pagerFull'          => true,
                           'LIST_COL_SORT_ORDER' => 'ASC',
                           'LIST_COL_SORT'       => '',
                           'LIST_FILTER_ALL'     => '',
                           'ROW_LIMIT'           => '',
                           'LIST_SEQUENCE'       => '',
                           'NEW_SEARCH'          => false,

                           /**
                           * Checkbox
                           */
                           'checked_class'       => 'widgetlist-checkbox',
                           'checked_flag'        => array(),

                           /**
                           * Hooks
                           */
                           'column_hooks'        => array(),
                           'row_hooks'           => array()
                           );

      $this->isJumpingList = false;

      //Ajax ListJump
      if (!empty($_GET))
      {
         if (array_key_exists('LIST_FILTER_ALL',$_GET))
         {
            $this->items['LIST_FILTER_ALL']     = $_GET['LIST_FILTER_ALL'];
            $this->isJumpingList = true;
         }
         if (array_key_exists('LIST_COL_SORT',$_GET))
         {
            $this->items['LIST_COL_SORT']       = $_GET['LIST_COL_SORT'];
            $this->isJumpingList = true;
         }
         if (array_key_exists('LIST_COL_SORT_ORDER',$_GET))
         {
            $this->items['LIST_COL_SORT_ORDER'] = $_GET['LIST_COL_SORT_ORDER'];
            $this->isJumpingList = true;
         }
         if (array_key_exists('LIST_SEQUENCE',$_GET))
         {
            $this->items['LIST_SEQUENCE']       = $_GET['LIST_SEQUENCE'];
            $this->isJumpingList = true;
         }

         if (array_key_exists('ROW_LIMIT',$_GET) && !empty($_GET['ROW_LIMIT']))
         {
            $this->isJumpingList = true;
            $this->items['ROW_LIMIT']                      = $_GET['ROW_LIMIT'];
            if ($this->items['showPagination'])
            {
               $_SESSION['pageDisplayLimit']                  = $_GET['ROW_LIMIT'];
               $_SESSION['ROW_LIMIT'][$this->items['name']]   = $_GET['ROW_LIMIT'];
            }
         }

         if (array_key_exists('action',$_GET) && $_GET['action'] =='ajax_widgetlist_checks')
         {
            WidgetList::AjaxMaintainChecks();
            exit;
         }

         //we dont want these being built in BuildURL() when passing other getvars
         $this->ClearSortGetVars();
      }

      $this->fixHtmlLinksReplace = array();

      $this->sequence     = 1;
      $this->totalRows    = 0;
      $this->totalPage    = 0;
      $this->listSortNext = 'ASC';
      $this->filter       = '';
      $this->listFilter   = '';
      $this->fieldList    = array();
      $this->templateFill = array();
      $this->results      = array();

      $this->items['template'] = '
      <!--HEADER-->
      <div class="<!--CLASS-->" id="<!--NAME-->">
         <table class="list <!--TABLE_CLASS-->" style="<!--INLINE_STYLE-->" border="0" width="100%" cellpadding="0" cellspacing="0">
            <!--LIST_TITLE-->
            <tr class="list_header"><!--HEADERS--></tr>
               <!--DATA-->
            <tr>
               <td colspan="<!--COLSPAN_FULL-->" align="left" style="padding:0px;margin:0px;text-align:left">
                  <div style="background-color:#ECECEC;height:50px;"><div style="padding:10px"><!--CUSTOM_CONTENT--></div>
               </td>
            </tr>
         </table>
         <div class="pagination" style="float:left;text-align:left;width:100%;margin:0px;padding:0px;"><div style="margin:auto;float:left;margin:0px;padding:0px;"><!--PAGINATION_LIST--></div></div>
         <!--FILTER-->
         <input type="hidden" name="<!--JUMP_URL_NAME-->" id="<!--JUMP_URL_NAME-->" value="<!--JUMP_URL-->">
      </div>';

      $this->items['row'] = '
      <tr style="background-color:<!--BGCOLOR-->;<!--ROWSTYLE-->" class="<!--ROWCLASS-->"><!--CONTENT--></tr>';

      $this->items['list_description'] = '
      <tr class="summary">
         <td id="<!--LIST_NAME-->_list_description" class="header" style="text-align:left;padding-bottom:5px;padding-top:5px;" colspan="<!--COLSPAN-->"><!--LIST_DESCRIPTION--></td>
      </tr>';

      $this->items['col'] = '
      <td class="<!--CLASS-->" align="<!--ALIGN-->" title="<!--TITLE-->" onclick="<!--ONCLICK-->" style="<!--STYLE-->"><!--CONTENT--></td>';

      $this->items['templateSequence'] = '
      <!--LIST_SEQUENCE--> of <!--TOTAL_PAGES-->';

      //Sorting
      //
      $this->items['templateSortColumn'] = '<td style="font-weight:bold;<!--INLINE_STYLE-->" id="<!--COL_HEADER_ID-->" class="<!--COL_HEADER_CLASS-->" valign="middle"><span onclick="<!--FUNCTION-->(\'<!--COLSORTURL-->\',\'<!--NAME-->\')" style="cursor:pointer;background:none;" title="<!--TITLE_POPUP-->"><!--TITLE--><!--COLSORTICON-></span></td>';

      $this->items['templateNoSortColumn'] = '<td style="font-weight:bold;<!--INLINE_STYLE-->" title="<!--TITLE_POPUP-->" id="<!--COL_HEADER_ID-->" class="<!--COL_HEADER_CLASS-->" valign="middle"><span style="background:none;"><!--TITLE--></span></td>';

      $this->items['statement']['select']['view'] = 'SELECT <!--FIELDS--> FROM <!--SOURCE--> <!--WHERE--> <!--GROUPBY--> <!--ORDERBY--> <!--LIMIT-->';
      $this->items['statement']['select']['sql']  = 'SELECT <!--FIELDS--> FROM (<!--SQL-->) a <!--WHERE--> <!--GROUPBY--> <!--ORDERBY--> <!--LIMIT-->';

      $this->items['statement']['count']['view']  = 'SELECT count(1) total FROM <!--VIEW--> <!--WHERE-->';
      $this->items['statement']['count']['sql']   = 'SELECT count(1) total FROM (<!--SQL-->) s <!--WHERE-->';
      $this->items['statement']['count']['table'] = 'SELECT count(1) total FROM <!--TABLE--> <!--WHERE-->';

      //Pagintion
      //
      $this->items['template_pagination_wrapper'] = '
      <ul id="pagination" class="page_legacy">
         Page <!--PREVIOUS_BUTTON-->
         <input type="text" value="<!--SEQUENCE-->" size="1" style="width:15px;padding:0px;font-size:10px;">
         <input type="hidden" id="<!--LIST_NAME-->_total_rows" value="<!--TOTAL_ROWS-->">
         <!--NEXT_BUTTON--> of <!--TOTAL_PAGES--> pages <span style="margin-left:20px">Total <!--TOTAL_ROWS--> records found</span>
         <span style="padding-left:20px;">Show <!--PAGE_SEQUENCE_JUMP_LIST--> per page</span>
      </ul>
      ';

      $this->items['template_pagination_next_active']       = "<li><span onclick=\"<!--FUNCTION-->('<!--NEXT_URL-->','<!--LIST_NAME-->')\" style=\"cursor:pointer;background: transparent url(<!--HTTP_SERVER-->images/page-next.gif) no-repeat\">&nbsp;</span></li>";
      $this->items['template_pagination_next_disabled']     = "<li><span style=\"opacity:0.4;filter:alpha(opacity=40);background: transparent url(<!--HTTP_SERVER-->images/page-next.gif) no-repeat\">&nbsp;</span></li>";
      $this->items['template_pagination_previous_active']   = "<li><span onclick=\"<!--FUNCTION-->('<!--PREVIOUS_URL-->','<!--LIST_NAME-->')\" style=\"cursor:pointer;background: transparent url(<!--HTTP_SERVER-->images/page-back.gif) no-repeat\">&nbsp;</span></li>";
      $this->items['template_pagination_previous_disabled'] = "<li><span style=\"opacity:0.4;filter:alpha(opacity=40);background: transparent url(<!--HTTP_SERVER-->images/page-back.gif) no-repeat\">&nbsp;</span></li>";

      $this->items['template_pagination_jump_active']   = '<li><div class="active"><!--SEQUENCE--></div></li>';
      $this->items['template_pagination_jump_unactive'] = '<li onclick="<!--FUNCTION-->(\'<!--JUMP_URL-->\',\'<!--LIST_NAME-->\')"><div><!--SEQUENCE--></div></li>';

      $this->Initialize($list);

      //New default pagination
      //
      if(! $this->items['pagerFull'])
      {
         $this->items['template_pagination_wrapper'] = '
         <ul id="pagination">
            <!--PREVIOUS_BUTTON-->
            <!--JUMP-->
            <!--NEXT_BUTTON-->
         </ul>
         ';

         $this->items['template_pagination_next_active']       = "<li onclick=\"<!--FUNCTION-->('<!--NEXT_URL-->','<!--LIST_NAME-->')\"><div>Next</div></li>";
         $this->items['template_pagination_next_disabled']     = '<li><div>Next</div></li>';
         $this->items['template_pagination_previous_active']   = "<li onclick=\"<!--FUNCTION-->('<!--PREVIOUS_URL-->','<!--LIST_NAME-->')\"><div>Previous</div></li>";
         $this->items['template_pagination_previous_disabled'] = '<li><div>Previous</div></li>';
      }
   }

   /**
   * AjaxMaintainChecks()
   *
   * Maintains any checkboxes in regard to being checked and unchecked
   *
   * @todo check if request keys and values exist
   */
   public static function AjaxMaintainChecks()
   {
                        error_log(print_r($_REQUEST,true));
      /**
      * A list must be provided
      */
      if(array_key_exists('LIST_NAME', $_REQUEST))
      {
         $listName = $_REQUEST['LIST_NAME'];
         $sqlHash  = $_REQUEST['SQL_HASH'];

         /**
         * The placeholder is created when the list initially forms. This validates it and makes it so
         * not just anything can be injected into the session via this method.
         */
         if(array_key_exists('list_checks', $_SESSION) && array_key_exists($listName, $_SESSION['list_checks']) && array_key_exists($sqlHash, $_SESSION['list_checks'][$listName]))
         {
            /**
            * For each posted check box
            */
            foreach($_POST as $value => $checked)
            {
               if(empty($checked))
               {
                  /**
                  * Unset if it exists and is unchecked
                  */
                  if(array_key_exists($value, $_SESSION['list_checks'][$listName][$sqlHash]))
                  {
                     unset($_SESSION['list_checks'][$listName][$sqlHash][$value]);
                  }
               }
               else
               {
                  /**
                  * Set it as checked
                  */
                  $_SESSION['list_checks'][$listName][$sqlHash][$value] = true;
                  error_log(print_r($_SESSION['list_checks'],true));
               }
            }
         }

         /**
         * Check All
         */
         if(array_key_exists('checked_all', $_REQUEST))
         {
            if(array_key_exists('list_checks', $_SESSION) && array_key_exists($_REQUEST['LIST_NAME'], $_SESSION['list_checks']))
            {
               if(array_key_exists('check_all_'.$sqlHash, $_SESSION['list_checks'][$_REQUEST['LIST_NAME']]) && array_key_exists($_REQUEST['LIST_SEQUENCE'], $_SESSION['list_checks'][$_REQUEST['LIST_NAME']]['check_all_'.$sqlHash]))
               {
                  if(empty($_REQUEST['checked_all']))
                  {
                     unset($_SESSION['list_checks'][$_REQUEST['LIST_NAME']]['check_all_'.$sqlHash][$_REQUEST['LIST_SEQUENCE']]);
                  }
                  else
                  {
                     $_SESSION['list_checks'][$_REQUEST['LIST_NAME']]['check_all_'.$sqlHash][$_REQUEST['LIST_SEQUENCE']] = true;
                  }
               }
               else
               {
                  if(! empty($_REQUEST['checked_all']))
                  {
                     $_SESSION['list_checks'][$_REQUEST['LIST_NAME']]['check_all_'.$sqlHash][$_REQUEST['LIST_SEQUENCE']] = true;
                  }
               }
            }
         }
      }
   }

   public static function SetButton(&$list, $columnName, $buttonId, $page, $text, $function, $tags, $innerClass='',$asParameters=false,$condition='')
   {
      if (!isset($list['function'][$columnName]))
      {
         $list['function'][$columnName] = "'' $columnName";
      }
      $list['buttons'][$columnName][$buttonId]['page']       = $page;
      $list['buttons'][$columnName][$buttonId]['text']       = $text;
      $list['buttons'][$columnName][$buttonId]['function']   = $function;
      $list['buttons'][$columnName][$buttonId]['tags']       = $tags;
      $list['buttons'][$columnName][$buttonId]['parameters'] = $asParameters;

      if(! empty($innerClass))
      {
         $list['buttons'][$columnName][$buttonId]['innerClass'] = $innerClass;
      }

      //If you set a condition, you are wanting this button only to render when a certain status is passed in the record
      //
      if(! empty($condition))
      {
         $list['buttons'][$columnName][$buttonId]['condition'] = $condition;
      }
   }

   /**
   * Initialize()
   *
   * Initialize any user defined variables
   *
   * @param mixed $list
   * @return void
   */
   public function Initialize(&$list)
   {
      if(is_array($list))
      {
         foreach($list as $key => $value)
         {
            if(array_key_exists($key, $this->items))
            {
               $this->items[$key] = $value;
            }
         }
      }

      if ($this->items['databaseRef'] !== false)
      {
         $this->DATABASE = $this->items['databaseRef'];
      }


      $this->items['fieldNames'] = array();
      if (empty($this->items['fields']) && empty($this->items['data']))
      {
         //Lazy mode where new columns added to view show up in list (unless you name the column "HIDE_xxxxx")
         //
         if ($this->items['sql'])
         {
            preg_match("/\s+from\s+`?([a-z\d_]+)`?/i", $this->items['sql'], $match);
            $viewName = $match[1];
         }
         elseif ($this->items['view'])
         {
            $viewName = $this->items['view'];
         }

         if ($this->DATABASE->Select("SHOW FULL COLUMNS IN ".$viewName))
         {
            foreach($this->DATABASE->results['FIELD'] as $col)
            {
               $this->items['fieldNames'][] = $col;
               $columnUpper = strtoupper($col);
               $columnLower = strtolower($col);
               if (substr($columnUpper,0,5) != 'HIDE_')
               {
                  //any columns named HIDE_ will not show
                  //
                  $this->items['fields'][$columnLower] = $this->autoColumnName($columnUpper);
                  if ($columnLower != 'features')
                  {
                     $this->items['columnSort'][$columnLower] = $columnLower;
                  }
               }
            }
         }
      }

      if (array_key_exists('searchClear',$_REQUEST))
      {
         $this->ClearSearchSession();
      }

      if ($this->items['searchClear'] || $this->items['searchClearAll'])
      {
         $this->ClearSearchSession(array_key_exists('searchClearAll',$this->items));
      }

      $matchesCurrentList   = (array_key_exists('BUTTON_VALUE', $_GET) && $_GET['BUTTON_VALUE'] == $this->items['buttonVal']);
      $isSearchRequest      = (array_key_exists('search_filter', $_GET));
      $templateCustomSearch = (!empty($this->items['templateFilter'])); // if you define templateFilter WidgetList will not attempt to build a where clause with search

      /**
      * Search restore
      */
      if(!$isSearchRequest && array_key_exists('SEARCH_FILTER',$_SESSION) && array_key_exists($this->items['name'],$_SESSION['SEARCH_FILTER']) && $this->items['searchSession'])
      {
         $isSearchRestore = true;
      }

      if($isSearchRequest && $matchesCurrentList && !$templateCustomSearch && ! empty($this->items['showSearch']) || $isSearchRestore)
      {
         if (!$isSearchRestore)
         {
            $_SESSION['SEARCH_FILTER'][$this->items['name']] = $_GET['search_filter'];
            $searchFilter = trim($_GET['search_filter']);
         }
         else
         {
            $searchFilter = $_SESSION['SEARCH_FILTER'][$this->items['name']];
         }

         if(! empty($searchFilter))
         {
            if(! empty($this->items['filter']) && ! is_array($this->items['filter']))
            {
               //convert string to array filter
               $filterString = $this->items['filter'];
               $this->items['filter'] = array();
               $this->items['filter'][] = $filterString;
            }

            $fieldsToSearch = $this->items['fields'];

            foreach($this->items['columns'] as $columPivot)
            {
               $fieldsToSearch[$columPivot] = $columPivot;
            }

            $searchCriteria = trim($searchFilter);
            $searchSQL      = array();
            $numericSearch  = false;

            /**
            * Comma delimited search
            */
            if(stristr($searchFilter, ','))
            {
               //It is either a CSV or a comma inside the search string
               //
               $criteriaTmp = explode(',', trim($searchFilter,","));

               //Assumed a CSV of numeric ids
               //
               $isNumeric = true;
               foreach($criteriaTmp as $key => $value)
               {
                  $criteriaTmp[$key] = trim($value);

                  if(! empty($criteriaTmp[$key]))
                  {
                     if(! is_numeric($criteriaTmp[$key]))
                     {
                        $isNumeric = false;
                     }
                  }
                  else
                  {
                     unset($criteriaTmp[$key]);
                  }
               }

               if($isNumeric)
               {
                  if(is_array($this->items['searchIdCol']))
                  {
                     foreach($this->items['searchIdCol'] as $searchIdCol)
                     {
                        if(array_key_exists($searchIdCol, $fieldsToSearch))
                        {
                           $searchSQL[] = "`$searchIdCol` IN(". trim($searchFilter,",").")";
                        }
                     }

                     if (!empty($searchSQL))
                     {
                        /**
                        * Assemble Numeric Filter
                        */
                        $this->items['filter'][] = "(".implode(' OR ',$searchSQL).")";
                     }
                  }
                  elseif(array_key_exists($this->items['searchIdCol'],$this->items['fields']))
                  {
                     $numericSearch = true;
                     $this->items['filter'][] = "`{$this->items['searchIdCol']}` IN(".implode(',', $criteriaTmp).")";
                  }
               }
            }
            elseif(is_array($this->items['searchIdCol']))
            {
               if(is_numeric($searchFilter) && !stristr($searchFilter,'.'))
               {
                  foreach($this->items['searchIdCol'] as $searchIdCol)
                  {
                     if(array_key_exists($searchIdCol, $fieldsToSearch))
                     {
                        $searchSQL[] = "`$searchIdCol` IN($searchFilter)";
                     }
                  }

                  if (!empty($searchSQL))
                  {
                     /**
                     * Assemble Numeric Filter
                     */
                     $this->items['filter'][] = "(".implode(' OR ',$searchSQL).")";
                  }
               }
            }
            elseif(is_numeric($searchFilter) && !stristr($searchFilter,'.') && array_key_exists($this->items['searchIdCol'],$this->items['fields']))
            {
               //19.95 is numeric, but people might be searching for dollar amounts which should be string based search
               $numericSearch = true;
               $this->items['filter'][] = "`{$this->items['searchIdCol']}` IN(".intval($searchFilter).")";
            }

            // If it is not an id or a list of ids then it is assumed a string search
            if(!$numericSearch)
            {
               foreach ($fieldsToSearch as $fieldName=>$fieldTitle)
               {
                  if ($fieldName == 'features')
                  {
                     continue;
                  }

                  //Search only specified fields. This can involve a dynamic field list from an advanced search form
                  //
                  if(! empty($this->items['searchFieldsIn']))
                  {
                     /**
                     * If it exists in either key or value
                     */
                     if(! array_key_exists($fieldName, $this->items['searchFieldsIn']) && ! in_array($fieldName, $this->items['searchFieldsIn']))
                     {
                        continue;
                     }
                  }
                  elseif(! empty($this->items['searchFieldsOut']))
                  {
                     if(array_key_exists($fieldName, $this->items['searchFieldsOut']) || in_array($fieldName, $this->items['searchFieldsOut']))
                     {
                        continue;
                     }
                  }

                  $searchSQL[] = "`$fieldName` LIKE '%".mysql_real_escape_string($searchCriteria, $this->DATABASE->GetConnection())."%'";
               }

               /**
               * Assemble String Filter
               */
               if(! empty($searchSQL))
               {
                  $this->items['filter'][] = "(".implode(' OR ',$searchSQL).")";
               }
            }

            /**
            * Clean up
            */
            unset($fieldsToSearch);
            unset($searchCriteria);
            unset($searchSQL);
            unset($numericSearch);
         }

         unset($searchFilter);
      }

      if(!array_key_exists('BUTTON_VALUE', $_REQUEST))
      {
         //Initialize page load/Session stuff whe list first loads
         //
         WidgetList::ClearCheckBoxSession($this->items['name']);
      }

      if (!array_key_exists('templateHeader',$this->items))
      {
         $this->items['templateHeader'] = '';
      }

      //Set a list title if it exists
      //
      if(! array_key_exists('BUTTON_VALUE', $_GET) && ! empty($this->items['title']))
      {
         $this->items['templateHeader'] = '
         <h1><!--TITLE--></h1><div class="horizontal_rule"></div>
         <!--FILTER_HEADER-->
         ';
      }
      else
      {
         if (! array_key_exists('BUTTON_VALUE', $_GET))
         {
            $this->items['templateHeader'] = '
            <!--FILTER_HEADER-->
            ';
         }
      }

      //Build the filter (If any)
      //
      if ((is_array($this->items['filter']) && count($this->items['filter']) > 0) )
      {
         $this->filter = implode(' AND ', $this->items['filter']);
      }
      else if(! empty($this->items['filter']) && strlen($this->items['filter']) > 0)
      {
         $this->filter = $this->items['filter'];
      }

      //Sorting
      //
      if(! empty($this->items['LIST_COL_SORT']))
      {
         $this->items['LIST_SEQUENCE'] = 1;
      }

      if(! empty($this->items['LIST_SEQUENCE']))
      {
         $this->sequence = $this->items['LIST_SEQUENCE'];
      }

      if(! empty($this->items['ROW_LIMIT']))
      {
         $this->items['rowLimit'] = $this->items['ROW_LIMIT'];
      }

      if(array_key_exists('ROW_LIMIT',$_SESSION) && array_key_exists($this->items['name'],$_SESSION['ROW_LIMIT']) && !empty($_SESSION['ROW_LIMIT'][$this->items['name']]))
      {
         $this->items['rowLimit'] = $_SESSION['ROW_LIMIT'][$this->items['name']];
      }

      if(! empty($this->items['LIST_COL_SORT']))
      {
         switch($this->items['LIST_COL_SORT_ORDER'])
         {
            case 'ASC': $this->listSortNext = 'DESC'; break;
            default:    $this->listSortNext = 'ASC';
         }
      }

      $this->GenerateLimits();
   }

   /**
   * GenerateLimits()
   *
   * @return void
   */
   private function GenerateLimits()
   {
      //Pagination
      //
      $this->items['bindVars']['LOW']  = $this->items['rowStart'];
      $this->items['bindVars']['HIGH'] = $this->items['rowLimit'];

      if($this->sequence > 1 && ! $this->items['NEW_SEARCH'])
      {
         $this->items['bindVars']['LOW'] = ((($this->sequence * $this->items['rowLimit']) - $this->items['rowLimit']));
      }
   }

   /**
   * BuildHeaders()
   *
   * @return void
   */
   private function BuildHeaders()
   {
      $headers = array();

      //Adds sorting to a column header if specified
      //
      foreach($this->items['fields'] as $field => $fieldTitle)
      {
         $colWidthStyle = '';
         $colClass      = '';
         $popupTitle    = '';
         $templateIdx   = 'templateNoSortColumn';

         //Column class
         //
         if(! empty($this->items['headerClass']) && is_array($this->items['headerClass']))
         {
            if(array_key_exists(strtolower($field), $this->items['headerClass']))
            {
               $colClass = $this->items['headerClass'][strtolower($field)];
            }
         }

         //Column width
         //
         if(! empty($this->items['columnWidth']))
         {
            if(array_key_exists(strtolower($field), $this->items['columnWidth']))
            {
               $colWidthStyle = "width:".$this->items['columnWidth'][strtolower($field)].";";
            }
         }

         $_SESSION['LIST_SEQUENCE'][$this->sqlHash] = $this->sequence;

         //Hover Title
         //
         if(array_key_exists($field,$this->items['columnPopupTitle']))
         {
            $popupTitle = $this->items['columnPopupTitle'][$field];
         }

         /**
         * Column is an input
         */
         if(array_key_exists($fieldTitle, $this->items['inputs']) && is_array($this->items['inputs'][$fieldTitle]))
         {
            /**
            * Default checkbox hover to "Select All"
            *
            * Do specific input type functions
            */
            switch($this->items['inputs'][$fieldTitle]['type'])
            {
               case 'checkbox':

                  if(empty($popupTitle) && $this->items['inputs'][$fieldTitle]['items']['check_all'])
                  {
                     $popupTitle = 'Select All';
                  }

                  /**
                  * No sort on this column
                  */
                  if(! array_key_exists($fieldTitle,$this->items['columnNoSort']))
                  {
                     $this->items['columnNoSort'][$fieldTitle] =  true;
                  }

                  if(empty($colClass))
                  {
                     $this->items['headerClass'] = array($fieldTitle => 'widgetlist-checkbox-header');
                     $colClass = $this->items['headerClass'][$fieldTitle];
                  }

               break;
            }

            /**
            * Build the input
            */
            $fieldTitle = $this->BuildColumnInput($fieldTitle);
         }

         if($this->items['useSort'] && (in_array($field, $this->items['columnSort']) || array_key_exists($field, $this->items['columnSort'])) && !in_array($field, $this->items['columnNoSort']) || (empty($this->items['columnSort'])&& $this->items['useSort'] && !in_array($field, $this->items['columnNoSort'])))
         {
            $templateIdx = 'templateSortColumn';
            $colSort = array();

            //Assign the column to be sorted
            //
            if(!empty($this->items['columnSort']) && array_key_exists($field, $this->items['columnSort']))
            {
               $colSort['LIST_COL_SORT'] = $this->items['columnSort'][$field];
            }
            elseif(!empty($this->items['columnSort']) && in_array($field, $this->items['columnSort']) || empty($this->items['columnSort']))
            {
               $colSort['LIST_COL_SORT'] = $field;
            }

            $colSort['PAGE_ID']             = $this->items['pageId'];
            $colSort['LIST_NAME']           = $this->items['name'];
            $colSort['BUTTON_VALUE']        = $this->items['buttonVal'];
            $colSort['LIST_COL_SORT_ORDER'] = $this->listSortNext;
            $colSort['LIST_FILTER_ALL']     = $this->items['LIST_FILTER_ALL'];
            $colSort['ROW_LIMIT']           = $this->items['ROW_LIMIT'];
            $colSort['LIST_SEQUENCE']       = $this->sequence;
            $icon = "";

            if( (array_key_exists('LIST_COL_SORT', $this->items) && !empty($this->items['LIST_COL_SORT']) && $this->items['LIST_COL_SORT'] === $colSort['LIST_COL_SORT']) || (array_key_exists($this->sqlHash,$_SESSION['LIST_COL_SORT']) && is_array($_SESSION['LIST_COL_SORT'][$this->sqlHash]) && array_key_exists($field,$_SESSION['LIST_COL_SORT'][$this->sqlHash])) )
            {
               $changedSession = false;
               if(array_key_exists('LIST_COL_SORT', $this->items) && ! empty($this->items['LIST_COL_SORT']))
               {
                  $changedSession = (array_key_exists('LIST_COL_SORT',$_SESSION) && array_key_exists($this->sqlHash,$_SESSION['LIST_COL_SORT']) && !array_key_exists($this->items['LIST_COL_SORT'],$_SESSION['LIST_COL_SORT'][$this->sqlHash]));
                  $_SESSION['LIST_COL_SORT'][$this->sqlHash] = array($this->items['LIST_COL_SORT']=>$this->items['LIST_COL_SORT_ORDER']);
               }

               if(!$changedSession && array_key_exists('LIST_COL_SORT', $this->items) && ! empty($this->items['LIST_COL_SORT']))
               {
                  if ($this->items['LIST_COL_SORT_ORDER'] == 'DESC')
                  {
                     $icon = "&uarr;";
                  }
                  else
                  {
                     $icon = "&darr;";
                  }
               }
               else if (!$changedSession && is_array($_SESSION['LIST_COL_SORT']) && array_key_exists($this->sqlHash,$_SESSION['LIST_COL_SORT']))
               {
                  //load sort from session
                  $order = array_values($_SESSION['LIST_COL_SORT'][$this->sqlHash]);
                  if ($order[0] == 'DESC')
                  {
                     $colSort['LIST_COL_SORT_ORDER'] = "ASC";
                     $icon = "&uarr;";
                  }
                  else
                  {
                     $colSort['LIST_COL_SORT_ORDER'] = "DESC";
                     $icon = "&darr;";
                  }
               }
            }

            //Carry over any search criteria on a sort
            //
            if(array_key_exists('search_filter', $_GET) && ! empty($_GET['search_filter']))
            {
               $_GET['search_filter'] = trim($_GET['search_filter']);

               if(! empty($_GET['search_filter']))
               {
                  $colSort['search_filter'] = $_GET['search_filter'];
               }
            }

            if(array_key_exists('ajax_action', $this->items) && ! empty($this->items['ajax_action']))
            {
               $colSort['action'] = $this->items['ajax_action'];
            }
            $colSort['SQL_HASH']      = $this->sqlHash;

            $pieces = array('<!--COLSORTURL-->'       => BuildUrl($this->items['pageId'],$colSort,true),
                            '<!--NAME-->'             => $this->items['name'],
                            '<!--COLSORTICON->'       => $icon,
                            '<!--COL_HEADER_ID-->'    => str_replace(array(' '),array('_'),strip_tags($field)),
                            '<!--INLINE_STYLE-->'     => $colWidthStyle,
                            '<!--TITLE_POPUP-->'      => $popupTitle,
                            '<!--COL_HEADER_CLASS-->' => $colClass,
                            '<!--TITLE-->'            => $fieldTitle,
                            '<!--FUNCTION-->'         => $this->items['ajax_function']
                            );



            $headers[] = Fill($pieces, $this->items[$templateIdx]);
         }
         else
         {
            $pieces = array('<!--TITLE-->'            => $fieldTitle,
                            '<!--INLINE_STYLE-->'     => $colWidthStyle,
                            '<!--TITLE_POPUP-->'      => $popupTitle,
                            '<!--COL_HEADER_CLASS-->' => $colClass,
                            '<!--COL_HEADER_ID-->'    => str_replace(array(' '),array('_'),strip_tags($field)),
                           );

            $headers[] = Fill($pieces, $this->items[$templateIdx]);
         }

      }

      $this->templateFill['<!--COLSPAN_FULL-->'] = count($headers);

      if($this->items['mode'] != 'passive')
      {
         $pieces = array('<!--LIST_SEQUENCE-->' => $this->sequence,
                         '<!--TOTAL_PAGES-->'   => $this->totalPages);

         $this->templateFill['<!--PAGE_SEQUENCE_DISPLAY-->'] = Fill($pieces, $this->items['templateSequence']);
      }

      $this->templateFill['<!--PAGINATION_LIST-->'] = $this->BuildPagination();
      $this->templateFill['<!--HEADERS-->']         = implode($headers);

      if (!empty($this->items['listDescription']))
      {
         $fillDesc = array();
         $fillDesc['<!--COLSPAN-->']          = count($headers);
         $fillDesc['<!--LIST_DESCRIPTION-->'] = $this->items['listDescription'];
         $fillDesc['<!--LIST_NAME-->']        = $this->items['name'];
         $this->templateFill['<!--LIST_TITLE-->']      = Fill($fillDesc,$this->items['list_description']);
      }
      else
      {
         $this->templateFill['<!--LIST_TITLE-->'] = '';
      }

      unset($headers);
      unset($pieces);
   }

   public static function ClearSortGetVars()
   {
      unset($_GET['LIST_FILTER_ALL'],$_GET['ROW_LIMIT'],$_GET['LIST_SEQUENCE'],$_GET['LIST_COL_SORT_ORDER'],$_GET['LIST_COL_SORT'],$_GET['LIST_FILTER_ALL']);
   }

   /**
   * ClearSearchSession()
   *
   * pass $items['searchClear'] = ($customConditional) when you initialize to clear the session based on some case.  Good for edit pages with searches
   *
   */
   public function ClearSearchSession($all=false)
   {
      if (array_key_exists('SEARCH_FILTER',$_SESSION) && array_key_exists($this->items['name'],$_SESSION['SEARCH_FILTER']))
      {
         unset($_SESSION['SEARCH_FILTER'][$this->items['name']]);
      }
      if (array_key_exists('ROW_LIMIT',$_SESSION) && array_key_exists($this->items['name'],$_SESSION['ROW_LIMIT']))
      {
         unset($_SESSION['ROW_LIMIT'][$this->items['name']]);
      }

      if ($all && array_key_exists('SEARCH_FILTER',$_SESSION))
      {
         unset($_SESSION['SEARCH_FILTER']);
      }
      if ($all && array_key_exists('ROW_LIMIT',$_SESSION))
      {
         unset($_SESSION['ROW_LIMIT']);
      }
   }

   /**
   * ClearSQLSession()
   *
   * pass $items['searchClear'] = ($customConditional) when you initialize to clear the session based on some case.  Good for edit pages with searches
   *
   */
   public function ClearSQLSession($all=false)
   {
      if (array_key_exists('LIST_SEQUENCE',$_SESSION) && array_key_exists($this->sqlHash,$_SESSION['LIST_SEQUENCE']))
      {
         unset($_SESSION['LIST_SEQUENCE'][$this->sqlHash]);
      }
      if (array_key_exists('LIST_COL_SORT',$_SESSION) && array_key_exists($this->sqlHash,$_SESSION['LIST_COL_SORT']))
      {
         unset($_SESSION['LIST_COL_SORT'][$this->sqlHash]);
      }

      if ($all && array_key_exists('LIST_COL_SORT',$_SESSION))
      {
         unset($_SESSION['LIST_COL_SORT']);
      }
      if ($all && array_key_exists('LIST_SEQUENCE',$_SESSION))
      {
         unset($_SESSION['LIST_SEQUENCE']);
      }
   }

   /**
   * ClearCountSession()
   *
   */
   static public function ClearCountSession($all=false)
   {
      if (array_key_exists('list_count',$_SESSION))
      {
         unset($_SESSION['list_count']);
      }
   }


   /**
   * BuildStatement()
   *
   * Builds the select statement
   *
   * @return string $statement
   */
   private function BuildStatement()
   {
      $statement = '';
      $pieces    = array('<!--FIELDS-->'  => '',
                         '<!--SOURCE-->'  => '',
                         '<!--WHERE-->'   => '',
                         '<!--GROUPBY-->' => '',
                         '<!--ORDERBY-->' => '',
                         '<!--LIMIT-->'   => '');

      if(! empty($this->items['sql']) || ! empty($this->items['force_query_sql']))
      {
         if (!empty($this->items['fieldNames']))
         {
            foreach($this->items['fieldNames'] as $column)
            {
               $tick = '`';
               if(isset($this->items['function'][$column]))
               {
                  $tick   = '';
                  $column = $this->items['function'][$column];
               }
               $this->fieldList[] = "$tick$column$tick";
            }
            $fields = implode(',', $this->fieldList);
         }
         else
         {
            $fields = "*";
         }

         $sqlPieces = array();
         $sqlPieces['<!--FIELDS-->'] = $fields;
         $sqlPieces['<!--SQL-->']    = $this->items['sql'];

         if(! empty($this->items['force_query_sql']))
         {
            $statement = $this->items['force_query_sql'];
         }
         else
         {
            $statement = $this->items['statement']['select']['sql'];
         }

         $statement = str_replace(array_keys($sqlPieces), array_values($sqlPieces), $statement);
      }
      elseif(! empty($this->items['view']))
      {
         //Build out a list of columns to select from
         //
         foreach($this->items['fields'] as $column => $fieldTitle)
         {
            if(isset($this->items['function'][$column]))
            {
               $column = $this->items['function'][$column];
            }

            $this->fieldList[] = $column;
         }

         //Add any columns without corresponding header titles
         //
         foreach($this->items['columns'] as $column)
         {
            $this->fieldList[] = $column;
         }

         $viewPieces = array();
         $viewPieces['<!--FIELDS-->'] = implode(',', $this->fieldList);
         $viewPieces['<!--SOURCE-->'] = $this->items['view'];

         $statement = str_replace(array_keys($viewPieces), array_values($viewPieces), $this->items['statement']['select']['view']);
      }

      $this->sqlHash = md5(Fill($pieces, $statement));

      if ($this->items['searchClear'] || $this->items['searchClearAll'])
      {
         $this->ClearSQLSession(array_key_exists('searchClearAll',$this->items));
      }

      if (empty($this->items['LIST_SEQUENCE']))
      {
         if ($_SESSION['LIST_SEQUENCE'][$this->sqlHash] > 0)
         {
            $this->sequence = $_SESSION['LIST_SEQUENCE'][$this->sqlHash];
            $this->GenerateLimits();
         }
      }

      if(! empty($this->filter))
      {
         $where = '';
         if (! empty($this->items['sql']) )
         {
            $where = ' WHERE ';
         }
         elseif (empty($this->items['sql']))
         {
            $where = ' WHERE ';
         }
         $pieces['<!--WHERE-->'] = $where . $this->filter;
      }

      if(! empty($this->items['groupBy']))
      {
         $pieces['<!--GROUPBY-->'] .= ' GROUP BY ' . $this->items['groupBy'];
      }

      if(! empty($this->items['LIST_COL_SORT']) || (!empty($_SESSION['LIST_COL_SORT'][$this->sqlHash])))
      {
         if (! empty($this->items['LIST_COL_SORT']) )
         {
            $pieces['<!--ORDERBY-->'] .= ' ORDER BY `' . $this->items['LIST_COL_SORT'] . "` " . $this->items['LIST_COL_SORT_ORDER'];
         }
         else
         {
            list($column, $order) = each($_SESSION['LIST_COL_SORT'][$this->sqlHash]);
            $pieces['<!--ORDERBY-->'] .= ' ORDER BY `' . $column . "` " . $order;
         }

         // Add base order by
         if (! empty($this->items['orderBy']))
         {
            $pieces['<!--ORDERBY-->'] .= ',' . $this->items['orderBy'];
         }
      }
      elseif(! empty($this->items['orderBy']))
      {
         $pieces['<!--ORDERBY-->'] .= ' ORDER BY ' . $this->items['orderBy'];
      }

      $pieces['<!--LIMIT-->'] = ' LIMIT :LOW, :HIGH';

      $statement = Fill($pieces, $statement);

      if($this->items['rowLimit'] >= $this->totalRows)
      {
         $this->items['bindVars']['LOW'] = 0;
         $this->sequence = 1;
      }

      return $statement;
   }

   /**
   * BuildColumnLink()
   *
   * Pieces together a column link
   *
   * @return string
   */
   private function BuildColumnLink(&$column)
   {
      $links      = $this->items['links'][$column];
      $url        = array('PAGE_ID');
      $function   = $this->items['linkFunction'];
      $parameters = '';

      $url['PAGE_ID'] = $this->items['pageId'];

      if(array_key_exists('PAGE_ID', $links) && ! empty($links['PAGE_ID']))
      {
         $url['PAGE_ID'] = $links['PAGE_ID'];
      }
      if(array_key_exists('ACTION', $links) && ! empty($links['ACTION']))
      {
         $url['ACTION'] = $links['ACTION'];
      }
      if(array_key_exists('BUTTON_VALUE', $links) && ! empty($links['BUTTON_VALUE']))
      {
         $url['BUTTON_VALUE'] = $links['BUTTON_VALUE'];
      }
      if(array_key_exists('tags', $links))
      {
         foreach($links['tags'] as $tagName => $tag)
         {
            if(isset($this->results[$tag][$j]))
            {
               $url[$tagName] = $this->results[$tag][$j];
            }
            else
            {
               $url[$tagName] = $tag;
            }
         }
      }

      if(array_key_exists('onclick', $links) && is_array($links['onclick']))
      {
         if(isset($links['onclick']['function']))
         {
            $function = $links['onclick']['function'];
         }

         if(array_key_exists('tags', $links['onclick']) && is_array($links['onclick']['tags']))
         {
            foreach($links['onclick']['tags'] as $tagName => $tag)
            {
               if(isset($this->results[strtoupper($tag)][$j]))
               {
                  $parameters = ", '" . $this->results[strtoupper($tag)][$j]; "'";
               }
            }
         }
      }

      if(array_key_exists('ajax_action', $this->items) && ! empty($this->items['ajax_action']))
      {
         $url['action'] = $this->items['ajax_action'];
      }
      $url['SQL_HASH']      = $this->sqlHash;

      $linkUrl = BuildUrl($this->items['pageId'],$url);

      return "$function('$linkUrl'$parameters)";
   }

   /**
   * BuildColumnInput()
   *
   * Pieces together a column that is of type: input
   *
   * @return string
   */
   private function BuildColumnInput($column, $row = 0)
   {
      $content = '';

      $inputManager = $this->items['inputs'][$column];

      switch($inputManager['type'])
      {
         case 'checkbox':

            if(! array_key_exists('list_checks', $_SESSION) || ! array_key_exists($this->items['name'], $_SESSION['list_checks']) || ! array_key_exists($this->sqlHash, $_SESSION['list_checks'][$this->items['name']]))
            {
               $_SESSION['list_checks'][$this->items['name']][$this->sqlHash] = array();
            }

            $input = array();
            $input['name']        = 'widget_check_name';
            $input['id']          = 'widget_check_id';
            $input['check_all']   = false;
            $input['value']       = '';
            $input['checked']     = '';
            $input['onclick']     = '';
            $input['input_class'] = 'widgetlist-checkbox-input';

            $input['class_handle'] = '';

            $input = array_merge($input,$inputManager['items']);

            $onClick = array();
            $checkAllId = '';

            /**
            * Get a value. Assumes it is a column initially.
            *
            * @note headers are ignored and would fail as $row would be null
            */
            if(! empty($input['value']))
            {
               if(array_key_exists(strtoupper($input['value']), $this->results))
               {
                  $input['value'] = $this->results[strtoupper($input['value'])][$row];
               }
            }

            /**
            * Append class handle
            */
            $input['input_class'] = "{$input['input_class']} {$input['class_handle']}";

            if($input['check_all'])
            {
               $checkAllId = $input['id'];
               if(array_key_exists('list_checks', $_SESSION) && array_key_exists($this->items['name'], $_SESSION['list_checks']) && array_key_exists($this->sqlHash, $_SESSION['list_checks'][$this->items['name']]))
               {
                  if(array_key_exists('check_all_'.$this->sqlHash, $_SESSION['list_checks'][$this->items['name']]) && array_key_exists($this->sequence, $_SESSION['list_checks'][$this->items['name']]['check_all_'.$this->sqlHash]))
                  {
                     $input['checked'] = true;
                  }
               }

               /**
               * Set header class
               */
               if(is_array($this->items['headerClass']) && array_key_exists('checkbox', $this->items['headerClass']))
               {
                  if(array_key_exists('check_all_'.$this->sqlHash, $_SESSION['list_checks'][$this->items['name']]) && array_key_exists($this->sequence, $_SESSION['list_checks'][$this->items['name']]['check_all_'.$this->sqlHash]))
                  {
                     $input['checked'] = true;
                  }
               }
            }
            else
            {
               $input['input_class'] = "{$input['input_class']} {$input['class_handle']} {$input['class_handle']}_list";
            }

            /**
            * Setup onclick action
            */
            if(empty($input['onclick']))
            {
               $listJumpUrl['BUTTON_VALUE']        = $this->items['buttonVal'];
               $listJumpUrl['LIST_COL_SORT']       = $this->items['LIST_COL_SORT'];
               $listJumpUrl['LIST_COL_SORT_ORDER'] = $this->items['LIST_COL_SORT_ORDER'];
               $listJumpUrl['LIST_FILTER_ALL']     = $this->items['LIST_FILTER_ALL'];
               $listJumpUrl['ROW_LIMIT']           = $this->items['ROW_LIMIT'];
               $listJumpUrl['LIST_SEQUENCE']       = $this->sequence;
               $listJumpUrl['LIST_NAME']           = $this->items['name'];
               $listJumpUrl['SQL_HASH']            = $this->sqlHash;
               $listJumpUrl['action']              = 'ajax_widgetlist_checks';

               $listJumpUrl = BuildUrl(basename($_SERVER['PHP_SELF']),$listJumpUrl);

               $onClick[] = "AjaxMaintainChecks(this, '{$input['class_handle']}', '{$this->items['name']}', '{$listJumpUrl}', '{$checkAllId}');";
            }

            $input['onclick'] = implode(' ', $onClick);

            /**
            * Checkbox is checked or not per query value
            */
            if(! empty($this->items['checked_flag']))
            {
               if(array_key_exists($column, $this->items['checked_flag']))
               {
                  $input['checked'] = (bool) $this->results[strtoupper($this->items['checked_flag'][$column])][$row];
               }
            }

            /**
            * Checkbox is checked or not per session (overwrites query)
            */
            if(array_key_exists('list_checks', $_SESSION) && array_key_exists($this->items['name'], $_SESSION['list_checks']) && array_key_exists($this->sqlHash, $_SESSION['list_checks'][$this->items['name']]))
            {
               if(array_key_exists($input['value'], $_SESSION['list_checks'][$this->items['name']][$this->sqlHash]))
               {
                  $input['checked'] = true;
               }
            }

            $content = WidgetCheck($input);

         break;
         case 'text':
            $content = WidgetInput();
         break;
         case 'select':
            $content = WidgetSelect();
         break;
      }

      return $content;
   }

   /**
   * BuildColumnButtons()
   *
   * Pieces together a column button array
   *
   * @return string
   */
   private function BuildColumnButtons(&$column, $pointer)
   {
      $buttons     = $this->items['buttons'][$column];
      $columnValue = $this->results[strtoupper($column)][$pointer];
      $btnOut      = array();
      $strCnt      = 0;
      foreach($buttons as $buttonId => $buttonAttribs)
      {
         $url          = array('PAGE_ID');
         $function     = $this->items['linkFunction'];
         $parameters   = '';
         $renderButton = true;

         if(is_object($buttonAttribs))
         {
            $btnOut[] = $buttonAttribs;
         }
         else
         {
            if(array_key_exists('tags', $buttonAttribs))
            {
               foreach($buttonAttribs['tags'] as $tagName => $tag)
               {
                  //only uppercase will be replaced
                  //

                  if(isset($this->results[$tag][$pointer]))
                  {
                     $buttonAttribs['args'][$tagName] = $this->results[strtoupper($tag)][$pointer];
                  }
                  else
                  {
                     $buttonAttribs['args'][$tagName] = $tag;
                  }
               }
            }
            $nameId = $buttonId.'_'.$pointer;
            $buttonAttribs = array_merge($buttonAttribs,array('name'=>$nameId,'id'=>$nameId));
         }

         if(!is_object($buttonAttribs) && array_key_exists('condition', $buttonAttribs))
         {
            //never show button if you pass a condition unless explicitly matching the value of the features
            //
            $renderButton = false;
            $allConditions = explode(':', $columnValue);
            if (in_array(ltrim($buttonAttribs['condition'], ':'), $allConditions))
            {
               $renderButton = true;
            }
         }

         if ($renderButton)
         {
            $strCnt += (strlen($buttonAttribs['text']) * 15);
            $btnOut[] = WidgetButton($buttonAttribs['text'], $buttonAttribs, true);
         }
      }

      //BS width algorithm. HACK/TWEAK/OMG Get it working.
      //
      $colWdth = (($strCnt + (count($btnOut) * 35)) / 2) + 10;

      return '<div style="border:0px solid black;text-align:center;white-space:nowrap;margin:auto;width:'.$colWdth.'px"><div style="margin:auto;display:inline-block">'.implode('', $btnOut).'</div></div>';
   }

   public function autoColumnName($column)
   {
      return ucwords(str_replace(array("_","-"),array(" ","-"),strtolower($column)));
   }

   protected function BuildRows()
   {

      $sql = $this->BuildStatement();

      if($this->totalResultCount > 0)
      {
         if(empty($this->items['data']))
         {
            if ($this->debug)
            {
               error_log("WidgetList - {$this->items['name']} - MAIN SELECT start");
            }

            //Run the actual statement
            //
            $this->totalRowCount = $this->DATABASE->Select($sql, $this->items['bindVars']);

            if ($this->debug)
            {
               error_log("WidgetList - {$this->items['name']} - MAIN SELECT ended.  Running ".$this->DATABASE->lastSql);
            }
         }

         if($this->totalRowCount > 0)
         {
            if(empty($this->items['data']))
            {
               $this->results = $this->DATABASE->results;
               unset($this->DATABASE->results);
            }
            else
            {
               $this->results = &$this->items['data'];
            }

            //Build each row
            //
            $max = $this->totalRowCount-1;
            $rows = array();

            /**
            * For each row
            */
            for($j=0; $j<=$max; $j++)
            {
               $columns = array();
               $customRowColor = null;
               $customRowStyle = null;

               /**
               * For each column (field) in this row
               */
               foreach($this->items['fields'] as $column => $fieldTitle)
               {
                  $colClasses       = array();
                  $theStyle         = '';
                  $colData          = '';
                  $colClass         = '';
                  $onClick          = '';
                  $colWidthStyle    = '';
                  $content          = '';
                  $contentTitle     = '';

                  /**
                  * Column is a Link
                  */
                  if(array_key_exists($column, $this->items['links']) && is_array($this->items['links'][$column]))
                  {
                     $onClick = $this->BuildColumnLink($column);
                  }

                  /**
                  * Column is a Button
                  */
                  elseif(array_key_exists($column, $this->items['buttons']) && is_array($this->items['buttons'][$column]))
                  {
                     $content = $this->BuildColumnButtons($column, $j);
                  }

                  /**
                  * Column is an input
                  */
                  elseif(array_key_exists($column, $this->items['inputs']) && is_array($this->items['inputs'][$column]))
                  {
                     $colClasses[] = $this->items['checked_class'];
                     $content = $this->BuildColumnInput($column, $j);
                  }

                  /**
                  * Column is text
                  */
                  else
                  {
                     if(isset($this->results[strtoupper($column)][$j]))
                     {
                        $cleanData = strip_tags($this->results[strtoupper($column)][$j]);

                        if(strlen($cleanData) > $this->items['strlength'])
                        {
                           $contentTitle = $cleanData;

                           $possibleMatches = array("/(.*)(\(<a.*?>.*?<\/a>\)\s)(.*)/i" => array(3), //<a>(id)</a> Text
                                                    "/(.*)(<a.*?>)(.*)(<\/a>)(.*)/i"    => array(3)  //<a>Text</a> other text
                                                   );

                           foreach($possibleMatches as $regex => $toFix)
                           {
                              $matched = preg_match_all($regex, $this->results[strtoupper($column)][$j], $matches, PREG_PATTERN_ORDER);

                              if(! empty($matched))
                              {
                                 $pieces = array();

                                 unset($matches[0]);

                                 foreach($matches as $key => $theText)
                                 {
                                    $fixedText = '';

                                    if(in_array($key, $toFix))
                                    {
                                       $fixedText = substr($theText[0], 0, $this->items['strlength']) . '...';
                                    }
                                    else
                                    {
                                       $fixedText = $theText[0];
                                    }

                                    $pieces[] = $fixedText;
                                 }

                                 $content = implode('', $pieces);

                                 break;
                              }
                           }

                           if(empty($matched))
                           {
                              if ((strpos($this->results[strtoupper($column)][$j],'&#') !== false && strpos($this->results[strtoupper($column)][$j],';') !== false))
                              {
                                 $content = $this->results[strtoupper($column)][$j];
                              }
                              else
                              {
                                 $content = substr($this->results[strtoupper($column)][$j], 0, $this->items['strlength']) . '...';
                              }
                           }
                        }
                        else
                        {
                           $content = $this->results[strtoupper($column)][$j];
                        }

                        //Stip HTML
                        //
                        if(! $this->items['allowHTML'])
                        {
                           $content = strip_tags($content);
                        }

                        $content = $this->DATABASE->Bind($content, $this->items['bindVars'], false);

                        //Column color
                        //
                        if(! empty($this->items['columnStyle']))
                        {
                           if(array_key_exists(strtolower($column), $this->items['columnStyle']))
                           {
                              $colHeader  = $this->items['columnStyle'][strtolower($column)];

                              if(array_key_exists(strtoupper($colHeader), $this->results))
                              {
                                 $theStyle = $this->results[strtoupper($colHeader)][$j];
                              }
                              else
                              {
                                 $theStyle = $colHeader;
                              }
                           }
                        }

                        //Column width
                        //
                        if(! empty($this->items['columnWidth']))
                        {
                           if(array_key_exists(strtolower($column), $this->items['columnWidth']))
                           {
                              $colWidthStyle = "width:".$this->items['columnWidth'][strtolower($column)].";";
                           }
                        }

                        //Column Class
                        //
                        if(! empty($this->items['columnClass']))
                        {
                           if(array_key_exists(strtolower($column), $this->items['columnClass']))
                           {
                              $colClasses[] = $this->items['columnClass'][strtolower($column)];
                           }
                        }
                     }
                  }

                  /**
                  * Setup any column classes
                  */
                  $colClasses[] = $this->items['collClass'];
                  $colClass = implode(' ', $colClasses);
                  unset($colClasses);

                  /**
                  * Row Color
                  */
                  if (!empty($this->items['rowColorByStatus']) && array_key_exists($column, $this->items['rowColorByStatus']) && is_array($this->items['rowColorByStatus'][$column]))
                  {
                     foreach ($this->items['rowColorByStatus'][$column] as $status=>$color)
                     {
                        if ($status === $content)
                        {
                           $customRowColor = $color;
                        }
                     }
                  }

                  /**
                  * Row Style
                  */
                  if (!empty($this->items['rowStylesByStatus']) && array_key_exists($column, $this->items['rowStylesByStatus']) && is_array($this->items['rowStylesByStatus'][$column]))
                  {
                     foreach ($this->items['rowStylesByStatus'][$column] as $status=>$inlineStyle)
                     {
                        if ($status === $content)
                        {
                           $customRowStyle = $inlineStyle;
                        }
                     }
                  }

                  /**
                  * Set up Column Pieces
                  */
                  $colPieces['<!--CLASS-->']   = $colClass;
                  $colPieces['<!--ALIGN-->']   = $this->items['collAlign'];
                  $colPieces['<!--STYLE-->']   = $theStyle . $colWidthStyle;
                  $colPieces['<!--ONCLICK-->'] = $onClick;

                  if ((strpos($contentTitle,'&#') !== false && strpos($contentTitle,';') !== false))
                  {
                     $colPieces['<!--TITLE-->']   = '';
                  }
                  else
                  {
                     $colPieces['<!--TITLE-->']   = htmlentities($contentTitle);
                  }
                  $colPieces['<!--CONTENT-->'] = $content;

                  /**
                  * Assemble the Column
                  */
                  $columns[] = Fill($colPieces, $this->items['col']);
               }


               //Draw the row
               //
               $pieces = array('<!--CONTENT-->' => implode('', $columns));

               if (empty($this->items['rowColorByStatus']) && empty($this->items['rowStylesByStatus']))
               {
                  //Set the row color
                  //
                  $rowColor = $this->items['rowColor'];

                  if($this->items['offsetRows'])
                  {
                     if($j&1)
                     {
                        $rowColor = $this->items['rowOffsets'][1];
                     }
                     else
                     {
                        $rowColor = $this->items['rowOffsets'][0];
                     }
                  }
                  //Draw default color
                  //
                  $pieces['<!--BGCOLOR-->']  = $rowColor;
                  $pieces['<!--ROWSTYLE-->'] = '';
                  $pieces['<!--ROWCLASS-->'] = $this->items['rowClass'];
               }
               else
               {
                  $pieces['<!--BGCOLOR-->']   = (!is_null($customRowColor)) ? $customRowColor : $this->items['rowColor'];
                  $pieces['<!--ROWSTYLE-->']  = (!is_null($customRowStyle)) ? $customRowStyle : '';
                  $pieces['<!--ROWCLASS-->']  = $this->items['rowClass'];
               }
               $rows[] = Fill($pieces, $this->items['row']);
            }

            $this->templateFill['<!--DATA-->'] = implode('', $rows);
         }
         else
         {

            if ($this->debug === true)
            {
               $sqlDebug = "<br/><br/><textarea style='width:100%;height:400px;'>".$this->DATABASE->lastSql."</textarea>";
            }
            if ($this->debug === true && $this->DATABASE->error === true)
            {
               $errorMsg = "<strong style='color:red'>(".$this->DATABASE->errorMsg.")</strong>";
            }
            $this->templateFill['<!--DATA-->'] = '<tr><td colspan="50"><div id="noListResults">'.$this->items['noDataMessage'].$errorMsg.$sqlDebug.'</div></td></tr>';
         }

      }
      else
      {
         if ($this->debug === true)
         {
            $sqlDebug = "<br/><br/><textarea style='width:100%;height:400px;'>".$this->DATABASE->lastSql."</textarea>";
         }
         if ($this->debug === true && $this->DATABASE->error === true)
         {
            $errorMsg = "<strong style='color:red'>(".$this->DATABASE->errorMsg.")</strong>";
         }
         $this->templateFill['<!--DATA-->'] = '<tr><td colspan="50"><div id="noListResults">'.$this->items['noDataMessage'].$errorMsg.$sqlDebug.'</div></td></tr>';
      }
   }

   /**
   * Render()
   *
   * Builds the entire widget component
   *
   * @return string
   */
   public function Render(&$results = '')
   {

      if(! empty($results))
      {
         $this->items['data'] = $results;
      }

      //Get total records for statement validation and pagination
      //
      if(! empty($this->items['data']) && empty($this->items['fields']))
      {
         foreach($this->items['data'] as $field => $content)
         {
            $this->items['fields'][strtolower($field)] = $this->autoColumnName($field);
         }
      }

      if(empty($this->items['data']))
      {
         $this->totalResultCount = $this->GetTotalRecords();
      }
      else
      {
         foreach($this->items['data'] as $field => $content)
         {
            $this->totalResultCount = count($this->items['data'][$field]);
            $this->totalRowCount = $this->totalResultCount;
            $this->totalRows = $this->totalResultCount;
            break;
         }
      }

      $this->BuildRows();

      $this->BuildHeaders();

      $listJumpUrl['PAGE_ID']             = $this->items['pageId'];
      $listJumpUrl['ACTION']              = 'AJAX';
      $listJumpUrl['BUTTON_VALUE']        = $this->items['buttonVal'];
      $listJumpUrl['LIST_COL_SORT']       = $this->items['LIST_COL_SORT'];
      $listJumpUrl['LIST_COL_SORT_ORDER'] = $this->items['LIST_COL_SORT_ORDER'];
      $listJumpUrl['LIST_FILTER_ALL']     = $this->items['LIST_FILTER_ALL'];
      $listJumpUrl['ROW_LIMIT']           = $this->items['ROW_LIMIT'];
      $listJumpUrl['LIST_SEQUENCE']       = $this->sequence;
      $listJumpUrl['LIST_NAME']           = $this->items['name'];
      $listJumpUrl['SQL_HASH']            = $this->sqlHash;

      if(array_key_exists('ajax_action', $this->items) && ! empty($this->items['ajax_action']))
      {
         $listJumpUrl['action'] = $this->items['ajax_action'];
      }
      $listJumpUrl = BuildUrl($this->items['pageId'],$listJumpUrl,true);

      $this->templateFill['<!--HEADER-->'] = $this->items['templateHeader'];
      $this->templateFill['<!--TITLE-->'] = $this->items['title'];
      $this->templateFill['<!--NAME-->'] = $this->items['name'];
      $this->templateFill['<!--JUMP_URL-->'] = $listJumpUrl;
      $this->templateFill['<!--JUMP_URL_NAME-->'] = $this->items['name'] . '_jump_url';
      $this->templateFill['<!--CLASS-->'] = $this->items['class'];

      if($this->totalRowCount > 0)
      {
         $this->templateFill['<!--INLINE_STYLE-->'] = '';
         $this->templateFill['<!--TABLE_CLASS-->'] = $this->items['tableclass'];
      }
      else
      {
         $this->templateFill['<!--INLINE_STYLE-->'] = 'table-layout:auto;';
      }

      //Filter form
      //
      if(! empty($this->items['showSearch']))
      {
         if(! empty($this->items['templateFilter']))
         {
            $this->templateFill['<!--FILTER_HEADER-->'] = $this->items['templateFilter'];
         }
         else
         {
            if(!array_key_exists('search_filter', $_GET) && empty($_GET['search_filter']) && !$this->isJumpingList)
            {
               //Search page url
               //
               $searchUrl = '';
               $searchVal = '';

               if(! empty($this->items['buttonVal']))
               {
                  $searchVal = $this->items['buttonVal'];
               }
               else
               {
                  $searchVal = $this->items['name'];
               }

               $filterParameters = array();
               $filterParameters['BUTTON_VALUE'] = $searchVal;
               $filterParameters['PAGE_ID']      = $this->items['pageId'];
               $filterParameters['LIST_NAME']    = $this->items['name'];
               $filterParameters['SQL_HASH']     = $this->sqlHash;
               if(array_key_exists('ajax_action', $this->items) && ! empty($this->items['ajax_action']))
               {
                  $filterParameters['action'] = $this->items['ajax_action'];
               }

               $searchUrl = BuildUrl($this->items['pageId'], $filterParameters, true);

               $INPUTS  = array();
               /**
               * Search value
               */
               $INPUTS['list_search']['value'] = '';

               if($this->items['searchSession'])
               {
                  if(array_key_exists('SEARCH_FILTER',$_SESSION) && array_key_exists($this->items['name'],$_SESSION['SEARCH_FILTER']))
                  {
                     $INPUTS['list_search']['value'] = $_SESSION['SEARCH_FILTER'][$this->items['name']];
                  }
               }

               /**
               * Search Input Field
               */
               $INPUTS['list_search']['list-search'] = true;
               $INPUTS['list_search']['width']       = '500';
               $INPUTS['list_search']['input_class'] = 'info-input';
               $INPUTS['list_search']['title']       = (empty($this->items['searchTitle'])) ? $this->items['searchBtnName'] :$this->items['searchTitle'];
               $INPUTS['list_search']['id']          = 'list_search_id_' . $this->items['name'];
               $INPUTS['list_search']['name']        = 'list_search_name_' . $this->items['name'];
               $INPUTS['list_search']['class']       = 'inputOuter widget-search-outer '.strtolower($this->items['name']).'-search';

               $searchAheadAttributes = array();
               $searchAheadAttributes['url']         = $searchUrl;
               $searchAheadAttributes['skip_queue']  = false;
               $searchAheadAttributes['target']      = $this->items['name'];
               $searchAheadAttributes['search_form'] = $this->items['list_search_form'];
               $searchAheadAttributes['onclick']     = '';

               if(! empty($this->items['searchOnclick']) && ! empty($this->items['list_search_form']))
               {
                  $searchAheadAttributes['onclick'] = $this->items['searchOnclick'];
               }

               if(! empty($this->items['searchOnkeyup']))
               {
                  $searchAheadAttributes['onkeyup'] = $this->items['searchOnkeyup'];
               }

               $INPUTS['list_search']['search_ahead'] = array_merge($searchAheadAttributes, $this->items['list_search_attribs']);

               $this->templateFill['<!--FILTER_HEADER-->'] = WidgetInput($INPUTS['list_search']);

               /**
               * Grouping box
               */

               if(!empty($this->items['groupByItems']))
               {
                  $INPUTS['list_group']['arrow_action']  = 'var stub;';
                  $INPUTS['list_group']['readonly']      = true;
                  if ($this->items['groupBySelected'])
                  {
                     $INPUTS['list_group']['value']      = $this->items['groupBySelected'];
                  }
                  else
                  {
                     $INPUTS['list_group']['value']      = $this->items['groupByItems'][key($this->items['groupByItems'])];
                  }
                  $INPUTS['list_group']['style']         = 'cursor:pointer;margin-left:5px;';
                  $INPUTS['list_group']['input_style']   = 'cursor:pointer;';
                  $INPUTS['list_group']['outer_onclick'] = 'ToggleAdvancedSearch(this);SelectBoxResetSelectedRow(\''.$this->items['name'].'\');';
                  $INPUTS['list_group']['list-search']   = false;
                  $INPUTS['list_group']['width']         = '200';//hard code for now.  needs to be dynamic based on max str length if this caller is made into a "WidgetFakeSelect"
                  $INPUTS['list_group']['id']            = 'list_group_id_' . $this->items['name'];
                  $INPUTS['list_group']['name']          = 'list_group_name_' . $this->items['name'];
                  $INPUTS['list_group']['class']         = 'inputOuter widget-search-outer '.strtolower($this->items['name']).'-group';

                  $groupRows = array();
                  if (!$this->items['groupBySelected'])
                  {
                     $class     = 'widget-search-results-row-selected';
                  }

                  foreach ($this->items['groupByItems'] as $grouping)
                  {
                     if ($this->items['groupBySelected'] && $this->items['groupBySelected'] === $grouping)
                     {
                        $class     = 'widget-search-results-row-selected';
                     }
                     $groupRows[] = '<div class="widget-search-results-row '.$class.'" title="'.$grouping.'" onmouseover="ll(\'.widget-search-results-row\').removeClass(\'widget-search-results-row-selected\')" onclick="SelectBoxSetValue(\''.$grouping.'\',\''.$this->items['name'].'\');'.$this->items['groupByClick'].'">'.$grouping.'</div>';
                     $class = '';
                  }

                  $INPUTS['list_group']['search_ahead']  = array(
                     'skip_queue' => false,
                     'search_form'=>  '
                     <div id="advanced-search-container" style="height:100% !important;">
                        '.implode("\n",$groupRows).'
                     </div>',
                     'onclick'    => $this->items['searchOnclick'],
                  );

                  $this->templateFill['<!--FILTER_HEADER-->'] .= '<div class="fake-select"><div class="label">'.$this->items['groupByLabel'].':</div> '.WidgetInput($INPUTS['list_group']).'</div>';
               }

            }
            else
            {
               $this->templateFill['<!--FILTER_HEADER-->'] = '';
            }
         }
      }
      return Fill($this->templateFill, $this->items['template']);
   }

   private function BuildPagination()
   {
      $pageRange = 3;
      $pageNext  = 1;
      $pagePrev  = 1;
      $showPrev  = false;
      $showNext  = true;
      $prevUrl      = '';
      $nextUrl      = '';
      $tags      = '';

      $urlTags['SQL_HASH']        = $this->sqlHash;
      $urlTags['PAGE_ID']         = $this->items['pageId'];
      $urlTags['LIST_NAME']       = $this->items['name'];
      $urlTags['BUTTON_VALUE']    = $this->items['buttonVal'];
      $urlTags['LIST_FILTER_ALL'] = $this->items['LIST_FILTER_ALL'];

      $templates['btn_previous'] = $this->items['template_pagination_previous_disabled'];
      $templates['btn_next']     = $this->items['template_pagination_next_active'];

      //Carry over any search criteria on a sort
      //
      if(array_key_exists('search_filter', $_GET) && ! empty($_GET['search_filter']))
      {
         $_GET['search_filter'] = trim($_GET['search_filter']);

         if(! empty($_GET['search_filter']))
         {
            $urlTags['search_filter'] = $_GET['search_filter'];
         }
      }

      if( empty($this->items['LIST_COL_SORT']))
      {
         $urlTags['LIST_COL_SORT']       = $this->items['LIST_COL_SORT'];
         $urlTags['LIST_COL_SORT_ORDER'] = $this->items['LIST_COL_SORT_ORDER'];
         $urlTags['ROW_LIMIT']           = $this->items['ROW_LIMIT'];
      }

      if(array_key_exists('paginate', $this->items['links']) && is_array($this->items['links']['paginate']))
      {
         $links = $this->items['links']['paginate'];

         foreach($links as $tagName => $tag)
         {
            $urlTags[$tagName] = $tag;
         }
      }

      if(array_key_exists('ajax_action', $this->items) && ! empty($this->items['ajax_action']))
      {
         $urlTags['action'] = $this->items['ajax_action'];
      }

      if($this->sequence == $this->totalPages || ! $this->totalPages > 0)
      {
         $showNext = false;
      }
      else
      {
         $pageNext = $this->sequence + 1;

         $urlTags['LIST_SEQUENCE'] = $pageNext;
         $nextUrl = BuildUrl($this->items['pageId'],$urlTags,true);
      }

      if($this->sequence > 1)
      {
         $pagePrev = $this->sequence - 1;

         $urlTags['LIST_SEQUENCE'] = $pagePrev;
         $prevUrl = BuildUrl($this->items['pageId'],$urlTags,true);

         $showPrev = true;
      }

      if(! $showNext)
      {
         $templates['btn_next'] = $this->items['template_pagination_next_disabled'];
      }

      if($showPrev)
      {
         $templates['btn_previous'] = $this->items['template_pagination_previous_active'];
      }

      //Assemble navigation buttons
      //
      $pieces = array();
      $values = array();

      $pieces[] = '<!--NEXT_URL-->';
      $pieces[] = '<!--LIST_NAME-->';
      $pieces[] = '<!--HTTP_SERVER-->';
      $pieces[] = '<!--PREVIOUS_URL-->';
      $pieces[] = '<!--FUNCTION-->';

      $values[] = $nextUrl;
      $values[] = $this->items['name'];
      $values[] = (($_SERVER['SERVER_PORT'] == 80) ? 'http://' : 'https://').$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']).'/';
      $values[] = $prevUrl;
      $values[] = $this->items['ajax_function'];

      $templates['btn_next']     = str_replace($pieces, $values, $templates['btn_next']);
      $templates['btn_previous'] = str_replace($pieces, $values, $templates['btn_previous']);

      /**
      * Sequence Range Drop Down
      *
      * Show x per page
      */
      $urlTags['LIST_SEQUENCE'] = $this->sequence;
      $urlTags['ROW_LIMIT'] = 10;

      //Automate select box and rules
      //
      $rowLimitSelect = array(10,20,50,100,500,1000);
      $rowLimitSelectData = array();
      $rowLimitSelectConfigs = array();

      //Set a default of 10
      //
      $urlTags['ROW_LIMIT'] = 10;

      $rowLimitUrl = BuildUrl($this->items['pageId'],$urlTags,true);

      $rowLimitSelectData[$rowLimitUrl] = 10;

      foreach($rowLimitSelect as $key => $jumpCount)
      {
         if($this->totalRows >= $jumpCount || $this->totalRows > $rowLimitSelect[$key-1])
         {
            $urlTags['ROW_LIMIT'] = $jumpCount;

            $rowLimitUrl = BuildUrl($this->items['pageId'],$urlTags,true);

            $rowLimitSelectData[$rowLimitUrl] = $jumpCount;

            if($this->items['rowLimit'] == $jumpCount)
            {
               $rowLimitSelectConfigs['selected'] = $rowLimitUrl;
            }
         }
      }

      $rowLimitSelectConfigs['view'] = $rowLimitSelectData;
      $rowLimitSelectConfigs['width']    = 58;
      $rowLimitSelectConfigs['onchange'] = $this->items['ajax_function'] . "(this.value,'{$this->items['name']}')";

      $pageSelect = WidgetSelect('', $rowLimitSelectConfigs);


      //Ensure the range does not exceed the actual number of pages
      //
      if($this->totalPages < $pageRange)
      {
         $pageRange = $this->totalPages;
      }

      /**
      * Create a range of x or less numbers.
      *
      * Take 2 off and add 2 or as much as possible either way
      */
      $startingPoint = $this->sequence;
      $kill = $pageRange;

      while($kill > 0)
      {
         $kill--;

         if($startingPoint <= 1)
         {
            break;
         }
         else
         {
            $startingPoint--;
         }
      }

      $endPoint = $this->sequence;
      $kill = $pageRange;

      while($kill > 0)
      {
         $kill--;

         if($endPoint < $this->totalPages)
         {
            $endPoint++;
         }
         else
         {
            break;
         }
      }

      $jumpSection = array();

      //Builds jump section    previous 4 5 6 7 next
      //
      for($page=$startingPoint; $page<=$endPoint; $page++)
      {
         $urlTags['LIST_SEQUENCE'] = $page;
         $urlTags['SQL_HASH']      = $this->sqlHash;
         $jumpTemplate = '';
         $jumpUrl = '';

         $jumpUrl = BuildUrl($this->items['pageId'], $urlTags, true);

         if($page == $this->sequence)
         {
            $jumpTemplate = $this->items['template_pagination_jump_active'];
         }
         else
         {
            $jumpTemplate = $this->items['template_pagination_jump_unactive'];
         }

         $pieces = array('<!--SEQUENCE-->','<!--JUMP_URL-->','<!--LIST_NAME-->','<!--FUNCTION-->');
         $values = array($page,$jumpUrl,$this->items['name'],$this->items['ajax_function']);

         $jumpSection[] = str_replace($pieces, $values, $jumpTemplate);
      }

      $jumpSection = implode('',$jumpSection);

      $pieces = array();
      $values = array();

      $pieces[] = '<!--PREVIOUS_BUTTON-->';
      $pieces[] = '<!--SEQUENCE-->';
      $pieces[] = '<!--NEXT_BUTTON-->';
      $pieces[] = '<!--TOTAL_PAGES-->';
      $pieces[] = '<!--TOTAL_ROWS-->';
      $pieces[] = '<!--PAGE_SEQUENCE_JUMP_LIST-->';
      $pieces[] = '<!--JUMP-->';
      $pieces[] = '<!--LIST_NAME-->';

      $values[] = $templates['btn_previous'];
      $values[] = $this->sequence;
      $values[] = $templates['btn_next'];
      $values[] = $this->totalPages;
      $values[] = $this->totalRows;
      $values[] = $pageSelect;
      $values[] = $jumpSection;
      $values[] = $this->items['name'];

      $paginationOutput = str_replace($pieces,$values,$this->items['template_pagination_wrapper']);
      //Commenting out this so that list pagination boxes are always there - session restore no likey this block
      //
      /*if($this->totalPages <= 1)
      {
         $paginationOutput = '';
      }*/
      if ($this->items['showPagination'])
      {
         return $paginationOutput;
      }
      else
      {
         return '';
      }
   }

   /**
   * GetTotalRecords()
   */
   private function GetTotalRecords()
   {

      $filter = '';
      $fields = array();
      $sql    = '';
      $hashed = false;

      if(! empty($this->items['force_count_sql']))
      {
         $sql = $this->items['force_count_sql'];
      }
      elseif(! empty($this->items['table']))
      {
         $sql = str_replace('<!--TABLE-->', $this->items['table'], $this->items['statement']['count']['table']);
      }
      elseif(! empty($this->items['sql']))
      {
         $sql = str_replace('<!--SQL-->', $this->items['sql'], $this->items['statement']['count']['sql']);
      }
      elseif(! empty($this->items['view']))
      {
         $sql = str_replace('<!--VIEW-->', $this->items['view'], $this->items['statement']['count']['view']);
      }

      $filter = '';
      if(! empty($this->filter))
      {
         $filter = ' WHERE ' . $this->filter;
      }

      $sql = str_replace('<!--WHERE-->',$filter, $sql);

      $queryHash = md5($this->DATABASE->Bind($sql, $this->items['bindVars'], false));

      if(! empty($sql))
      {
         if ($this->items['showPagination'])
         {
            $usedCountCache = 'No';

            /**
            * Check if a count limit cache exists
            */
            if ($this->items['cachedCount'] != -1)
            {
               $hashed = true;
               $rows   = 1;
               $this->totalRows = $this->items['cachedCount'];
               $usedCountCache = 'Yes';
            }
            elseif(array_key_exists('list_count', $_SESSION))
            {
               if(array_key_exists($queryHash, $_SESSION['list_count']))
               {
                  $queryTotalAttribs = $_SESSION['list_count'][$queryHash];

                  $secondsPassed = abs((time() - $queryTotalAttribs['stamp']));

                  if(! empty($secondsPassed) && ! ($secondsPassed > $this->items['count_cache_time']))
                  {
                     $hashed = true;
                     $rows   = 1;
                     $this->totalRows = $queryTotalAttribs['count'];
                     $usedCountCache = 'Yes';
                  }
               }
            }

            if(! $hashed)
            {
               $rows = $this->DATABASE->Select($sql, $this->items['bindVars']);

               if($rows > 0)
               {
                  $this->totalRows = $this->DATABASE->results['TOTAL'][0];

                  $_SESSION['list_count'][$queryHash]['stamp'] = time();
                  $_SESSION['list_count'][$queryHash]['count'] = $this->DATABASE->results['TOTAL'][0];
               }
            }
         }
         else
         {
            $rows = 1;
         }
      }
      else
      {
         $rows = 0;
      }

      if($this->totalRows > 0)
      {
         $this->totalPages = ceil($this->totalRows / $this->items['rowLimit']);
      }

      return $rows;
   }


   public static function GetFilterAndDrillDown($listId)
   {
      $filter = '';
      $drillDown = '';
      if(!array_key_exists('BUTTON_VALUE', $_REQUEST))
      {
         //Initialize page load/Session stuff whe list first loads
         //
         WidgetList::ClearCheckBoxSession($listId);
      }

      if (array_key_exists('drill_down',$_REQUEST))
      {
         $drillDown = $_REQUEST['drill_down'];
         $_SESSION['DRILL_DOWNS'][$listId] = $drillDown;
      }
      elseif(array_key_exists('DRILL_DOWNS',$_SESSION) && array_key_exists($listId,$_SESSION['DRILL_DOWNS']))
      {
         $drillDown = $_SESSION['DRILL_DOWNS'][$listId];
      }
      else
      {
         $drillDown = 'default';
      }

      if (array_key_exists('filter',$_REQUEST))
      {
         $filter = $_REQUEST['filter'];
         $_SESSION['DRILL_DOWN_FILTERS'][$listId] = $filter;
      }
      elseif(array_key_exists('DRILL_DOWN_FILTERS',$_SESSION) && array_key_exists($listId,$_SESSION['DRILL_DOWN_FILTERS']))
      {
         $filter = $_SESSION['DRILL_DOWN_FILTERS'][$listId];
      }
      return array($drillDown,$filter);
   }

   public static function ClearCheckBoxSession($list)
   {
      /*hack for pages with drilldown using GetFilterAndDrillDown static helper*/
      if(array_key_exists('DRILL_DOWN_FILTERS',$_SESSION))
      {
         unset($_SESSION['DRILL_DOWN_FILTERS']);
      }

      if(array_key_exists('DRILL_DOWNS',$_SESSION))
      {
         unset($_SESSION['DRILL_DOWNS']);
      }

      if (array_key_exists('list_checks',$_SESSION) && array_key_exists($list,$_SESSION['list_checks']))
      {
         unset($_SESSION['list_checks'][$list]);
      }
   }

   public static function BuildDrillDownLinkColumn($listId,$drillDownName,$dataToPassFromView,$columnToShow,$functionName='ListDrillDown',$columnAlias='',$columnClass='')
   {
      if (empty($columnAlias))
      {
         $columnAlias = $columnToShow;
      }

      if (!empty($columnClass))
      {
         $columnClass = " ',$columnClass,'";
      }
      return <<<EOD
      CONCAT('<a style="cursor:pointer;" class="{$columnAlias}_drill{$columnClass}" onclick="$functionName(\'$drillDownName\',\'', $dataToPassFromView ,'\',\'$listId\')">',$columnToShow,'</a>')  as $columnAlias,
EOD;
   }

   public function __destructor()
   {
      $this->results = array();
   }
}

?>