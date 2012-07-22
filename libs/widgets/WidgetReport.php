<?php

class WidgetReport extends WidgetList
{
   var $rawTotals = array();

   function __construct($name = '', $list = '')
   {
      /**
      * Base constructor
      */
      parent::__construct($name, $list);

      /**
      * Report overrides
      */
      $this->items['template'] = '
      <!--HEADER--> <div id="report_custom_content"><!--CUSTOM_CONTENT--></div>
      <div style="visibility:hidden;height:0;" class="clear"></div>
      <div class="<!--CLASS-->" id="<!--NAME-->">
         <table class="list <!--TABLE_CLASS-->" style="<!--INLINE_STYLE-->" border="0" width="100%" cellpadding="0" cellspacing="0">
            <!--LIST_TITLE-->
            <tr class="list_header"><!--HEADERS--></tr>
               <!--DATA-->
         </table>
         <div class="pagination" style="text-align:left;"><div style="margin:auto;float:left;margin:0px;padding:0px;"><!--PAGINATION_LIST--></div></div>
         <input type="hidden" name="<!--JUMP_URL_NAME-->" id="<!--JUMP_URL_NAME-->" value="<!--JUMP_URL-->">
      </div>';

      /**
      * Report specific items
      */
      $reportItems = array(
         /**
         * Total row
         */
         'totalRow'         => array(),
         'totalRowFirstCol' => '<b>Total:</b>',
         'totalRowMethod'   => array()
      );

      if(is_array($list))
      {
         foreach($reportItems as $key => $value)
         {
            if(array_key_exists($key, $list))
            {
               $this->items[$key] = $list[$key];
            }
            else
            {
               $this->items[$key] = $value;
            }
         }
      }
   }

   function BuildRows()
   {
      parent::BuildRows();

      if($this->totalRowCount > 0)
      {
         /**
         * Add Total Row
         */
         if (! empty($this->items['totalRow']))
         {
            $columns  = array();
            $firstCol = true;

            foreach($this->items['fields'] as $column => $fieldTitle)
            {
               if ($firstCol)
               {
                  $firstCol = false;
                  $content  = $this->items['totalRowFirstCol'];
                  $this->rawTotals[$column] = strip_tags($content);
               }
               else
               {
                  if (in_array($column, $this->items['totalRow']) || array_key_exists($column, $this->items['totalRow']))
                  {
                     $content = 0;
                     $prefix  = '';
                     $suffix  = '';
                     $maxPrec = 0;

                     if (array_key_exists($column, $this->items['totalRow']))
                     {
                        $column = $this->items['totalRow'][$column];
                     }

                     foreach ($this->results[strtoupper($column)] as $val)
                     {
                        $rawVal = preg_replace('~[^0-9\.]~', '', $val);

                        if (preg_match('~\.([0-9]+)~', $rawVal, $matches))
                        {
                           $maxPrec = max(array(strlen($matches[1]), $maxPrec));
                        }

                        $content += $rawVal;

                        if (preg_match('~^([^0-9]+)~', $val, $matches))
                        {
                           $prefix = $matches[1];
                        }
                        else if (preg_match('~([^0-9]+)$~', $val, $matches))
                        {
                           $suffix = $matches[1];
                        }
                     }

                     if (array_key_exists($column, $this->items['totalRowMethod']))
                     {
                        switch ($this->items['totalRowMethod'][$column])
                        {
                           case 'average' :
                              $content = round($content / $this->totalRowCount, $maxPrec);
                           break;
                           default :
                           break;
                        }
                     }

                     if (! empty($maxPrec))
                     {
                        $content = number_format($content, $maxPrec);
                     }

                     $this->rawTotals[$column] = $content;

                     $content = "{$prefix}{$content}{$suffix}";
                  }
                  else
                  {
                     $content = '';
                     $this->rawTotals[$column] = '';
                  }
               }

               /**
               * Set up Column Pieces
               */
               $colPieces['<!--CLASS-->']   = $colClass;
               $colPieces['<!--ALIGN-->']   = $this->items['collAlign'];
               $colPieces['<!--STYLE-->']   = $theStyle . $colWidthStyle;
               $colPieces['<!--ONCLICK-->'] = $onClick;
               $colPieces['<!--TITLE-->']   = $fieldTitle;
               $colPieces['<!--CONTENT-->'] = $content;

               /**
               * Assemble the Column
               */
               $columns[] = Fill($colPieces, $this->items['col']);
            }

            /**
            * Draw the row
            */
            $pieces = array(
               '<!--CONTENT-->'  => implode('', $columns),
               '<!--BGCOLOR-->'  => $this->items['rowOffsets'][$this->totalRowCount % 2],
               '<!--ROWSTYLE-->' => ''
            );

            /**
            * Add to $rows
            */
            $this->templateFill['<!--DATA-->'] .= Fill($pieces, $this->items['row']);
         }
      }
   }
}
?>
