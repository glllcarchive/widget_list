<?php

/**
* sql()
*
* Encapsulation of mysql functions
*
* @since   PHP5
* @author  funtson 10/2010
* @internal bindvariable simulation enables for a mysqli transition or in-house caching mechanism
*/

class sql
{

   public  $isLocalDev;

   /**
   * Cache for all statements run
   *
   * @var array $sqlStack
   */
   public  $sqlStack;

   /**
   * The last statement run
   *
   * @var string $lastSql
   */
   public  $lastSql;

   /**
   * The last statement run with original bindings
   *
   * @var string $lastSqlRaw
   */
   public  $lastSqlRaw;

   /**
   * The last bind vars passed
   *
   * @var array $lastSqlBind
   */
   public  $lastSqlBind;

   /**
   * Holds results from select statement
   *
   * @var array $results
   */
   public  $results;

   /**
   * affected row count
   *
   * @var int $count
   */
   public  $count;

   /**
   * error flag
   *
   * @var bool $error
   */
   public  $error;

   /**
   * transcation error flag
   *
   * @var bool $transError
   */
   public  $transError;

   /**
   * erorr message
   *
   * @var string $errorMsg
   */
   public  $errorMsg;

   /**
   * last auto id of the last insert
   *
   * @var int $lastInsertId
   */
   public  $lastInsertId;

   /**
   * elasped query time
   *
   * @var int $elapsed
   */
   public  $elapsed;

   /**
   * mysql connection handler
   *
   * @var resource $conn
   */
   public $conn;

   /**
   * transaction flag
   *
   * @var bool $transaction
   */
   private $transaction;

   /**
   * how you want your results formatted
   * column-row = $row['field'][]
   * row-column = $row[]['field']
   * @var string $resultMode
   */
   public $resultMode;

   /**
   * noformatting
   * mixed
   * upper
   * lower
   * @var string $caseManipulation
   */
   public $caseManipulation;

   public $useSqlStack;

   public $clearResultSet;

   public $lightMode;

   public function __construct()
   {
      global $G_DEBUG;

      $this->isLocalDev       = (PHP_OS == "WINNT");
      $this->sqlStack         = array();
      $this->results          = array();
      $this->lastSql          = '';
      $this->lastSqlRaw       = '';
      $this->lastSqlBind      = array();
      $this->errorMsg         = '';
      $this->count            = 0;
      $this->elapsed          = 0;
      $this->error            = false;
      $this->transError       = false;
      $this->transaction      = false;
      $this->conn             = false;
      $this->resultMode       = '';
      $this->caseManipulation = 'upper';
      $this->useSqlStack      = true;
      $this->clearResultSet   = false;
      $this->lightMode        = true;

      //Always off in product/default
      //
      if(! empty($G_DEBUG))
      {
         $this->lightMode = false;
      }

      $this->CustomObjects();
   }

   /**
   * CustomObjects()
   *
   * Customize things you might need for your usage of this class
   */
   public function CustomObjects()
   {
      //$this->xxx = new xxxx();
   }


   public function Log($string,$force=false)
   {
      global $G_DEBUG;

      if( false || $force)
      {
         error_log($string);
      }
   }

   public function Notify($emailBody)
   {

   }

   public function LogLastSql($function='',$message='')
   {
      if (!empty($function))
      {
         $function .= " - ";
      }

      if (!empty($message))
      {
         $this->Log($message,true);
      }
      $this->Log("{$function}SQL - ".$this->lastSql,true);
      $this->Log("{$function}Rows updated - ".$this->count,true);
   }

   /**
   * Connect()
   *
   * Creates Connection Handler and selects Database
   *
   * @return bool
   */
   public function Connect($alwaysToMaster=false,$newLink=false)
   {
      $mysql_db_user   = DB_SERVER_USERNAME;
      $mysql_db_pass   = DB_SERVER_PASSWORD;
      $this->mysql_db  = DB_DATABASE;
      $mysql_db_server = DB_SERVER;

      if(! empty($mysql_db_server) & ! empty($this->mysql_db) & ! empty($mysql_db_pass) & ! empty($mysql_db_user))
      {
         $this->conn = mysql_connect($mysql_db_server, $mysql_db_user, $mysql_db_pass, $newLink);

         if (! $this->conn && $this->isSlave)
         {
            $mysql_db_server = DB_SERVER;
            $this->conn = mysql_connect($mysql_db_server, $mysql_db_user, $mysql_db_pass, $newLink);
         }

         if(! $this->conn)
         {
            $this->error = $this->Fail();
            throw new Exception('Unable to connect to database');
         }
         else
         {
            if(! $this->SelectDB($this->mysql_db))
            {
               throw new Exception('Unable to connect to database');
            }
            else
            {
               return $this->Success('Sucessfully connected to database: ' . $this->mysql_db);
            }
         }
      }
      else
      {
         throw new Exception('Missing Database Credentials');
      }
   }

   public function GetConnection()
   {
      return $this->conn;
   }

   /**
   * CheckConnection()
   *
   * Revive stale connections
   *
   */
   public function CheckConnection()
   {
      if (!$this->isLocalDev)
      {
         if (! mysql_ping($this->conn) )
         {
            try
            {
               if ($this->isSlave)
               {
                  $this->Connect(2);
               }
               else
               {
                  $this->Connect();
               }
            }
            catch(Exception $e)
            {
               $this->Log(__METHOD__.' could not connect or disconnect ('.$e.') Line: '.__LINE__);
            }
         }
      }
   }

   public function Disconnect()
   {
      if(! $this->conn)
      {
         $this->error = $this->Fail();
      }
      else
      {
         mysql_close($this->conn);
      }
   }

   /**
   * StartTransaction()
   *
   * Initiates transaction
   *
   * @return bool
   */
   public function StartTransaction()
   {
      $this->Log('START TRANSACTION');
      if(mysql_query('START TRANSACTION', $this->conn))
      {
          $this->transaction = true;

          return $this->Success('Successfully started transaction');
      }
      else
      {
          return $this->Fail('Failed starting transaction');
      }
   }

   private function RegisterStatistics(&$query)
   {
      if(! $this->lightMode || $this->transaction)
      {
         $this->sqlStack[$query] = 0;
         $this->sqlStack[$query] = $this->elapsed;
      }
   }

   /**
   * EndTransaction()
   *
   * Ends transaction
   *
   * @return void
   */
   public function EndTransaction()
   {
      if($this->transError)
      {
         $this->transError = false;
         if($this->Rollback())
         {
            $this->transaction = false;
            $this->Notify('Transaction Rolled Back because of error<br /><br />'.implode('<br /><br />',array_keys($this->sqlStack)));
            $this->Success('Successfully ended transaction via ROLLBACK');
         }
      }
      else if($this->Commit())
      {
         $this->transaction = false;
         $this->Success('Successfully ended transaction via COMMIT');
      }
      else
      {
         $this->Fail('EndTransaction Failed');
      }
   }

   /**
   * SelectDB()
   *
   * Selects current transaction database
   *
   * @param string $db
   * @return bool
   */
   public function SelectDB($db = '')
   {
      $dbSel = false;
      if (empty($db))
      {
         $db = $this->mysql_db;
      }
      if(! empty($db))
      {
          $dbSel = mysql_select_db($db, $this->conn);
      }

      return $dbSel;
   }

   /**
   * ValidCredentials()
   *
   * Add any extra validation to the bindvars and query here
   *
   * @param string $query
   * @param array $bindVars
   * @return bool
   */
   private function ValidCredentials(&$query, &$bindVars)
   {
      $valid = false;

      if(! empty($query) && is_array($bindVars))
      {
          $valid = true;
      }

      return $valid;
   }

   /**
   * Select()
   *
   * Accepts a query and simulated bind variable(s) and performs a select
   *
   * @param string $query
   * @param mixed $bindVars
   * @param mixed $resultsHolder -
   * @return integer
   */
   public function Select($query = '', $bindVars = array(), &$resultsHolder = array(), $rowProcessor = '')
   {
      $this->error = false;

      $this->CheckConnection();

      $this->count = 0;

      if($this->ValidCredentials($query, $bindVars))
      {
         $this->Bind($query, $bindVars);

         $this->elapsed = $this->GetTime();

         $this->Log($query);

         $result = mysql_query($query, $this->conn);

         $this->elapsed -= $this->GetTime();

         $this->RegisterStatistics($query);

         if ($result === false)
         {
            $this->count = $this->Fail();
         }
         else
         {
            $this->count = mysql_num_rows($result);

            if($this->count > 0)
            {
               $this->results = array();

               if($this->count > 1)
               {
                  $i=0;
                  while ($row = mysql_fetch_assoc($result))
                  {
                     foreach($row as $columnName => $columnValue)
                     {
                        $this->buildResultRow($i,$columnName,$columnValue);
                     }
                     if (!empty($rowProcessor) && function_exists($rowProcessor))
                     {
                        $rowProcessor($row);
                     }
                     $i++;
                  }
               }
               else
               {
                  $temp = mysql_fetch_assoc($result);
                  foreach($temp as $columnName => $columnValue)
                  {
                     $rowNum = 0;
                     $this->buildResultRow($rowNum,$columnName,$columnValue);
                  }
                  if (!empty($rowProcessor) && function_exists($rowProcessor))
                  {
                     $rowProcessor($temp);
                  }
               }

               $resultsHolder = $this->results;
            }

            mysql_free_result($result);
         }

         $this->ExplainQueries($query,'.'.__CLASS__,$this->elapsed,$this->conn);

         return $this->count;
      }
   }

   public static function ExplainQueries($query,$type='.sql',$elapsedTime='',$link='',$explain=true)
   {
      if (PHP_OS == "WINNT")
      {
         if (stristr($query,'SET '))
         {
            $explain = false;
         }
         $rules = array();
         $rules['skeptical_indexes'] = array('idx_deleted','idx_was_reprocessed','idx_non_composite_common_ancestor','idx_non_composite_t_stamp','idx_non_composite_hold_date','idx_non_composite_order_coonfirmed','idx_non_composite_campaign_order_id','idx_non_composite_customers_id','idx_non_composite_cust_status','idx_non_composite_tracking_num','idx_non_composite_idx_rebill_depth','idx_non_composite_idx_orders_cust_email');
         $rules['slow_threshold']    = ' > 1';
         $rules['speed']    = array(
            'system'         => 0,
            'const'          => 1,
            'eq_ref'         => 2,
            'ref'            => 3,
            'fulltext'       => 4,
            'ref_or_null'    => 5,
            'index_merge'    => 6,
            'unique_subquery'=> 7,
            'index_subquery' => 8,
            'range'          => 9,
            'index'          => 10,
         );

         $elapsedTime = abs($elapsedTime);

         fileLogger::debug("\n\n\n======================================QUERY ($elapsedTime)======================================\n\n\n",null,null,false,$type);
         fileLogger::debug($query."\n\n",null,null,false,$type);

         if ($explain)
         {
            $result = (is_resource($link)) ? mysql_query("EXPLAIN ".$query,$link) : mysql_query("EXPLAIN ".$query);
            if ($result !== false)
            {
               $sizes = array();
               $rows  = array();
               while ($row = mysql_fetch_assoc($result))
               {
                  if (is_array($row))
                  {
                     foreach ($row as $k=>$v)
                     {
                        if (!array_key_exists($k,$sizes) || $sizes[$k] < strlen($v))
                        {
                           $sizes[$k] = strlen($v);
                        }
                     }
                     $rows[] = $row;
                  }
               }

               foreach ($rows as $k=>$row)
               {
                  if ($k == 0)
                  {
                     foreach ($sizes as $col=>$size)
                     {
                        $out[] = str_pad($col,$size+10,' ',STR_PAD_RIGHT);
                     }
                     $out[] = "\n";
                     $out[] = str_pad("",array_sum($sizes)+100,'-')."\n";
                  }

                  $hitSkeptic       = false;
                  $hitIndex         = false;
                  $indexes          = array();
                  $indexType       = '';
                  $totalApproxScans = 0;

                  foreach ($row as $column=>$value)
                  {
                     if ($column == 'key' && (in_array(trim($value),$rules['skeptical_indexes'])))
                     {
                        $hitSkeptic = true;
                     }

                     if ($column == 'key')
                     {
                        $indexName = $value;
                     }

                     if ($column == 'rows')
                     {
                        if ($totalApproxScans == 0)
                        {
                           $totalApproxScans  = intval($value);
                        }
                        else
                        {
                           $totalApproxScans *= intval($value);
                        }
                     }

                     if ($column == 'type')
                     {
                        $indexType = $value;
                     }

                     if ($hitSkeptic && $column == 'Extra' && !stristr($value,'Using index'))
                     {
                        $hitSkeptic = false;
                     }

                     if ($column == 'Extra' && stristr($value,'Using index'))
                     {
                        $hitIndex  = true;
                        $indexes[] = $indexName;
                     }

                     $out[] = str_pad($value,$sizes[$column]+10,' ',STR_PAD_RIGHT);
                  }

                  $out[] = "\n";
               }

               if ($hitSkeptic)
               {
                  sendGenericEmail( getLocalLimeDevEmailAddress() , "Skeptical Index" , "Skeptical Index Hit! ".__FUNCTION__, "Dont be a skeptic.  This index is being used somewhere.<br /><br />$query<br /><br /><pre>".implode('',$out)."</pre>");
               }

               if ($hitIndex)
               {
                  $insert = "INSERT INTO all_clients_limelight.indexes_hit VALUES ('','".implode(',',$indexes)."','".mysql_real_escape_string($query,$link)."','".basename($_SERVER['PHP_SELF'])."','{$indexType}','{$totalApproxScans}','{$elapsedTime}','".( (array_key_exists($indexType, $rules['speed'])) ? $rules['speed'][$indexType] : '-1')."',CURRENT_TIMESTAMP);";
                  mysql_query($insert,$link);
               }

               eval('$thresholdBroken = ('.$elapsedTime.' '.$rules['slow_threshold'].');');

               if ($thresholdBroken)
               {
                  fileLogger::debug("\n\n\n======================================QUERY ($elapsedTime)======================================\n\n\n",null,null,false,'.slow');
                  fileLogger::debug($query."\n\n",null,null,false,'.slow');
                  fileLogger::debug(implode('',$out)."\n\n","EXPLAIN PLAN\n\n",null,false,'.slow');
               }

               fileLogger::debug(implode('',$out)."\n\n","EXPLAIN PLAN\n\n",null,false,$type);
            }
         }
         else
         {
            fileLogger::debug("None Available - Is Update/Write Query\n\n","EXPLAIN PLAN\n\n",null,false,$type);
         }
      }
   }

   /**
   * SelectOne()
   *
   * Return the result value of the first column in the select results
   *
   * $column - if you need to get a certain column.  Else is first in list
   */
   public function SelectOne($query = '', $bindVars = array(), $default = false, $column = false)
   {
      $result = $default;

      if ($this->Select($query, $bindVars))
      {
         $pointer = ($column ? strtoupper($column) : key($this->results));

         if (array_key_exists($pointer, $this->results))
         {
            if (!is_null($this->results[$pointer][0]))
            {
               $result = $this->results[$pointer][0];
            }
            else
            {
               $result = $default;
            }
         }
      }

      return $result;
   }

   /**
   * buildResultRow()
   *
   * @param string $query
   * @param mixed $bindVars
   * @return bool
   */
   public function buildResultRow(&$rowNum,$columnName,&$columnValue)
   {
      switch($this->caseManipulation)
      {
         case 'mixed':
            $columnName = ucwords($columnName);
            break;
         case 'lower':
            $columnName = strtolower($columnName);
            break;
         case 'upper':
            $columnName = strtoupper($columnName);
            break;
      }
      switch($this->resultMode)
      {
         case 'column-row':
            $this->results[$rowNum][$columnName] = $columnValue;
            break;
         default:
            $this->results[$columnName][$rowNum] = $columnValue;
            break;
      }
   }

   /**
   * Execute()
   *
   * Accepts a query performs mysql_query
   *
   * @param string $query
   * @return resource
   */
   public function Execute($query)
   {
      $this->lastSql = $query;
      $this->Log($query);
      return mysql_query($query, $this->conn);
   }

   /**
   * Update()
   *
   * Accepts a query and simulated bind variable(s) and performs an update
   *
   * @param string $query
   * @param mixed $bindVars
   * @return bool
   */
   public function Update($query = '', $bindVars = array())
   {
      $this->error = false;
      $this->CheckConnection();
      if ($this->isSlave)
      {
         $isSlave = true;
         // we shouldnt be writing to slave if currently connected
         try
         {
            $this->Disconnect();
            $this->Connect(true);
         }
         catch(Exception $e)
         {
            $this->Log(__METHOD__.' could not connect or disconnect ('.$e.') Line: '.__LINE__);
         }
      }
      $this->count = 0;

      if($this->ValidCredentials($query, $bindVars))
      {
         $this->Bind($query, $bindVars);

         $this->elapsed = $this->GetTime();

         $this->Log($query);

         $result = mysql_query($query, $this->conn);

         $this->elapsed -= $this->GetTime();

         $this->RegisterStatistics($query);

         if ($isSlave)
         {
            // go back to slave
            try
            {
               $this->Disconnect();
               $this->Connect(2);
            }
            catch(Exception $e)
            {
               $this->Log(__METHOD__.' could not connect or disconnect ('.$e.') Line: '.__LINE__);
            }
         }

         if (! $result)
         {
            return $this->Fail('Failed Updating Records');
         }
         else
         {
            //MySQL Wont update if values are the same, so, although true, this may return 0 - also if deleting all rows
            //
            $this->count = mysql_affected_rows($this->conn);

            if (stristr($query,'insert') && stristr($query,'into'))
            {
               $this->lastInsertId = mysql_insert_id($this->conn);
            }

            $this->ExplainQueries($query,'.'.__CLASS__,$this->elapsed,$this->conn,false);

            return true;
         }
      }
   }

   /**
   * Insert()
   *
   * Accepts a query and simulated bind variable(s) and performs an Insert
   *
   * @param string $query
   * @param mixed $bindVars
   * @return bool
   */
   public function Insert($query = '', $bindVars = array())
   {
      return $this->Update($query, $bindVars);
   }

   /**
   * GetColumnDefinitions()
   *
   * Gets you the table definition.  Returns zero if table doesnt exist
   *
   * @param string $tableName
   * @return bool
   */
   public function GetColumnDefinitions($tableName)
   {
      return $this->Select("SHOW FULL COLUMNS FROM `:TABLE_NAME`",array('TABLE_NAME'=>$tableName));
   }

   /**
   * GetMaxTableId()
   *
   * Fetches the max of specified column
   *
   * @param string $tableName
   * @param string $idName
   * @return integer count
   */
   public function GetMaxTableId($tableName = '', $idName = 'id')
   {
      $query = 'SELECT MAX(:ID) FROM :TABLE';

      $bindVars = array('ID'    => $idName,
                        'TABLE' => $tableName);

      return $this->Select($query, $bindVars);
   }

   /**
   * Bind()
   *
   * Simulated bindvariable mechanism mainly for security/sanitisation and improved transition
   *
   * @param string $query
   * @param string $bindvars
   * @return void
   *
   * @todo restructure/position lastSql
   */
   public function Bind(&$query, &$bindvars, $isSQL=true)
   {
      $this->lastSqlRaw  = $query;

      if(count($bindvars) > 0)
      {
         //Sort by length of largest KEY desc
         //

         $lengths = array();
         $inputs  = array_keys($bindvars);
         foreach ($inputs as $key)
         {
            $lengths[$key] = strlen($key);
         }
         $tmp = $bindvars;
         $bindvars = array();
         arsort($lengths);
         foreach (array_keys($lengths) as $key)
         {
            $bindvars[$key] = $tmp[$key];
         }

         //Replace occurrences in your SQL
         //
         foreach($bindvars as $bind => $var)
         {
            if ($isSQL === true)
            {
               // -- only escape when parsing SQL, but when parsing dodah, we want HTML bindVars to not be escaped
               $var = mysql_real_escape_string($var,$this->conn);
            }
            $query = str_replace(':' . $bind, $var , $query);
         }
      }
      $this->lastSqlBind = $bindvars;

      if ($isSQL === true)
      {
         $this->lastSql = $query;
      }
      else
      {
         return $query;
      }
   }

   /**
   * Success()
   *
   * object specific - return-success / debugging layer
   *
   * @param string $message
   * @return integer count
   */
   private function Success($message = '')
   {
      $this->Log('MySQL: ' . $message);
      return true;
   }

   /**
   * Fail()
   *
   * object specific - return-fail / debugging layer
   * Also handles transactions
   *
   * @param string $message
   * @return integer count
   */
   private function Fail($message = '')
   {

      if (stristr($this->lastSql,'show full columns'))
      {
         // no need to log these if checking if using a show full columns to check if a table exists
         return false;
      }

      if($this->transaction)
      {
         $this->transError = true;
      }
      else
      {
         $this->error = true;
      }

      if(! empty($message))
      {
         $this->Log('MySQL: ' . $message,true);
      }

      $this->errorMsg = 'MySQL: ' . mysql_errno($this->conn) . ': ' . mysql_error($this->conn). ' on '. $this->lastSql;
      $this->Log($this->errorMsg,true);
      $this->Notify($this->errorMsg);

      return false;
   }

   /**
   * Commit()
   *
   * Commits a transaction
   *
   * @return bool
   */
   public function Commit()
   {
      $this->Log('COMMIT');
      if(mysql_query('COMMIT', $this->conn))
      {
         return $this->Success('Transaction(s) successfully Committed');
      }
      else
      {
         $this->Rollback();
         return $this->Fail('Commit failed');
      }
   }

   /**
   * Rollback()
   *
   * Rolls back a transaction
   *
   * @return bool
   */
   public function Rollback()
   {
      $this->Log('ROLLBACK');
      if(mysql_query('ROLLBACK', $this->conn))
      {
          return $this->Success('Transaction(s) successfully Rolled Back');
      }
      else
      {
          return $this->Fail('Rollback failed');
      }
   }

   private function GetTime()
   {
      list($usec, $sec) = explode(" ",microtime());
      return ((float)$usec + (float)$sec);
   }

   public function __destruct()
   {
      if($this->transaction)
      {
          if($this->transError)
          {
             if($this->Rollback())
             {
                 $this->Success('Disconnecting with known error - Rolling Back');
             }
             else
             {
                 $this->Fail('Failed dissconnecting via ROLLBACK');
             }
          }
          else
          {
             if($this->Commit())
             {
                 $this->Success('Dissconnected successfully via COMMIT');
             }
             else
             {
                 $this->Fail('Failed dissconnecting via COMMIT');
             }
          }
      }

      if($this->conn)
      {
         mysql_close($this->conn);
         $this->conn = false;
      }
   }
}

if (defined('DB_SERVER_USERNAME') && defined('DB_SERVER_PASSWORD') && defined('DB_DATABASE') && defined('DB_SERVER'))
{
   if(! isset($DATABASE) || ! is_object($DATABASE))
   {
      try
      {
         $DATABASE = new sql();
         $DATABASE->Connect();
      }
      catch(Exception $e)
      {
         $valid = false;
      }
   }
}

?>
