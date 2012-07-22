<?php

/**
* WidgetSelect()
*
* It expects an ID and VALUE from the query
*
* @todo add a preg_replace for (width.*px|em) on $items['style'] 7/16
* @todo make it so you dont have to explicitly set sql to null when not passing it as a first parameter
* @todo need to support individual template replacement for the multip dimensional template array
*
* @param string $sql
* @param mixed $list
*/
$widgetSelectCache = array();

function WidgetSelect($sql = '', $list = array())
{
   global $DATABASE;
   global $widgetSelectCache;
   global $G_TEMPLATE;

   $valid = true;
   $selectOutput = array();

   //Parameter evaluation identification
   //
   if(empty($list) && is_array($sql))
   {
      $list = $sql;
   }

   if(! empty($sql) && ! array_key_exists('sql',$list) && ! array_key_exists('view',$list))
   {
      $list['sql'] = $sql;
   }

   /**
   * Default configurations
   */
   $items = array();

   $items['name']              = '';
   $items['id']                = '';
   $items['multiple']          = false;
   $items['insertUnknown']     = false;
   $items['required']          = '';
   $items['strLenBrk']         = 50;
   $items['optGroupIndicator'] = '';
   $items['label']             = '';
   $items['selectTxt']         = 'Select One';
   $items['showId']            = false;
   $items['showIdLeft']        = false;
   $items['passive']           = false;
   $items['freeflow']          = false;
   $items['no_style']          = false;

   /**
   * Select box attributes
   */
   $items['attribs']   = array();
   $items['size']      = 1;
   $items['disabled']  = false;
   $items['noDataMsg'] = 'No Data';
   $items['selectOne'] = 0;
   $items['selected']  = array();

   /**
   * SQL
   */
   $items['bindVars'] = array();
   $items['view']     = '';
   $items['sql']      = '';
   $items['orderBy']  = '';
   $items['filter']   = '';

   /**
   * Actions
   */
   $items['onchange'] = '';
   $items['onclick']  = '';

   /**
   * Style
   */
   $items['style']       = '';             //meant for internal use as nothing is appended; only replaced.
   $items['inner_class'] = 'select-inner'; //Left long piece of image
   $items['outer_class'] = 'select-outer'; //Right corner of image
   $items['outer_style'] = '';             //Basically the width of the select box ends up here
   $items['extra_class'] = '';             //Not used currently 7/16
   $items['width']       = 184;            //width of the select-outer element

   /**
   * @deprecated
   */
   $items['class'] = '';


   /**
   * Setup templates
   */
   if($items['freeflow'])
   {
      /**
      * Freeflow
      */
      $items['template']['wrapper'] = $G_TEMPLATE['widget']['selectfree']['wrapper'];
      $items['template']['option']  = $G_TEMPLATE['widget']['selectfree']['option'];
      $items['template']['initial'] = '';
      $items['template']['passive'] = '';
   }
   else
   {
      /**
      * Standard
      */
      $items['template']['wrapper'] = $G_TEMPLATE['widget']['selectbox']['wrapper'];
      $items['template']['option']  = $G_TEMPLATE['widget']['selectbox']['option'];
      $items['template']['initial'] = $G_TEMPLATE['widget']['selectbox']['initial'];
      $items['template']['passive'] = $G_TEMPLATE['widget']['selectbox']['passive'];
   }

   if (array_key_exists('selected',$list) && !is_array($list['selected']))
   {
      $list['selected'] = array($list['selected']);
   }

   /**
   * Merge settings
   */
   $items = array_merge($items,$list);

   if($items['no_style'])
   {
      $items['template']['wrapper'] = $G_TEMPLATE['widget']['selectbox_nostyle']['wrapper'];
   }

   /**
   * Legacy class support and width
   */
   if(stristr($items['class'], 'inputLarge')) //197
   {
      $items['outer_style'] = ';width:184px;';
      $items['width'] = 184;
   }
   elseif(stristr($items['class'], 'inputLong')) //300
   {
      $items['outer_style'] = ';width:281px;';
      $items['width'] = 281;
   }
   elseif(stristr($items['class'], 'inputSmall'))
   {
      $items['outer_style'] = ';width:130px;';
      $items['width'] = 130;
   }
   else
   {
      if(! empty($items['width']) && is_numeric($items['width']))
      {
         $items['outer_style'] = ";width:{$items['width']}px;";
      }
      else
      {
         $items['outer_style'] = ';width:184px;';
         $items['width'] = 184;
      }
   }

   $items['class'] = trim(str_replace(array('inputLarge', 'inputLong', 'inputSmall'), '', $items['class']));

   /**
   * If multiple then override above settings and classes
   */
   if($items['multiple'])
   {
      $items['outer_style'] = ';width:280px;';
      $items['width'] = 280;

      $items['inner_class'] = 'select-inner-multiple';
      $items['outer_class'] = 'select-outer-multiple';

      /**
      * Backwards compatibility--sorry if you actually want 5!
      */
      if($items['size'] == 5)
      {
         $items['size'] = 7;
      }
   }

   if($items['showId'])
   {
      $items['template']['option'] = $G_TEMPLATE['widget']['selectbox']['option_showid'];
   }

   if($items['showIdLeft'])
   {
      $items['template']['option'] = $G_TEMPLATE['widget']['selectbox']['option_showid_left'];
   }

   if($items['disabled'])
   {
      $items['disabled']  = 'disabled="disabled"';
      $items['class']    .= ' disabled';
   }

   if($items['multiple'])
   {
      $items['multiple'] = 'multiple="multiple"';
   }

   if($items['required'])
   {
      $items['required'] = $G_TEMPLATE['widget']['required'];
   }

   if(! empty($items['sql']))
   {
      $sql = $items['sql'];
   }
   elseif(! empty($items['view']))
   {
      $sql = "SELECT id, value FROM " . $items['view'];
   }
   else
   {
      $valid = false;
   }

   if(! empty($items['filter']))
   {
      $sql .= ' WHERE ' . implode(' AND ', $items['filter']);
   }

   if(! empty($items['orderBy']))
   {
      $sql .= ' ORDER BY ' . $items['orderBy'];
   }

   /**
   * Should be a data array
   */
   if(is_array($items['view']))
   {
      $sqlKey = implode(',',$items['view']);
      $i = 0;
      foreach ($items['view'] as $k=>$v)
      {
         $widgetSelectCache[md5($sqlKey)]['ID'][$i] = $k;
         $widgetSelectCache[md5($sqlKey)]['VALUE'][$i] = $v;
         $i++;
      }
   }
   elseif(empty($items['data']))
   {
      $sqlKey = $DATABASE->Bind($sql, $items['bindVars'], false);
   }

   /**
   * Get the results from the cache
   */
   if(is_array($widgetSelectCache) && array_key_exists(md5($sqlKey),$widgetSelectCache))
   {
      $rows = count($widgetSelectCache[md5($sqlKey)]['VALUE']);
      $selectRows = $widgetSelectCache[md5($sqlKey)];
   }

   /**
   * Run the actual SQL statement
   */
   elseif(empty($items['data']))
   {
      $rows = $DATABASE->Select($sql, $items['bindVars'], $widgetSelectCache[md5($sqlKey)] );
      $selectRows = &$widgetSelectCache[md5($sqlKey)];
   }

   /**
   * Pass in raw data
   */
   elseif(! empty($items['data']) && is_array($items['data']) && ! empty($items['rows']))
   {
      $rows = &$items['rows'];
      $selectRows = &$items['data'];
   }

   if($rows > 0 && $valid)
   {
      if(! $items['passive'])
      {
         $addedGroup   = false;
         $groupByRow   = false;
         $groupByValue = -1;
         if (array_key_exists('GROUPING_VAL', $selectRows))
         {
            $groupByRow = true;
         }

         $max = $rows-1;

         $selected = '';

         if($items['selectOne'] > 0 || (is_array($items['selectOne']) && ! empty($items['selectOne'])) && ! $items['freeflow'])
         {
            $theVal = '';
            $theClk = '';
            $theSel = '';
            $theTxt = $items['selectTxt'];

            if(is_array($items['selectOne']))
            {
               $selOnAttr = $items['selectOne'];

               if(array_key_exists('text',$selOnAttr))
               {
                  $theTxt = $selOnAttr['text'];
               }
               if(array_key_exists('value',$selOnAttr))
               {
                  $theVal = $selOnAttr['value'];
               }
            }

            $find = array( '<!--VALUE-->',
                           '<!--ONCLICK-->',
                           '<!--SELECTED-->',
                           '<!--CONTENT-->',
                           );

            $replace = array( $theVal,
                              $theClk,
                              $theSel,
                              $theTxt,
                              );

            $selectOutput[] = str_replace($find, $replace, $items['template']['initial']);
         }

         $hasASelectedMatch = false;
         $startedGrouping   = false;
         for($i=0; $i<=$max; $i++)
         {
            if(is_array($items['selected']) && count($items['selected']) > 0)
            {
               if(in_array($selectRows['ID'][$i], $items['selected']) || in_array($selectRows['VALUE'][$i], $items['selected']))
               {
                  $hasASelectedMatch = true;
                  $selected = ' selected';
               }
            }

            if(strlen($selectRows['VALUE'][$i]) > $items['strLenBrk'])
            {
               $first = substr(htmlentities($selectRows['VALUE'][$i]), 0, 10);
               $last  = substr(htmlentities($selectRows['VALUE'][$i]), -40);

               $content = $first . '...' . $last;
            }
            else
            {
               $content = htmlentities($selectRows['VALUE'][$i]);
            }

            $find = array( '<!--VALUE-->',
                           '<!--ONCLICK-->',
                           '<!--SELECTED-->',
                           '<!--CONTENT-->',
                           '<!--COUNTER-->',
                           '<!--NAME-->',
                           );

            $replace = array( $selectRows['ID'][$i],
                              $items['onclick'],
                              $selected,
                              $content,
                              $i,
                              $items['name'],
                              );

            if ($groupByRow)
            {
               if ($selectRows['GROUPING_VAL'][$i] !== $groupByValue)
               {
                  if ($addedGroup)
                  {
                     $selectOutput[] = '</optgroup>';
                  }

                  $addedGroup = true;
                  // Start new optgroup
                  $groupByValue = $selectRows['GROUPING_VAL'][$i];

                  $selectOutput[] = '<optgroup label="'.$groupByValue.'">';
                  $selectOutput[] = str_replace($find, $replace, $items['template']['option']);
               }
               else
               {
                  $selectOutput[] = str_replace($find, $replace, $items['template']['option']);
               }
            }
            else
            {
               if ($selectRows['ID'][$i] === 'GROUPING')
               {
                  //Output OPTGROUP tags and labels no one can select this
                  if ($startedGrouping)
                  {
                     $selectOutput[]  = '</optgroup>';
                  }
                  $selectOutput[]  = '<optgroup label="'.$selectRows['VALUE'][$i].'">';
                  $startedGrouping = true;
               }
               else
               {
                  //Output regular option tag
                  $selectOutput[] = str_replace($find, $replace, $items['template']['option']);
               }
            }

            $selected = '';
         }

         if ($groupByRow && $addedGroup)
         {
            $selectOutput[] = '</optgroup>';
         }

         if ($hasASelectedMatch === false && $items['insertUnknown'] === true)
         {
            // -- in this mode, you wish to inject an option into the select box even though the results didnt find the match
            $find = array(
               '<!--VALUE-->',
               '<!--ONCLICK-->',
               '<!--SELECTED-->',
               '<!--CONTENT-->');

            foreach ($items['selected'] as $vals)
            {
               $replace = array(
                  $vals,
                  $items['onclick'],
                  ' selected ',
                  $vals);
               $selectOutput[] = str_replace($find, $replace, $items['template']['option']);
            }
         }
      }
   }
   else
   {
      $find = array('<!--VALUE-->',
                     '<!--ONCLICK-->',
                     '<!--SELECTED-->',
                     '<!--CONTENT-->');

      $replace = array('',
                        $items['onclick'],
                        '',
                        $items['noDataMsg']);

      $selectOutput[] = str_replace($find, $replace, $items['template']['initial']);
   }

   $find = array( '<!--SIZE-->',
                  '<!--ID-->',
                  '<!--NAME-->',
                  '<!--MULTIPLE-->',
                  '<!--OPTIONS-->',
                  '<!--ONCHANGE-->',
                  '<!--REQUIRED-->',
                  '<!--CLASS-->',
                  '<!--DISABLED_FLG-->',
                  '<!--STYLE-->',
                  '<!--ATTRIBUTES-->',
                  '<!--INNER_CLASS-->',
                  '<!--OUTER_CLASS-->',
                  '<!--OUTER_STYLE-->',
                  '<!--INNER_STYLE-->',
                  '<!--OUTER_ACTION-->'
                  );

   if(empty($items['id']) && ! empty($items['name']))
   {
      $items['id'] = $items['name'];
   }

   $replace = array( $items['size'],
                     $items['id'],
                     $items['name'],
                     $items['multiple'],
                     implode('', $selectOutput),
                     $items['onchange'],
                     $items['required'],
                     $items['class'],
                     $items['disabled'],
                     $items['style'],
                     implode(' ', $items['attribs']),
                     $items['inner_class'],
                     $items['outer_class'],
                     $items['outer_style'],
                     '',
                     ''
                     );

   $finalTemplate = $items['template']['wrapper'];

   if($items['passive'] && is_array($items['selected']) && count($items['selected']) > 0)
   {
      $passKeys      = array_keys($selectRows['ID'], $items['selected'][0]);
      $replace[4]    = $selectRows['VALUE'][$passKeys[0]];
      $finalTemplate = $items['template']['passive'];
   }

   return str_replace($find, $replace, $finalTemplate);
}

function WidgetButton($text='', $list=array(), $small=false)
{
   global $G_TEMPLATE;

   $items = array('label'      => $text,
                  'name'       => '',
                  'id'         => '',
                  'url'        => '',
                  'link'       => '', //alias of url
                  'href'       => '', //alias of url
                  'page'       => '',
                  'parameters' => false,
                  'style'      => 'display:inline-block',
                  'frmSubmit'  => '', //this option adds hidden frmbutton
                  'submit'     => '',
                  'args'       => array(),
                  'class'      => 'btn',     //Always stays the same
                  'innerClass' => 'success', //.primary(blue) .info(light-blue) .success(green) .danger(red) .disabled(light grey) .default(grey)
                  'passive'    => false,
                  'function'   => 'ButtonLinkPost',
                  'onclick'    => '',
                  'template'   => '');

   if(is_array($list))
   {
      foreach($list as $key => $value)
      {
         if(array_key_exists($key, $items))
         {
            $items[$key] = $value;
         }
      }
   }

   if(array_key_exists('submit',$items) && ! empty($items['submit']))
   {
      // -- lazy form posting, pass in object reference, name whatever
      $items['onclick'] = "ButtonFormPost('{$list['submit']}');";
   }

   if(empty($items['template']))
   {
      $theClass = '';

      if($small === true)
      {
         $theClass = $items['class'] . " small " . $items['innerClass'];
      }
      elseif(! empty($items['class']))
      {
         $theClass = $items['class'] . " " . $items['innerClass'];
      }

      $items['template'] = $G_TEMPLATE['widget']['button']['default'];
   }
   else
   {
      $theClass = $items['class'];
   }

   if(empty($items['url']) && ! empty($items['page']))
   {
      $items['url'] = BuildUrl($items['page'], $items['args']);
   }
   if((!empty($items['url']) && empty($items['onclick'])) )
   {
      $items['onclick'] = $items['function'] . "('{$items['url']}')";
   }
   if((!empty($items['href']) && empty($items['onclick'])) )
   {
      $items['onclick'] = $items['function'] . "('{$items['href']}')";
   }
   if((!empty($items['link']) && empty($items['onclick'])) )
   {
      $items['onclick'] = $items['function'] . "('{$items['link']}')";
   }
   if(($items['parameters'] && ! empty($items['args'])) )
   {
      $parameters = array();

      foreach($items['args'] as $parameter)
      {
         if ($parameter == 'this' || stristr($parameter,'function') && stristr($parameter,'{') && stristr($parameter,'}'))
         {
            $parameters[] = $parameter;
         }
         else
         {
            $parameters[] = "'".$parameter."'";
         }
      }
      $items['onclick'] = $items['function'] . "(".implode(',', $parameters).")";
   }

   if(!empty($items['frmSubmit']))
   {
      $items['frmSubmit'] = "<input type=\"submit\" value=\"\" style=\"position: absolute; float: left; z-index: -1;\"/> ";
   }

   $pieces = array('<!--BUTTON_CLASS-->',
                   '<!--BUTTON_ONCLICK-->',
                   '<!--BUTTON_LABEL-->',
                   '<!--NAME-->',
                   '<!--ID-->',
                   '<!--BUTTON_STYLE-->',
                   '<!--BUTTON_CLASS_INNER-->',
                   '<!--FRM_SUBMIT-->',
                   );

   $values = array($theClass,
                   str_replace('"',"'",$items['onclick']),
                   $items['label'],
                   $items['name'],
                   $items['id'],
                   $items['style'],
                   $items['innerClass'],
                   $items['frmSubmit']
                   );

   return str_replace($pieces, $values, $items['template']);
}

/**
* WidgetEnableDisableNew() will be deprEcated soon as was used for a few legacy behaviors in campaign_list ON/OFF
*
* @param mixed $list
* @return mixed
*/
function WidgetEnableDisableNew($list=array())
{
   $items = array(
                  'yes_text'          => 'Yes',
                  'no_text'           => 'No',
                  'label'             => '',
                  'tr_class'          => '',
                  'is_enabled'        => false,
                  'yes_disabled'      => false,
                  'no_disabled'       => false,
                  'id'                => '',
                  'disable_js'        => '',
                  'enable_js'         => '',
                  'use_side_link'     => false,
                  'side_link_onclick' => '',
                  'side_link_text'    => '',
                  'slider_options'   => array(), // key is label value is HTML widget
                  );

   if(is_array($list))
   {
      foreach($list as $key => $value)
      {
         if(array_key_exists($key, $items))
         {
            $items[$key] = $value;
         }
      }
   }

   $yesChecked  = '';
   $noChecked   = 'checked="checked"';
   $displayRow1 = 'none';
   $displayOther= 'none';

   if ($items['is_enabled'])
   {
      $yesChecked   = 'checked="checked"';
      $noChecked    = '';
      $displayRow1  = 'table-row';
      $displayOther = 'table-row';
   }

   $idRow1 = '<!--ID-->';

   if ($items['yes_disabled'])
   {
      $yesDisabled  = 'disabled';
   }

   if ($items['no_disabled'])
   {
      $noDisabled  = 'disabled';
   }


   if($items['use_side_link'])
   {
      $items['side_link_template'] = '
               <a class="<!--ID-->" onclick="<!--SIDE_LINK_ONCLICK-->" style="display:'.$displayOther.';position: absolute; margin-right: 52px; margin-left: 530px; font-size: 12px; cursor: pointer; color: blue; margin-top: 67px;"><!--SIDE_LINK_TEXT--></a>';
   }

   $items['slider_option_template'] = '
            <tr class="<!--ID--> '.$items['tr_class'].'" style="display:'.$displayOther.';">
               <td class="label"><!--LABEL--><!--COLON--></td>
               <td>
                  <!--WIDGET_HTML-->
               </td>
            </tr>';

   $additionalRowHTML = '';
   if (!empty($items['slider_options']))
   {
      $slideDown = 'll(\'.<!--ID-->\').fadeIn(\'fast\');';
      $slideUp   = 'll(\'.<!--ID-->\').fadeOut(\'fast\');';
      foreach ($items['slider_options'] as $k=>$v)
      {
         $i++;
         $additionalRowHTML .= Fill(
         array(
            '<!--COLON-->'       =>(!empty($k)) ? ':' : '',
            '<!--ID-->'          => $items['id']. ' '.str_replace(array(' '),array('_'),  (!empty($k)) ? strtolower($k) : strtolower($items['label']) ).'_slider_'.$i ,
            '<!--LABEL-->'       =>$k,
            '<!--WIDGET_HTML-->' =>$v,
         ),$items['slider_option_template']);
      }
   }
   else
   {
      $slideDown = '';
      $slideUp   = '';
   }

   if (empty($items['tr_class']))
   {
      $items['tr_class'] = str_replace(array(' '),array('_'), strtolower($items['label']));
   }

   $items['template'] =
            '
            <!--SIDE_LINK-->
            <tr class="'.$items['tr_class'].'">
               <td class="label"><!--LABEL-->:</td>
               <td>
                  <input class="avsRadioInput" type="radio" name="<!--ID-->" id="<!--ID-->On" value="1"  '.$yesChecked.' '.$yesDisabled.' onchange="HasChanged();'.$slideDown.'<!--ENABLE_JAVASCRIPT-->"/><div class="reg" id="use<!--ID-->Yes"><!--YES_TXT--></div>
                  <input class="avsRadioInput" type="radio" name="<!--ID-->" id="<!--ID-->Off" value="0" '.$noChecked.' '.$noDisabled.' onchange="HasChanged();'.$slideUp.'<!--DISABLE_JAVASCRIPT-->"/><div class="reg" id="use<!--ID-->No"><!--NO_TXT--></div>
               </td>
            </tr>
            <!--ADDITIONAL_ROWS-->
            ';

   $pieces = array(
                   '<!--SIDE_LINK-->',
                   '<!--SIDE_LINK_ONCLICK-->',
                   '<!--SIDE_LINK_TEXT-->',
                   '<!--LABEL-->',
                   '<!--ADDITIONAL_ROWS-->',
                   '<!--DISABLE_JAVASCRIPT-->',
                   '<!--ENABLE_JAVASCRIPT-->',
                   '<!--YES_TXT-->',
                   '<!--NO_TXT-->',
                   '<!--ID-->',
                   );

   $values = array(
                   $items['side_link_template'],
                   $items['side_link_onclick'],
                   $items['side_link_text'],
                   $items['label'],
                   $additionalRowHTML,
                   $items['disable_js'],
                   $items['enable_js'],
                   $items['yes_text'],
                   $items['no_text'],
                   $items['id'],
                   );

   return str_replace($pieces, $values, $items['template']);
}

/**
* WidgetData().  A simple wrapper for WidgetList that provides an overflow handler and also allows you to pass already selected data to the WidgetList class
*
* @param mixed $results
* @param mixed $listConfigs
* @param mixed $templateConfigs (additional WidgetList parameters you want to configure on top of WidgetData items)
* @return mixed
*/
function WidgetData(&$results, $listConfigs=array(), $templateConfigs=array())
{
   global $G_TEMPLATE;
   $onkeyup = '';

   $items = array(
                  'id'                   => 'table_'.rand(0,1000),
                  'use_overflow_wrapper' => false,
                  'wrapper_height'       => '225px',
                  'wrapper_width'        => '485px',
                  'widget_data_length'   => 500000,
                  'table_class'          => 'tableB list tableBlowOutPreventer',
                  'header_style'         => 'background:#ececec',
                 );

   if(is_array($templateConfigs))
   {
      foreach($templateConfigs as $key => $value)
      {
         if(array_key_exists($key, $items))
         {
            $items[$key] = $value;
         }
      }
   }

   foreach( array_keys($results) as $key)
   {
      $configs['columnNoSort'][strtolower($key)] = strtolower($key);
   }

   $configs['template']     = '<!--WRAPPER_TOP--><table class="'.$items['table_class'].'"><tr style="'.$items['header_style'].'"><!--HEADERS--></tr><!--DATA--></table><!--WRAPPER_BOTTOM-->';
   $configs['col']          = '<td style="<!--STYLE-->"><!--CONTENT--></td>';
   $configs['row']          = '<tr class="<!--ROWCLASS-->"><!--CONTENT--></tr>';
   $configs['data']         = $results;
   $configs['strlength']    = $items['widget_data_length'];
   $configs['tableclass']   = 'tableBlowOutPreventer';
   if (!empty($listConfigs))
   {
      foreach ($listConfigs as $k=>$v)
      {
         $configs[$k] = $v;
      }
   }

   $replacements['<!--WRAPPER_TOP-->']    = '';
   $replacements['<!--WRAPPER_BOTTOM-->'] = '';
   //
   if ( $items['use_overflow_wrapper'] )
   {
      $replacements['<!--WRAPPER_TOP-->']    = "<div style=\"width:{$items['wrapper_width']};height:{$items['wrapper_height']};overflow-y:scroll;overflow-x:none;\">";
      $replacements['<!--WRAPPER_BOTTOM-->'] = "</div>";
   }

   $theList = new WidgetList($items['id'], $configs);

   return Fill($replacements, $theList->Render());
}

/**
* WidgetInput
*
* @todo pre/post text
* @todo type hidden
* @param mixed $list
* @return mixed
*/
function WidgetInput($list = array())
{
   global $G_TEMPLATE;

   $items = array('name'         => '',
                  'id'           => '',
                  'outer_id'     => '',
                  'value'        => '',
                  'input_type'   => 'text', //hidden
                  'width'        => 150,
                  'readonly'     => false,
                  'disabled'     => false,
                  'hidden'       => false,
                  'required'     => false,
                  'list-search'  => false,
                  'max_length'   => '',
                  'events'       => array(),
                  'title'        => '',
                  'add_class'    => '',
                  'class'        => 'inputOuter',
                  'inner_class'  => 'inputInner',
                  'outer_onclick'=> '',
                  'style'        => '',
                  'inner_style'  => '',
                  'input_style'  => '',
                  'input_class'  => '',
                  'template'     => '',
                  'search_ahead' => array(),
                  'search_form'  => '',
                  'search_handle'=> '',
                  'arrow_action' => ''
                  );

   //Standard Input
   //
   $items['template_required'] = $G_TEMPLATE['widget']['required'];

   $items['template'] = $G_TEMPLATE['widget']['input']['default'];

   if(is_array($list))
   {
      foreach($list as $key => $value)
      {
         if(array_key_exists($key, $items))
         {
            $items[$key] = $value;
         }
      }
   }

   $iconAction = '';
   $outerAction = '';
   $onkeyup      = '';

   if(! empty($items['outer_onclick']))
   {
      $outerAction = $items['outer_onclick'];
   }

   if(! empty($items['search_ahead']))
   {
      //Search Ahead Input
      //
      $items['template'] = $G_TEMPLATE['widget']['input']['search'];

      $fill = array();
      $fill['<!--MAGNIFIER-->'] = '';
      if($items['list-search'])
      {
         $fill['<!--MAGNIFIER-->'] = '<div class="widget-search-magnifier <!--ICON_EXTRA_CLASS-->" style="" onclick="<!--ICON_ACTION-->"></div>';
      }
      $items['template'] = Fill($fill,$items['template']);

      if(! empty($items['search_ahead']['search_form']))
      {
         if(empty($items['arrow_action']))
         {
            $items['arrow_action'] = "ToggleAdvancedSearch(this)";
         }
      }

      if(array_key_exists('icon_action', $items['search_ahead']))
      {
         $iconAction = $items['search_ahead']['icon_action'];
      }

      if (array_key_exists('events',$items) && array_key_exists('onkeyup',$items['events']))
      {
         $keyUp = $items['events']['onkeyup'].';';
      }
      else
      {
         $keyUp = '';
      }

      if(array_key_exists('onkeyup', $items['search_ahead']))
      {
         $items['events']['onkeyup'] = $keyUp.$items['search_ahead']['onkeyup'];
      }
      else
      {
         if($items['list-search'])
         {
            $items['events']['onkeyup'] = $keyUp."SearchWidgetList('{$items['search_ahead']['url']}', '{$items['search_ahead']['target']}', this);";
         }
         else if(array_key_exists('skip_queue', $items['search_ahead']) && ! empty($items['search_ahead']['skip_queue']))
         {
            $items['events']['onkeyup'] = $keyUp."WidgetInputSearchAhead('{$items['search_ahead']['url']}', '{$items['search_ahead']['target']}', this);";
         }
         else
         {
            $items['events']['onkeyup'] = $keyUp."WidgetInputSearchAheadQueue('{$items['search_ahead']['url']}', '{$items['search_ahead']['target']}', this);";
         }
      }

      if(! empty($items['events']['onkeyup']))
      {
         $iconAction = $items['events']['onkeyup'];
      }

      if(array_key_exists('onclick', $items['search_ahead']))
      {
         $items['events']['onclick'] = $items['search_ahead']['onclick'];
      }

      $items['input_class'] .= ' search-ahead';

      //Modify the width a bit to compensate for the search icon
      //
      $items['width'] = (intval($items['width']) - 30);

      //Build advanced searching
      //
      if(! empty($items['search_ahead']['search_form']))
      {
         if($items['list-search'])
         {
            $items['arrow_extra_class'] .= ' widget-search-arrow-advanced';
         }
         else
         {
            $items['arrow_extra_class'] .= ' widget-search-arrow-advanced-no-search';
         }

         $items['icon_extra_class']  .= ' widget-search-magnifier-advanced';
         $items['input_class'] .= ' search-ahead-advanced';
      }
   }

   //Apply pixel width
   //
   if(! empty($items['width']))
   {
      $items['width'] = intval($items['width']);
   }

   /**
   * Mandatory For outer boundary and IE7
   *
   * @todo should be css MCFW 5/2012
   */
   $items['style'] .= "width:{$items['width']}px";

   if(empty($items['required']))
   {
      $items['template_required'] = '';
   }

   if(! empty($items['disabled']))
   {
      $items['input_class'] .= ' disabled';
   }

   if(! empty($items['add_class']))
   {
      $items['class'] .= ' ' . $items['add_class'];
   }

   if(! empty($items['hidden']))
   {
      $items['style'] .= ' display:none';
   }

   if(! empty($items['events']) && is_array($items['events']))
   {
      $items['event_attributes'] = '';
      foreach ($items['events'] as $event=>$action)
      {
         $items['event_attributes'] .= ' '.$event.'="'.$action.'"'.' ';
      }
   }

   if (!array_key_exists('search_form',$items['search_ahead']))
   {
      $items['search_ahead']['search_form'] = '';
   }

   $pieces = array();
   $pieces[] = '<!--READONLY-->';
   $pieces[] = '<!--OUTER_ID-->';
   $pieces[] = '<!--OUTER_CLASS-->';
   $pieces[] = '<!--OUTER_STYLE-->';
   $pieces[] = '<!--INNER_CLASS-->';
   $pieces[] = '<!--INNER_STYLE-->';
   $pieces[] = '<!--INPUT_CLASS-->';
   $pieces[] = '<!--INPUT_STYLE-->';
   $pieces[] = '<!--ID-->';
   $pieces[] = '<!--NAME-->';
   $pieces[] = '<!--TITLE-->';
   $pieces[] = '<!--MAX_LENGTH-->';
   $pieces[] = '<!--ONKEYUP-->';
   $pieces[] = '<!--SEARCH_FORM-->';
   $pieces[] = '<!--VALUE-->';
   $pieces[] = '<!--REQUIRED-->';
   $pieces[] = '<!--ICON_ACTION-->';
   $pieces[] = '<!--OUTER_ACTION-->';
   $pieces[] = '<!--EVENT_ATTRIBUTES-->';
   $pieces[] = '<!--INPUT_TYPE-->';
   $pieces[] = '<!--ARROW_EXTRA_CLASS-->';
   $pieces[] = '<!--ARROW_ACTION-->';
   $pieces[] = '<!--ICON_EXTRA_CLASS-->';

   $values = array();
   $values[] = ($items['readonly']) ? 'readonly' : '';
   $values[] = $items['outer_id'];
   $values[] = $items['class'];
   $values[] = $items['style'];
   $values[] = $items['inner_class'];
   $values[] = $items['inner_style'];
   $values[] = $items['input_class'];
   $values[] = $items['input_style'];
   $values[] = $items['id'];
   $values[] = $items['name'];
   $values[] = $items['title'];
   $values[] = $items['max_length'];
   $values[] = $onkeyup;
   $values[] = $items['search_ahead']['search_form'];
   $values[] = EscapeIntoEntities($items['value']);
   $values[] = $items['template_required'];
   $values[] = $iconAction;
   $values[] = $outerAction;
   $values[] = $items['event_attributes'];
   $values[] = $items['input_type'];
   $values[] = $items['arrow_extra_class'];
   $values[] = $items['arrow_action'];
   $values[] = $items['icon_extra_class'];

   return str_replace($pieces, $values, $items['template']);
}

/**
* WidgetRadio
*
* @todo pre/post text
* @param mixed $list
* @return mixed
*/
function WidgetRadio($list = array())
{
   global $G_TEMPLATE;

   $items = array('name'         => '',
                  'id'           => '',
                  'value'        => '',
                  'width'        => '',
                  'disabled'     => false,
                  'hidden'       => false,
                  'required'     => false,
                  'checked'      => '',
                  'max_length'   => '',
                  'title'        => '',
                  'class'        => '',
                  'style'        => '',
                  'onclick'      => '',
                  'input_style'  => '',
                  'input_class'  => '',
                  'template'     => '',
                  );

   $items['template_required'] = $G_TEMPLATE['widget']['required'];

   $items['template'] = $G_TEMPLATE['widget']['radio']['default'];

   if(is_array($list))
   {
      foreach($list as $key => $value)
      {
         if(array_key_exists($key, $items))
         {
            $items[$key] = $value;
         }
      }
   }

   if(empty($items['required']))
   {
      $items['template_required'] = '';
   }

   if(! empty($items['checked']))
   {
      $items['checked'] .= 'checked';
   }

   if(! empty($items['disabled']))
   {
      $items['input_class'] .= ' disabled';
   }

   if(! empty($items['hidden']))
   {
      $items['style'] .= ' display:none';
   }

   $pieces = array();
   $pieces[] = '<!--INPUT_CLASS-->';
   $pieces[] = '<!--INPUT_STYLE-->';
   $pieces[] = '<!--ID-->';
   $pieces[] = '<!--NAME-->';
   $pieces[] = '<!--TITLE-->';
   $pieces[] = '<!--ONCLICK-->';
   $pieces[] = '<!--VALUE-->';
   $pieces[] = '<!--REQUIRED-->';
   $pieces[] = '<!--CHECKED-->';

   $values = array();
   $values[] = $items['input_class'];
   $values[] = $items['input_style'];
   $values[] = $items['id'];
   $values[] = $items['name'];
   $values[] = htmlentities($items['title']);
   $values[] = $items['onclick'];
   $values[] = htmlentities($items['value']);
   $values[] = $items['template_required'];
   $values[] = $items['checked'];

   return str_replace($pieces, $values, $items['template']);
}

/**
* WidgetCheck
*
* @todo pre/post text
* @param mixed $list
* @return mixed
*/
function WidgetCheck($list = array())
{
   global $G_TEMPLATE;

   $items = array('name'         => '',
                  'id'           => '',
                  'value'        => '',
                  'width'        => '',
                  'disabled'     => false,
                  'hidden'       => false,
                  'required'     => false,
                  'checked'      => '',
                  'max_length'   => '',
                  'title'        => '',
                  'class'        => '',
                  'style'        => '',
                  'onclick'      => '',
                  'input_style'  => '',
                  'input_class'  => '',
                  'template'     => '',
                  );

   $items['template_required'] = $G_TEMPLATE['widget']['required'];

   $items['template'] = $G_TEMPLATE['widget']['checkbox']['default'];

   if(is_array($list))
   {
      foreach($list as $key => $value)
      {
         if(array_key_exists($key, $items))
         {
            $items[$key] = $value;
         }
      }
   }

   if(empty($items['required']))
   {
      $items['template_required'] = '';
   }

   if(! empty($items['checked']))
   {
      $items['checked'] = 'checked';
   }

   if(! empty($items['class']))
   {
      $items['input_class'] = $items['class'];
   }

   if(! empty($items['disabled']))
   {
      $items['input_class'] .= ' disabled';
   }

   if(! empty($items['hidden']))
   {
      $items['style'] .= ' display:none';
   }

   $pieces = array();
   $pieces[] = '<!--INPUT_CLASS-->';
   $pieces[] = '<!--INPUT_STYLE-->';
   $pieces[] = '<!--ID-->';
   $pieces[] = '<!--NAME-->';
   $pieces[] = '<!--TITLE-->';
   $pieces[] = '<!--ONCLICK-->';
   $pieces[] = '<!--VALUE-->';
   $pieces[] = '<!--REQUIRED-->';
   $pieces[] = '<!--CHECKED-->';
   $pieces[] = '<!--VALUE-->';

   $values = array();
   $values[] = $items['input_class'];
   $values[] = $items['input_style'];
   $values[] = $items['id'];
   $values[] = $items['name'];
   $values[] = htmlentities($items['title']);
   $values[] = $items['onclick'];
   $values[] = htmlentities($items['value']);
   $values[] = $items['template_required'];
   $values[] = $items['checked'];
   $values[] = $items['value'];

   return str_replace($pieces, $values, $items['template']);
}

/**
* WidgetEnableDisable() will be deprEcated soon as was used for a few legacy behaviors in campaign_list ON/OFF
*
* @param mixed $list
* @return mixed
*/
function WidgetEnableDisable($list=array())
{
   global $G_TEMPLATE;

   $items = array('yes_text'          => 'Yes',
                  'no_text'           => 'No',
                  'name'              => '',
                  'is_enabled'        => false,
                  'yes_disabled'      => false,
                  'no_disabled'       => false,
                  'enable_text'       => 'Enable',
                  'id'                => '',
                  'custom_row1_html'  => false,
                  'always_show_row1'  => false,
                  'row1_widget'       => '',
                  'disable_js'        => '',
                  'enable_js'         => '',
                  'always_show_row1'  => false,
                  'use_line_top'      => false,
                  'use_line_bottom'   => true,
                  'use_side_link'     => false,
                  'side_link_onclick' => '',
                  'side_link_text'    => '',
                  'additional_rows'   => array(), // key is label value is HTML widget
                  );

   if(is_array($list))
   {
      foreach($list as $key => $value)
      {
         if(array_key_exists($key, $items))
         {
            $items[$key] = $value;
         }
      }
   }

   $yesChecked  = '';
   $noChecked   = 'checked="checked"';
   $displayRow1 = 'none';
   $displayOther= 'none';

   if ($items['is_enabled'])
   {
      $yesChecked   = 'checked="checked"';
      $noChecked    = '';
      $displayRow1  = 'block';
      $displayOther = 'block';
   }

   $idRow1 = '<!--ID-->';
   if ($items['always_show_row1'])
   {
      $idRow1       = ''; //no ID/class for main row 1 since you want to keep it always open so if the user toggles, it will stay there (see gateway credit card implementation)
      $displayRow1  = 'block';
   }

   if ($items['yes_disabled'])
   {
      $yesDisabled  = 'disabled';
   }

   if ($items['no_disabled'])
   {
      $noDisabled  = 'disabled';
   }


   if($items['use_side_link'])
   {
      $items['side_link_template'] = '
               <a class="<!--ID-->" onclick="<!--SIDE_LINK_ONCLICK-->" style="display:'.$displayOther.';position: absolute; margin-right: 52px; margin-left: 530px; font-size: 12px; cursor: pointer; color: blue; margin-top: 67px;"><!--SIDE_LINK_TEXT--></a>';
   }

   $items['additional_row_templates'] = '
            <div class="<!--ID-->" style="display:'.$displayOther.';width:660px;">
               <div class="hrline"></div><p><label><!--LABEL-->:</label><!--WIDGET_HTML--></p>
            </div>';

   if(!$items['custom_row1_html'])
   {
      //Used by default to auto generate your first row based on the name and the option, but you can also pass custom_row1_html=true to just pass a bunch of HTML garbage of your chosing
      //
      $items['label_widget_template'] = '
               <div class="hrline"></div><p><label><!--NAME-->:</label><!--ROW1_WIDGET--></p>
               ';
   }
   else
   {
      $items['label_widget_template'] = $items['row1_widget'];
   }

   $additionalRowHTML = '';
   if (!empty($items['additional_rows']))
   {
      foreach ($items['additional_rows'] as $k=>$v)
      {
         $additionalRowHTML .= Fill(
         array(
            '<!--ID-->'          =>$items['id'],
            '<!--LABEL-->'       =>$k,
            '<!--WIDGET_HTML-->' =>$v,
         ),$items['additional_row_templates']);
      }
   }

   $items['template'] =
            '
            <!--LINE_TOP-->
            <!--SIDE_LINK-->
            <div class="formBoxRow">
               <label><!--ENABLE_TEXT--> <!--NAME-->:</label>
               <span class="avsDiv" style="width:415px;">
                  <input class="avsRadioInput" type="radio" name="use<!--ID-->" id="use<!--ID-->On" value="1"  '.$yesChecked.' '.$yesDisabled.' onchange="ll(\'.<!--ID-->\').slideDown();<!--ENABLE_JAVASCRIPT-->"/><div class="reg" id="use<!--ID-->Yes"><!--YES_TXT--></div>
                  <input class="avsRadioInput" type="radio" name="use<!--ID-->" id="use<!--ID-->Off" value="0" '.$noChecked.' '.$noDisabled.' onchange="ll(\'.<!--ID-->\').slideUp();<!--DISABLE_JAVASCRIPT-->"/><div class="reg" id="use<!--ID-->No"><!--NO_TXT--></div>
               </span>
            </div>
            <div class="'.$idRow1.'" style="display:'.$displayRow1.';width:660px;">
               <!--ROW1-->
            </div>
            <!--ADDITIONAL_ROWS-->
            <!--LINE_BOTTOM-->
               ';
   $lineTopHTML = '';
   if ($items['use_line_top'])
   {
      $lineTopHTML = '<div class="hrline"></div>';
   }
   $lineBottomHTML = '';
   if ($items['use_line_bottom'])
   {
      $lineBottomHTML = '<div class="hrline"></div>';
   }

   $pieces = array(
                   '<!--SIDE_LINK-->',
                   '<!--SIDE_LINK_ONCLICK-->',
                   '<!--SIDE_LINK_TEXT-->',
                   '<!--ID-->',
                   '<!--ROW1-->',
                   '<!--NAME-->',
                   '<!--ROW1_WIDGET-->',
                   '<!--ADDITIONAL_ROWS-->',
                   '<!--ENABLE_TEXT-->',
                   '<!--DISABLE_JAVASCRIPT-->',
                   '<!--ENABLE_JAVASCRIPT-->',
                   '<!--LINE_TOP-->',
                   '<!--LINE_BOTTOM-->',
                   '<!--YES_TXT-->',
                   '<!--NO_TXT-->',
                   );

   $values = array(
                   $items['side_link_template'],
                   $items['side_link_onclick'],
                   $items['side_link_text'],
                   $items['id'],
                   $items['label_widget_template'],
                   $items['name'],
                   $items['row1_widget'],
                   $additionalRowHTML,
                   $items['enable_text'],
                   $items['disable_js'],
                   $items['enable_js'],
                   $lineTopHTML,
                   $lineBottomHTML,
                   $items['yes_text'],
                   $items['no_text'],
                   );

   return str_replace($pieces, $values, $items['template']);
}

/**
* WidgetFieldSet
*
* @deprecated
*
* @param mixed $list
* @return mixed
*
* @todo SQL drive with attributes (serialized)
* @todo left outer join to a config table to eliminate php configs
*/
function WidgetFieldSet($list = array())
{
   global $G_TEMPLATE;
   global $DATABASE;

   /**
   * Global
   */
   $items['fields']    = array();
   $items['rows']      = array();
   $items['sql']       = '';
   $items['data_rows'] = 0;
   $items['data']      = array();

   /**
   * Template
   */
   $items['tpl']['col']['id'] = '';
   $items['tpl']['row']['id'] = '';
   $items['tpl']['wrapper']['id']    = '';
   $items['tpl']['wrapper']['class'] = '';

   $items = array_merge($items,$list);

   //$items['checkbox'][''] = '';
   //$items['select']['']   = '';

   /**
   * Text
   */
   $items['text']['outer_id']    = '';
   $items['text']['outer_class'] = 'summary';

   /**
   * Get Data
   */
   if(empty($items['data']) && ! empty($items['sql']))
   {
      $items['data_rows'] = $DATABASE->Select($items['sql']);

      if($items['data_rows'])
      {
         $items['data'] = $DATABASE->results;
      }
   }

   foreach($items['rows'] as $rowKey => $columns)
   {
      $columnsTpl = array();

      foreach($columns as $field)
      {
         $attribs  = $items['fields'][$field];
         $template = $G_TEMPLATE['widget']['container']['col']['pre_text'];

         /**
         * Value
         */
         if(! empty($items['data']) && array_key_exists(strtoupper($field), $items['data']) && (! array_key_exists('value', $attribs['input']) || empty($attribs['input']['value'])))
         {
            $attribs['input']['value'] = $items['data'][strtoupper($field)][0];
         }

         /**
         * Template
         */
         if(array_key_exists('tpl', $attribs) && array_key_exists('col', $attribs['tpl']) && ! empty($attribs['tpl']['col']))
         {
            $template = $attribs['tpl']['col'];
         }

         switch($attribs['type'])
         {
            case 'checkbox':
               $columnsTpl[] = WidgetCheck($input);
            break;
            case 'text':

               $pieces = array();
               $pieces['<!--ID-->']       = $attribs['id'];
               $pieces['<!--CLASS-->']    = $attribs['class'];
               $pieces['<!--COL_SPAN-->'] = $attribs['col_span'];
               $pieces['<!--PRE_TEXT-->'] = $attribs['pre_text'];
               $pieces['<!--CONTENT-->']  = WidgetInput($attribs['input']);

               $columnsTpl[] = str_replace(array_keys($pieces), array_values($pieces), $template);

            break;
            case 'select':

               $pieces = array();
               $pieces['<!--ID-->']       = $attribs['id'];
               $pieces['<!--CLASS-->']    = $attribs['class'];
               $pieces['<!--COL_SPAN-->'] = $attribs['col_span'];
               $pieces['<!--PRE_TEXT-->'] = $attribs['pre_text'];
               $pieces['<!--CONTENT-->']  = WidgetSelect($attribs['input']);

               $columnsTpl[] = str_replace(array_keys($pieces), array_values($pieces), $template);

            break;
            case 'content':

               $pieces = array();
               $pieces['<!--ID-->']       = $attribs['id'];
               $pieces['<!--CLASS-->']    = $attribs['class'];
               $pieces['<!--COL_SPAN-->'] = $attribs['col_span'];
               $pieces['<!--PRE_TEXT-->'] = $attribs['pre_text'];
               $pieces['<!--CONTENT-->']  = $attribs['content'];

               $columnsTpl[] = str_replace(array_keys($pieces), array_values($pieces), $template);

            break;
         }
      }

      $rows[] = str_replace('<!--CONTENT-->', implode('', $columnsTpl), $G_TEMPLATE['widget']['container']['row']);
   }

   $pieces = array();
   $pieces[] = '<!--CONTENT-->';
   $pieces[] = '<!--OUTER_CLASS-->';
   $pieces[] = '<!--OUTER_ID-->';
   $pieces[] = '<!--FORM_ID-->';

   $values = array();
   $values[] = implode('',$rows);
   $values[] = $items['tpl']['wrapper']['class'];
   $values[] = $items['tpl']['wrapper']['id'];
   $values[] = $items['tpl']['wrapper']['form_id'];

   return str_replace($pieces, $values, $G_TEMPLATE['widget']['container']['wrapper']);
}
?>