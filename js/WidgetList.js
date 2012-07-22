
var debugFlag = 0;
var debugContainer = '';


/**
* ListSearchAheadResponse()
*/
var ListSearchAheadGlobalElement = '';
var ListSearchAheadQueueElement = '';
var ListSearchAheadInProgress = false;
var ListSearchAheadInProgressUrl;
var ListSearchAheadInProgressTarget;
var ListSearchAheadInProgressObj;



var AjaxMaintainChecksRunning = false;


function SearchWidgetList(url, listId, element)
{
   var inputField = ll(element).closest('div.inputOuter').find('input.search-ahead');

   if(inputField.length)
   {
      ListSearchAheadQueue(url, listId, inputField);
   }
}

//simple version
function RefreshList(list_id, callback)
{
   ListJumpMin(ll('#' + list_id + '_jump_url').val(), list_id, callback);
}

//will refresh to ajax.php version posting your entire form with it. useful if you have a button to drill into the same view (see advanced routing load balance page)
function RefreshProfileLimeList(list_id, action, id, callback)
{
   ajaxStatus(list_id, 1);
   var func = function()
   {
      ll.post('ajax.php', ll('#frmProfile').serialize() + '&selected_item=' + id + '&action=' + action, function(response)
      {
         ajaxStatus("loading" + list_id, 0);
         ll('#' + list_id).html(response['html']);
         if (typeof(callback) == 'string')
         {
            eval(callback);
         }
         else if (typeof(callback) == 'function')
         {
            (callback)();
         }
      }, "json");
   }
   RunAjax( func );
}


/**
 * Debug()
 *
 * @param string what
 * @return void
 */
function Debug(what)
{
   if(debugFlag >= 1)
   {
      if(window.document.getElementById(debugContainer))
      {
         var time = new Date();

         element = window.document.getElementById(debugContainer);

         element.innerHTML += '<br/><span>' + time.getHours() + ':' + time.getMinutes() + ':' + time.getSeconds() + '</span>  ' + what;
         element.scrollTop  = element.scrollHeight;
      }
   }
}

/**
 * Redirect()
 *
 * @param string inUrl
 * @return void
 */
function Redirect(inUrl)
{
   if(typeof(inUrl) == 'string')
   {
      if(inUrl.length > 0)
      {
         window.location = inUrl;
      }
      else
      {
         throw('Missing URL input for Redirect()');
      }
   }
   else
   {
      throw('Invalid URL type input for Redirect()');
   }
}

function ButtonFormPost(theForm)
{
   try
   {
      if (typeof(theForm) == "object")
      {
         theForm.submit();
      }
      else
      {
         if (document.getElementById(theForm) && typeof(theForm) !="undefined" && typeof(theForm) != 'null') {
            document.getElementById(theForm).submit();
         } else {
            throw new Exception();
         }
      }
   }
   catch(e)
   {
      // -- id might not be there, so try the form by name
      try
      {
         eval('document.' + theForm + '.submit()');
      }
      catch(e)
      {
         if (document.forms.length == 1)
         {
            document.forms[0].submit();
         }
         else
         {
            return false;
         }
      }
   }
}


function ButtonLinkPost(inUrl)
{
   try
   {
      Redirect(inUrl);
   }
   catch(e)
   {
      if(debugFlag)
      {
         Debug(e);
      }
   }
}

function ajaxStatus(eToHide, fadeInOut)
{

   if(document.getElementById(eToHide))
   {
      elmToHide  = document.getElementById(eToHide);

      eHider = "loading" + eToHide;

      if(document.getElementById(eHider))
      {
         elmHider = document.getElementById(eHider);
      }
      else
      {
         var overLay = '<div style="position:relative;top:0px;"><div class="ajaxLoad" id="' + eHider + '" style="height:' + ll(elmToHide).height() + 'px;width:' + ll(elmToHide).width() + 'px;top:-' + ll(elmToHide).height() + 'px;"></div></div>';

         ll(elmToHide).append(overLay);

         elmHider = document.getElementById(eHider);
      }

      if(typeof(fadeInOut) == 'number')
      {
         if(fadeInOut > 0)
         {
            fadeInOut = 1;
            ll(elmHider).fadeTo("fast", .20);
         }
         else
         {
            fadeInOut = 0;
            ll(elmHider).remove();
         }
      }
   }
}


function ListChangeGrouping(listId)
{
   ajaxStatus(listId, 1);
   HideAdvancedSearch(ll('#' + listId + '-group'));
   ll('#list_search_id_' + listId ).val('');
   InitInfoFields(ll('#list_search_id_' + listId));
   ListJumpMin(ll('#' + listId + '_jump_url').val() + '&searchClear=1&switch_grouping=' + ll('#list_group_id_' + listId ).val(), listId);
}


function ListDrillDown(mode,data,listId)
{
   ll('#list_search_id_' + listId).val('');
   ListJumpMin(ll('#' + listId + '_jump_url').val() + '&drill_down=' + mode + '&filter=' + data + '&search_filter=', listId, function(){
      InitInfoFields(ll('#list_search_id_' + listId));
   });
}


/**
* ListJumpResponse()
*/
function ListJumpResponse(response)
{
   //ajaxStatus("loading" + response['list_id'], 0);
   ll(document.getElementById(response['list_id'])).after(response['list']).remove();

   if(typeof response['callback'] === 'string')
   {
      eval(response['callback'] + '()');
   }
}

/**
* ListJumpMin()
*/
function ListJumpMin(url, id, callback, post)
{
   if(document.getElementById(id))
   {
      ajaxStatus(id, 1);
   }

   if(document.getElementById(id))
   {
      try
      {
         ll.post(url, post, function(response)
         {
            if(typeof(response['session_lost']) != 'undefined')
            {
               ajaxStatus(id, 0);

               LimePop('limePrompt', response['session_lost_message']);
            }
            else
            {
               ListJumpResponse(response);
               if (typeof(callback) == 'string')
               {
                  eval(callback);
               }
               else if (typeof(callback) == 'function')
               {
                  (callback)();
               }
            }
         }, "json");
      }
      catch(e)
      {
         LimeDbg(e);
      }
   }
}

function ListSearchAheadResponse()
{
   if(ListSearchAheadInProgress)
   {
      ListSearchAheadInProgress = false;

      if(ListSearchAheadQueueElement != '')
      {
         (ListSearchAheadQueueElement)(ListSearchAheadInProgressUrl,ListSearchAheadInProgressTarget,ListSearchAheadInProgressObj);

         ListSearchAheadQueueElement = '';
      }
   }
}

/**
* ListSearchAhead()
*/
function ListSearchAhead(url, id, element)
{
   if(! ListSearchAheadInProgress && ll(element).length && typeof(ll(element).val()) != 'undefined' && ll(element).val() != ll(element).attr('title'))
   {
      ListSearchAheadInProgress = true;
      ListJumpMin(url+ '&search_filter=' + ll(element).val(), id);
   }
}

/**
* ListSearchAheadQueue()
*/
function ListSearchAheadQueue(url, id, element)
{
   if(! ListSearchAheadInProgress)
   {
      ListSearchAheadGlobalElement = element;

      setTimeout("ListSearchAhead('"+url+"', '"+id+"', ListSearchAheadGlobalElement)", 500);
   }
   else
   {
      ListSearchAheadInProgressUrl = url;
      ListSearchAheadInProgressTarget = id;
      ListSearchAheadInProgressObj = element;

      ListSearchAheadQueueElement = ListSearchAhead;
   }
}

var WidgetSearchAheadQueuedRawDog = '';
var WidgetSearchAheadInProgress = false;
var WidgetSearchAheadInProgressUrl;
var WidgetSearchAheadInProgressTarget;
var WidgetSearchAheadInProgressObj;

/**
* WidgetSearchAheadResponse()
*
* @note searchTarget is the
*
* @todo session/token based WidgetSearchAheadQueuedRawDog?
*/
function WidgetSearchAheadResponse(response)
{
   if(WidgetSearchAheadInProgress)
   {
      WidgetSearchAheadInProgress = false;

      if(WidgetSearchAheadQueuedRawDog != '')
      {
         (WidgetSearchAheadQueuedRawDog)(WidgetSearchAheadInProgressUrl,WidgetSearchAheadInProgressTarget,WidgetSearchAheadInProgressObj);

         WidgetSearchAheadQueuedRawDog = '';
      }
   }

   var searchResults = '';
   var searchTarget = '';

   //Capture the results
   //
   if(response && typeof(response['content']) != 'undefined' && response['content'] != '')
   {
      searchResults = response['content'];
   }

   if(response && typeof(response['target']) != 'undefined' && response['target'] != '')
   {
      var result = response['target'] + '_results';
      var searchTarget = document.getElementById(result);
   }

   ll('.widget-search-content', searchTarget).html(searchResults);

   if(searchResults != '')
   {
      if(! ll(searchTarget).is(':visible'));
      {
         ll(searchTarget).slideDown();
      }
   }
   else
   {
      if(ll(searchTarget).is(':visible'));
      {
         ll(searchTarget).slideUp();
      }
   }
}

/**
 * ListSearchAhead()
 */
function WidgetInputSearchAhead(url, target, obj)
{
   if(! WidgetSearchAheadInProgress)
   {
      WidgetSearchAheadInProgress = true;

      if(document.getElementById(target) && document.getElementById(target).value != document.getElementById(target).title)
      {
         var targetElement = document.getElementById(target + '_results');

         if(! ll('.widget-search-content', targetElement).html())
         {
   /*         ll('.widget-search-content', targetElement).html(limeload);

            if(! ll(targetElement).is(':visible'));
            {
               ll(targetElement).slideDown();
            }*/
         }

         ll.post(url, {target:target,value:document.getElementById(target).value}, WidgetSearchAheadResponse, 'json');
      }
   }
   else
   {
      WidgetSearchAheadInProgressUrl = url;
      WidgetSearchAheadInProgressTarget = target;
      WidgetSearchAheadInProgressObj = obj;

      WidgetSearchAheadQueuedRawDog = WidgetInputSearchAhead;
   }
}

/**
* WidgetInputSearchAhead()
*
* @todo Log last keyup and launch it if it exceeds the wait time of the pending request
* @todo multiple search aheads on one page
* @todo baseurl
* @todo duration parameter
*/
function WidgetInputSearchAheadQueue(url, id, obj)
{
   setTimeout("WidgetInputSearchAhead('"+url+"', '"+id+"', '"+obj+"')", 500);
}


function WidgetAdvancedSearchReset(form_id, list_id, url)
{
   var frm_elements = document.getElementById(form_id);

   for (i = 0; i < frm_elements.length; i++)
   {
       field_type = frm_elements[i].type.toLowerCase();
       switch (field_type)
       {
       case "text":
       case "password":
       case "textarea":
           frm_elements[i].value = "";
           break;
       case "radio":
       case "checkbox":
           if (frm_elements[i].checked)
           {
               frm_elements[i].checked = false;
           }
           break;
       case "select-one":
       case "select-multi":
           frm_elements[i].selectedIndex = 0;
           break;
       default:
           break;
       }
   }

   InitInfoFields();
   ListJumpMin(url, list_id);
}


function BuildUrl(getVars)
{
   var url = '';
   ll.each(getVars, function(field, value)
   {
      url += '&' + field + '=' + escape(value);
   });
   return url;
}

(function(ll)
{
   ll(document).ready(
   function()
   {
      /**
      * @todo if is visible slide up
      */
/*      ll('.widget-search-drilldown').each(
      function()
      {
         //widget-search-content
         //
         var masterBlaster = this;

         ll('.widget-search-content', this).bind("mouseenter",
         function()
         {
            ll(masterBlaster).stop();
            ll(masterBlaster).slideDown();
         }
         ).bind("mouseleave",
         function()
         {
            //ll(masterBlaster).stop();
            ll(masterBlaster).slideUp();
         });
      });*/

      InitInfoFields();
   });
})(jQuery);


function InjectInfoField(inField,message)
{
   if(typeof(message) == 'undefined' || message == '')
   {
      var message = '';
      var color   = 'black';
   }
   else
   {
      var color   = 'red';
   }
   ll(inField).attr('title',message);
   InitInfoFields(inField);
   ll(inField).css('color',color);
}

/**
* InitInfoFields()
*
* @todo assign the info-input class if an object is passed in to preserve the functionality
*/
function InitInfoFields(inField)
{
   var theField = ll('.info-input');

   if(typeof(inField) != 'undefined')
   {
      theField = inField;
   }

   if(ll(theField).length)
   {
      //Assign an inputs title to its values initially
      //
      ll(theField).each(
      function()
      {
         if(ll(this).val() == '')
         {
            ll(this).val(ll(this).attr('title'));

            //Adjust its class to appear passive
            //
            ll(this).addClass('info-input-field-inactive');
         }
      });

      ll(theField).blur(
      function()
      {
         if(ll(this).val() != ll(this).attr('title'))
         {
            ll(this).css('color','black');
            if(ll(this).val() == '')
            {
               ll(this).css('color','#b4b3b3');
               ll(this).removeClass('info-input-field-active');
               ll(this).val(ll(this).attr('title'));
               ll(this).addClass('info-input-field-inactive');
            }
         }
         else
         {
            ll(this).css('color','#b4b3b3');
         }
      });

      ll(theField).focus(
      function()
      {
         if(ll(this).val() == ll(this).attr('title'))
         {
            ll(this).removeClass('info-input-field-inactive');
            ll(this).addClass('info-input-field-active');

            //Clear the field of the initial title if its the first onfocus
            //
            if(ll(this).val() != '' && ll(this).val() == ll(this).attr('title'))
            {
               ll(this).val('');
            }
         }
         else
         {
            ll(this).css('color','black');
            ll(this).addClass('info-input-field-active');
         }
      });
   }
}

/**
* AjaxMaintainChecks()
*
* @param obj
* @param checkbox_class
* @param list_id
* @param url
* @param check_all_id
*/
function AjaxMaintainChecks(obj, checkbox_class, list_id, url, check_all_id)
{
   AjaxMaintainChecksRunning = true;
   var serializedChecks = '';
   var checkedAllBool = true;
   var checkedAllString = '1';
   var checkAllId = checkbox_class + '_all';

   /**
   * Checking All
   */
   if(check_all_id && check_all_id != '' && ll('#' + check_all_id).length > 0)
   {
      //Overwrite assumed check-all checkbox id
      //
      checkAllId = check_all_id;

      //Check or uncheck check-all checkbox
      //
      ll('.' + checkbox_class).attr('checked', ll('#' + checkAllId).is(':checked'));
   }

   /**
   * Serialize all checkboxes both checked and unchecked
   */
   ll('.' + checkbox_class).each(
   function(key, value)
   {
      var checked = '0';

      if(this.checked)
      {
         checked = '1';
      }
      else
      {
         checkedAllBool = false;
         checkedAllString = '0';
      }

      serializedChecks += escape(this.value) + '=' + checked + '&';
   });

   /**
   * Check All Checkbox Status. On/Off
   */
   ll('#' + checkAllId).attr('checked', checkedAllBool);

   /**
   * Check All for this view (page/sequence
   */
   serializedChecks += 'checked_all=' + checkedAllString;

   /**
   * Record everything
   */
   ll.post(url, serializedChecks, function()
   {
      AjaxMaintainChecksRunning = false;
   }, "json");
}

function ToggleAdvancedSearch(searchElement)
{
   var contentArea = ll(searchElement).closest('div.inputOuter').find('.widget-search-drilldown');
   var inputArrow  = ll(searchElement).closest('div.inputOuter').find('.widget-search-arrow-advanced');
   var searchField = ll(searchElement).closest('div.inputOuter').find('.search-ahead');

   ll(contentArea).toggle();

   if(ll(contentArea).is(':visible'))
   {
      ll(inputArrow).css('visibility', 'hidden');
   }
   else
   {
      ll(inputArrow).css('visibility', 'visible');
   }
}

function SelectBoxResetSelectedRow(listId)
{
   var currentSelection = ll('#list_group_id_' + listId).val();
   ll('.widget-search-results-row[title="' + currentSelection + '"]').addClass('widget-search-results-row-selected');
}


function SelectBoxSetValue(value, listId)
{
   ll('#list_group_id_' + listId).val(value);
   SelectBoxResetSelectedRow(listId);
}



function HideAdvancedSearch(searchElement)
{
   var contentArea = ll(searchElement).closest('div.inputOuter').find('.widget-search-drilldown');
   var inputArrow  = ll(searchElement).closest('div.inputOuter').find('.widget-search-arrow-advanced');

   ll(contentArea).hide();

   ll(inputArrow).css('visibility', 'visible');
}