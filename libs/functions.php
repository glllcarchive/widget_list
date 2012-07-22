<?php


function JsonEncode($array, $return=false)
{
   foreach ($array as $k=>$v)
   {
      $array[$k] = utf8_encode($v);
   }

   if($return)
   {
      return json_encode($array);
   }
   else
   {
      echo json_encode($array);
   }
}

/**
* Build a query string
*
* @param array $args
* @return string
*/
function BuildQueryString($args)
{
   $query = array();

   foreach($args as $key => $value)
   {
      $query[] = $key . '=' . urlencode(urldecode($value));
   }

   return implode('&', $query);
}

/**
 * Build a URL
 * @param string $page
 * @param mixed $args
 * @return string url
 */
function BuildUrl($page='', $args = array(),$appendGet=false)
{
   $queryString = BuildQueryString($args);
   $getvars        = '';
   if ($appendGet)
   {
      $data = (strtolower($appendGet)=='post') ?  $_POST : $_GET;
      foreach ($data as $k=>$v)
      {
         if (!is_array($v))
         {
            $getvars .="&$k=".urlencode($v);
         }
      }
   }

   if(! stristr('?',$page))
   {
      return "$page?" . $queryString . $getvars;
   }
   else
   {
      return "$page" . $queryString . $getvars;
   }
}


function Fill($tags = '', $template = '')
{
   if(! empty($tags) && is_array($tags))
   {
      return str_replace(array_keys($tags), array_values($tags), $template);
   }

    return $template;
}



/**
* EscapeIntoEntities()
*
* Take any input array and clean/escape every value into its entities (perfect for input type="text" value="Not"This but &#34; this ")
*
* @param array or string $input
* @param array (optional) $keys - if you pass in something like $orders->fields you will only process a white list of keys
*/
function EscapeIntoEntities(&$input,$keys=array())
{
   if (is_array($input) && !empty($input))
   {
      foreach ($input as $k=>$v)
      {
         $process   = (array_search($k,$keys) !== false || array_key_exists($k,$keys) || empty($keys));
         if ($process)
         {
            if (!is_array($v))
            {
               $newVal = (!strstr($v,'&#')) ? htmlentities($v) : $v;
            }
            else
            {
               foreach ($v as $theKey=>$theVal)
               {
                  $newVal[$theKey] = (!strstr($theVal,'&#')) ? htmlentities($theVal) : $theVal;
               }
            }
         }
         else
         {
            $newVal = $v;
         }
         $input[$k] = $newVal;
      }
   }
   elseif (!is_array($input))
   {
      $input = (!strstr($input,'&#')) ? htmlentities($input) : $input;
   }
   return $input;
}

?>