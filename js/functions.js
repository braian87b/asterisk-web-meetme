function displayUserPass (id)
{
  if (document.WMAdd.userPass.value != 'NIS')
    $('#PasswordRow, #VerifyPasswordRow').show ();
}

function addEmailToTable (tableName)
{
  addRowToTable (tableName, true, 
		 [ {name: 'email[]', id: 'email', type: 'text', size: 30,
		    span: 'Email address of participant.', title: 'Email'},
		   {name: 'fname[]', id: 'first_name', type: 'text', size: 9,
		    span: 'First name of participant.', title: 'First name'},
		   {name: 'lname[]', id: 'last_name', type: 'text', size: 11,
		    span: 'Last name of participant.', title: 'Last name'},
		   {name: 'phone[]', id: 'telephone', type: 'text', size: 16,
		    span: ('Contact number for participant.  This can be an '
			   + 'extension, a US 10-digit number, an '
			   + 'international number with leading +, or '
			   + '\'Skype:\' followed by a Skype account name.'),
		    title: 'Telephone'},
		   {name: 'remove', id: 'remove', type: 'button',
		    span: 'Delete this participant.'}]);
}

function addPhoneToTable (tableName)
{
  addRowToTable (tableName, false, 
		 [ {name: 'phone[]', id: 'telephone', type: 'text', size: 35,
		    span: ('Contact number for participant.  This can be an '
			   + 'extension, a US 10-digit number, an '
			   + 'international number with leading +, or '
			   + '\'Skype:\' followed by a Skype account name.'),
		    title: 'Telephone'},
		   {name: 'type[]', id: 'type', type: 'text',
		    span: 'Type of contact (e.g., office, cell, home)',
		    title: 'Type'},
		   {name: 'from_ldap[]', id: 'from_ldap', type: 'hidden'},
		   {name: 'remove', id: 'remove', type: 'button',
		    span: 'Delete this phone number.'}]);
}

function addRowToTable (tableName, do_suggest, field_data)
{
  var tbl = $('#' + tableName);

  if ($('tr', tbl).length == 0)
    {
      var row = $('<tr>').appendTo (tbl);

      for (var i = 0; i < field_data.length; i++)
	if (field_data[i].title)
	  $('<td>').css ('text-align', 'center').css ('font-weight', 'bold')
	      .html (field_data[i].title).appendTo (row);

    }
 
  var row = $('<tr>').appendTo (tbl);
  for (i = 0; i < field_data.length; i++)
    {
      var inner = ($('<input>').attr ('name', field_data[i].name)
		   .attr ('type', field_data[i].type)
		   .addClass (field_data[i].id)
		   .prop ('wmmfield', field_data[i].id));

      if (do_suggest)
	$(inner).attr ('autocomplete', 'off')
	  .autocomplete ({source: 'autosuggest.php?field=' + field_data[i].id});
      if (field_data[i].title)
	inner.prop ('wmmtitle', field_data[i].title.toLowerCase ());

      if (field_data[i].size)
	inner.attr ('size', field_data[i].size);

      if (field_data[i].span)
	inner = $('<span>').attr ('title', field_data[i].span).append (inner);

      $('<td>').append (inner).appendTo (row);
    }

  $('.remove', row).addClass ('warn').val ('Delete')
		   .click (function () { deleteCurrentRow (this); });
  $('input', row).button ();
  $('span', row).tooltip ($('span').tooltip ('option'));
  $('td', row).has (':hidden').hide ();

  if (do_suggest)
    $('input:text', row).on ('autocompleteselect',
			     function (event, ui) { fillIn (event.target,
							    ui.item.value,
							    true); })
		        .on ('autocompleteopen',
			     function () { $('span', row).tooltip ('disable')})
		        .on ('autocompleteclose',
			     function () { $('span', row).tooltip ('enable')})
      			.change (function ()
				 { fillIn (this, $(this).val (), false); });
}

function fillIn (input, val, completion)
{
  var cell = $(input), row = cell.closest ('tr');
  if (! completion && cell.prop ('wmmfield') == 'first_name')
    return;

  $.getJSON ('fillin_user.php',
	     'value=' + val + '&field=' + cell.prop ('wmmfield'),
	     function (data)
	     { 
	       $.each (data, function (key, val)
		       { if (! $('.' + key, row).val ())
			   $('.' + key, row).val (val);
			 if (data['admin'] == 'Participant')
			   $('.' + key, row).prop ('wmmFill', val);
			 else
			   $('.' + key, row).attr ('readonly', true)
			     .css ('color', 'black')
			     .closest ('span').tooltip ('disable');
		       })
	     });
}

function vemail(addr)
{
  return addr.match (/\b(^(\S+@).+((\.com)|(\.net)|(\.edu)|(\.mil)|(\.gov)|(\.org)|(\..{2,2}))$)\b/gi);
}

function vphone (number)
{
  var phone = number.replace (/\s|-|\.|\(|\)/g, '');

  if (phone.length == 0 || phone == '[none]')
    return true;

  if (phone.substring (0, 6) == 'Skype:'
      || phone.substring (0, 6) == 'skype:')
    return true;

  if (phone.substring (0, 1) == '+')
    return true;

  if (isNaN (phone))
    return false;

  if (phone.length == 3
      && (phone.substring (0, 1) == '1' || phone.substring (0, 1) == '6'
	  || phone.substring (0, 1) == '7'
	  || phone.substring (0, 2) == '22'))
    return true;

  if (phone.length == 4 && phone.substring (0, 1) == '4'
      && vphone (phone.substring (1, 3)))
    return true;

  if (phone.length == 5 && phone.substring (0, 2) == '53')
    return true;

  if (phone.length > 5 && phone.substring (0, 1) == '1')
    phone = phone.substring (1, phone.length);

  if (phone.length == 10)
    return true;

  return false;
}

function deleteCurrentRow (obj)
{
  if ($(obj).val () == 'Delete')
    {
      $(obj).val ('Sure?');
      $(obj).data ('reset', setTimeout (function ()
					{ $(obj).val ('Delete'); }, 3000));
      return;
    }

  if ($(obj).data ('reset'))
    clearTimeout ($(obj).data ('reset'));

  var row = $(obj).closest ('tr');
  var table = row.closest ('table');

  $('span', row).tooltip ().tooltip ('destroy');
  row.remove ();
  if (table.find ('tr').length == 1)
    table.find ('tr').remove ();
}

function loadResult (selector, url)
{
  $(selector).load (url, $('form').serialize ());
  return false;
}

function setupUI (head, allowsize)
{
  $('input', head).attr ('autocomplete', 'off');
  $('span, td', head).tooltip ({ position: { my: "center top+10"}, track: true,
					     show: {effect: "slideDown",
					            duration: 700,
						    delay: 1500},
					     hide: {effect: "fadeOut",
					   	    duration: 1250 }});
  $('textarea', head).css ('text-align', 'left');
  $(':input[readonly]', head).css ('color', 'black');
  $('input, button', head).not(':radio, :checkbox').button ();
  $('select, textarea', head).addClass ('ui-button ui-widget ui-state-default ui-corner-all');
  win_y = $(window).height ();
  h_y = $('#header').height ();
  select_y = $('#select').height ();
  if (head.attr ('id') == 'deleteResult')
    win_y -= 50;
  if (allowsize != undefined)
    win_y -= allowsize;

  $('table.data', head).tableScroll ({height: win_y - h_y - select_y - 180,
				      flush: false});
  if (!$.browser.msie)
    {
      $(':checkbox', head).addClass ('css-checkbox med');
      $(':checkbox', head).next ('label').addClass ('css-label med elegant');
    }

  if ($('.tablescroll_wrapper', head).length && $('#now', head).length)
    $('.tablescroll_wrapper', head)
      .scrollTop ($('#now', head).offset ().top - 350);
}

function confirmDelete (url)
{
  var confs = [];

  $(':checkbox:checked').each (function ()
	{ confs.push ([ $(this).closest ('tr').find ('.del_starttime').text (),
			$(this).closest ('tr').find ('.del_confDesc').text ()]);});


  if (confs.length == 0)
    return false;

  var confmsg = ('Are you sure you want to delete the '
		 + (confs.length == 1 ? 'conference '
		    : (confs.length + ' conferences '))
		 + 'below?\n\n');

  for (var i = 0; i < confs.length; i++)
    confmsg += '\t\t' + confs[i][1] + ' at ' + confs[i][0] + '\n';

  if (!confirm (confmsg))
    return false;

  $('#deleteResult').load (url, $('#deleteConf').serialize ());
  return false;
}

function callChange (id)
{
  if ($(id).val () == "other")
    $('#otherData').show ();
  else
    $('#otherData').hide ();
}

function conf_invite (confno, pin)
{
  var selected = $('#toCall').val ();
  var name, phone;

  if (selected.length == 0)
    {
      alert ("No participant selected.");
      return;
    }

  if (selected != "other")
    {
      var brace = selected.indexOf ("[");

      name = selected.substring (0, brace - 1);
      phone = selected.substring (brace + 1, selected.length - 1);
    }
  else
    {
      name = $('#toCallName').val ();
      phone = $('#toCallPhone').val ();
    }

  if (phone.length == 0 || !vphone (phone))
    {
      alert ("Phone number invalid.");
      return;
    }

  if (name.length == 0)
    {
      alert ("Name missing.");
      return;
    }

  if (!confirm (("Are you sure you want to add " + name + " at " + phone
		 + " to conference " + confno + "?")))
    return;
	  
  var params = ('confno=' + confno + '&invite_num=' + phone
		+ '&name="' + name + '"');

  if (pin)
    params += '&pin=' + pin;

  $('#callInvite span').text ('Calling ....');
  $.post ('call_operator.php', params,
	function (result) {$('#callInvite span').text (result.Response); setTimeout (function () {$('#callInvite span').text ('Call')}, 5000); }, 'json');
;
  return;
}

function currentDate ()
{
  var dateSplit = $('#now').val ().split ('-');

  return new Date (dateSplit[0], dateSplit[1] - 1, dateSplit[2]);
}

function changeReportType (first)
{
  if (first)
    $('area').mouseover (toolTip).mouseout (function () {$('#tip').hide ();});

  if (curtype == 'yearly')
    {
      $('#year').show ();
      $('#date').hide ();
    }
  else
    {
      var dateFmt = (curtype == 'monthly' ? 'MM yy' : 'MM d, yy');

      $('#year').hide ();
      $('#date').show ();
      if (!first)
	$('#date').datepicker ('destroy');

      $('#date').datepicker ({ altField: '#now', altFormat: 'yy-mm-dd',
			       changeMonth: true, changeYear: true,
			       hideIfNoPrevNext: true, maxDate: '+1y',
			       onClose: dateSet, showAnim: 'slideDown',
			       showButtonPanel: true, gotoCurrent: true,
			       currentText: 'Reset', dateFormat: dateFmt,
			       onChangeMonthYear: dateSet,
			       defaultDate: currentDate ()})
		.val ($.datepicker.formatDate (dateFmt, currentDate ()));

      $('<style>.ui-datepicker-calendar { display: '
	+ (curtype == 'monthly' ? 'none' : 'inline') + '; }</style>')
	.appendTo ('head');
    }
}

function daysPerMonth (year, month)
{
  return timeDifference = new Date (year, month, 0).getDate ();
}

function yearChange ()
{
  $('#now').val ($('#year').val () + '-01-01');

  reportUpdate ();
}

function dateSet ()
{
  if (curtype == 'monthly')
    {
      var year = $("#ui-datepicker-div .ui-datepicker-year").val ();
      var month = $("#ui-datepicker-div .ui-datepicker-month").val ();
      $('#date').datepicker ('setDate', new Date (year, month, 1));
    }

  reportUpdate ();
}

function downSelect (type, parm)
{
  var len = type == 'monthly' ? 4 : (type == 'daily' ? 7 : 10);
  var val = ($('#now').val ().substr (0, len)
	     + (type == 'hourly' ? ' ' : '-') + parm);

  while (val.length < 10)
    val += '-01';

  $('#now').val (val);

  curtype = type;
  changeReportType (false);
  reportUpdate ();
}

function reportUpdate ()
{
  $('#headerMsg').text ('Total Conferences Scheduled and Held '
			+ (curtype == 'daily' ? 'on' : 'in'));

  if (curtype == 'hourly')
    {
      window.top.location
	= ('index.php?' + st + '&hour=' + $('#now').val ());
      return;
    }

  $.cookie ('ReportNow', $('#now').val ());
  $('#ReportPNG').prop ('useMap', '#' + curtype + 'Map')
    .attr ('src', 'report-gen.php?now=' + $('#now').val ()
	   + '&type=' + curtype);
}

function toolTip (evt)
{
  var date = currentDate ();
  var dateFmt = (curtype == 'yearly' ? 'MM yy' : 'MM d, yy');
  var parm = $(this).attr ('value');

  if (curtype == 'yearly')
    date.setMonth (parm - 1);
  else if (curtype == 'monthly')
    date.setDate (parm);

  if (curtype == 'monthly'
      && parm > daysPerMonth (date.getFullYear (), date.getMonth ()))
    return;

  str = 'Click for ' + $.datepicker.formatDate (dateFmt, date);
  if (curtype == 'daily')
    str += ' at ' + (parm == 0 ? '12 am'
		     : (parm < 12 ? parm + ' am'
			: (parm  == 12 ? 12 : parm - 12) + ' pm'));

  $('#tip').show ().text (str);
  ew = $('#tip').width ();
  lv = Math.max (evt.pageX - (ew / 4), 2);
  if (lv + ew > $(window).width ())
    lv -= ew / 2;

  $('#tip').offset ({left: lv, top: evt.pageY - 22});
}

function conf_action (action, confno, id, caller)
{
  if ((action == 'end'
       && !confirm ('Are you sure you want to end this conference?'))
      || (action == 'kick'
	  && !confirm ('Are you sure you want to remove ' + caller + '?')))
    return;

  $.get ('conf_actions.php',
	 'action=' + action + '&confno=' + confno + '&id='
	 + encodeURIComponent (id));
}

function onSubmitUser (type, isAdmin)
{
  var action = $('#_button').attr ('name');

  if ($('#_delete').prop ('deleting') == 1)
    action = 'delete';
  else if (! validateUserSubmit (action, isAdmin))
    return false;

  $('#addUserResult').load ('user_add_' + type + '.php?' + action,
			    $('form').serialize ());

  return false;
}

function validateUserSubmit (action, isAdmin)
{
  var error = false;
  var userType = $('[name=userType]:checked').val ();

  if (!vemail ($('[name=userEmail]').val ()))
    error = "Invalid or missing email address.";
  else if ($('[name=fname]').val ().length < 2)
    error = "First name missing.";
  else if ($('[name=lname]').val ().length < 2)
    error = "Last name missing.";
  else if (action == "add" && $('[name=userPass]').val ().length == 0)
    error = "Password not specified";
  else if ($('[name=userPass]').val ()
	   != $('[name=verifyUserPass]').val ())
    error = "Passwords don't match.";
  else 
    $('.telephone').each (function ()
			  { if ($(this).val ().length == 0)
			      error = 'Phone number must be specified.';
			    else if (!vphone ($(this).val ()))
			      error = ('"' + $(this).val () + '" is not a '
				       + 'valid phone number.\nIt must be an '
				       + 'extension, a domestic number, or an '

				       + 'international number.');});

  if (!error)
    $('.type').each (function ()
		     { if ($(this).val ().length == 0)
			 error = 'Phone type missing.'; });

  if (error)
    {
      alert (error);
      return false;
    }

  if (! userType || userType.length == 0)
    userType = 'Participant';
  var confirmMsg = "Do you want to " + action + " ";
  if (isAdmin)
    confirmMsg += userType + " ";

  confirmMsg += $('[name=fname]').val() + " ";
  confirmMsg += $('[name=lname]').val() + " ";
  confirmMsg += ' (' + $('[name=userEmail]').val() + ')?';
  return confirm (confirmMsg);
}

function togglePass (id)
{
  if (id.checked)
    {
      $('#PinRow, #UserOptionsRow').show ();
      $('#adminpin, #pin').each (function ()
	{ if (!$(this).val ())
	    $(this).val (1000 + Math.floor (Math.random () * 9000)); });
    }
  else
    {
      $('#PinRow, #UserOptionsRow').hide ();
      $('#adminpin, #pin').val ('');
    }
}

function toggleLimit (id)
{
  if (id.checked)
    {
      $('table.options').css ('width', '100%');
      $('#MaxParticipantsRow').show ();
      $('#limitLabel').text ('Limited to');

      if ($('#maxUsers').val () == '0')
	$('#maxUsers').val ('10');
    }
  else
    {
      $('table.options').css ('width', '80%');
      $('#MaxParticipantsRow').hide ();
      $('#limitLabel').text ('Limited');
      $('#maxUsers').val ('0');
    }
}

function toggleEmail (id)
{
  if (id.checked)
    $('#EmailOptionsRow, #RemindOptionsRow').show ();
  else
    $('#EmailOptionsRow, #RemindOptionsRow').hide ();
}

function toggleMessage (id)
{
  if (id.checked)
    $('#EmailMessage').show ();
  else
    $('#EmailMessage').hide ();
}

function toggleDelete (id)
{
  tr = $(id).parents ('tr');
  if (id.checked)
    tr.attr ('class', tr.attr ('class') + '_del');
  else
    tr.attr ('class', tr.attr ('class').replace ('_del', ''));
}

function recurHide (id)
{
  if (id.checked)
    $('#recurData').show ();
  else
    $('#recurData').hide ();
}

function onSubmitConf ()
{
  var action = $('#_button').attr ('name');

  if ($('#_delete').prop ('deleting') == 1)
    action = 'delete';
  else if (! validateConfSubmit (action))
    return false;

  $.post ('conf_add.php?' + action, $('form').serialize (),
	  function (data) { $('#conf_add_response'). html (data); });

  return false;
}

function validateConfSubmit (action)
{
  var sendingemail = false;
  var error = false;
  var confopts = '';
  var emailopts = '';
  var remopts = ''
  $('.confOptions:checked').each (function () {confopts += $(this).val ();});
  $('.emailOptions:checked').each (function () {emailopts += $(this).val ();});
  $('.remindOptions:checked').each (function () {remopts += $(this).val ();});

  if (emailopts.indexOf ('s') >= 0)
    sendingemail = true;

  if (!sendingemail && remopts.length)
    if (!confirm ('Disabling email will also disable reminders.  '
		  + 'Is that what you intended?'))
      return false;
	  
  if ($('[name=confDesc]').val ().length == 0)
    error = 'Conference name missing.';
  else if (!vemail ($('[name=confOwner]').val ()))
    error = 'Conference owner not valid.';
  else if (isNaN ($('[name=confno]').val ())
      || $('[name=confno]').val ().length != 7)
    error = 'Conference number invalid (must be 7 digits).';
  else if (emailopts.indexOf ('t') >= 0
	   && $('[name=emailText]').val ().length == 0)
    error = "Extra email text is blank.";

  else
    $('#adminpin, #pin').each (function ()
			       { var val = $(this).val ();
				 if (val && (isNaN (val) || val.length != 4))
				   error = 'PIN must be 4 digits.';});

  if (!error && $('#limited').is (':checked')
      && (isNaN ($('#maxUsers').val ())
	  || $('#maxUsers').val () < 3))
	error = 'Maximum number of users must be a number greater than 2.'

  if (!error)
    $('.email').each (function ()
		      { var email = $(this).val ();
			if (email.length == 0)
			  error = 'Email address missing.';
			else if (!vemail (email))
			  error = ('"' + email + '" is not an valid email '
				   + 'address.'); });

  if (!error)
    $('.telephone').each (function ()
			  { if (!vphone ($(this).val ()))
			      error = ('"' + $(this).val () + '" is not a '
				       + 'valid phone number.\nIt must be an '
				       + 'extension, a domestic number, or an '

				       + 'international number.');});

  if (error)
    {
      alert (error);
      return false;
    }

  $('input:text').each (function ()
		{ var email = $(this).closest ('tr').find ('.email').val ();
		  var query = ($(this).prop ('wmmfield') == 'telephone'
			       ? 'add a phone number for ' + email + ' of '
			       : ('change the ' + $(this).prop ('wmmtitle')
				  + ' of ' + email + ' from '
				  + $(this).prop ('wmmFill') + ' to '));

		  if ($(this).prop ('wmmFill')
		      && $(this).prop ('wmmFill') != $(this).val ()
		      && !confirm ('Did you mean to ' + query + $(this).val ()
				   + '?'))
		    error = true; });

  if (error)
    return false;
		    
  $('.phones [value="[none]"]').val ('');

  if ($('.email').length == 0
      && !confirm ("You haven't invited anybody to participate in this conference.\nIs that what you intended?"))
    return false;

  var startDate = new Date ($('#startTime').val ());
  var confirmMsg
    = ('You are ' + (action == 'update' ? 'updating' : 'scheduling')
       + ' a conference at ');

  confirmMsg += (startDate.toLocaleTimeString () + ' on '
		 + startDate.toLocaleDateString () + ' for ');
  
  var duration = $('#duration').val ().split (':');
  if (parseInt (duration[0]) == 1)
    confirmMsg += "1 hour";
  else if (parseInt (duration[0]) != 0)
    confirmMsg += parseInt (duration[0]) + " hours";

  if (parseInt (duration[1]))
    {
      if (parseInt (duration[0]))
	confirmMsg += " and ";
      confirmMsg += parseInt (duration[1]) + " minutes";
    }

  confirmMsg += ".";

  if ($('#limited').is (':checked'))
    confirmMsg += ("\nA maximum of " + $('#maxUsers').val ()
		   + " people may participate in this conference.");

  if ($('.email').length > 0)
    confirmMsg += ("\nYou have invited " + $('.email').length
		   + ($('.email').length == 1 ? " person" : " people")
		   + " to participate in this conference.");

  if ($('#recur').is (':checked'))
    confirmMsg += ("\nThe conference will recur "
		   + $('select[name=recurLbl]').val ().toLowerCase ()
		   + " for a total of "
		   + $('#recurPrd').val () + " times.");

  if (confopts.indexOf ('M') >= 0)
    confirmMsg += "\nThis conference will be moderated.";

  if (confopts.indexOf ('r') >= 0)
    confirmMsg += "\nThis conference will be recorded.";

  if (!confirm (confirmMsg + "\n\nIs this correct?"))
    return false;

  return true;
}

function processResult (message, error, s, t, sent_email)
{
  $('#actionResult').text (message);
  if (error)
    {
      $('#_button, #_delete').hide ();
      $('form input').change (function () { $('#_button, #_delete').show ();
					    $('#actionResult').text (''); });
      return;
    }

  $('form').attr ({action: 'index.php', onsubmit: null, target: '_top'});
  $('#_button').val ('Continue').closest ('span').tooltip ('disable');
  $('#_delete').hide ();
  $('input:text').attr ('readonly', true).css ('color', 'black');
  $('#s').val (s);
  $('#t').val (t);
  $('#_delete').hide ();

  if (sent_email)
    {
      $('#resultEmail').show ();
      $('.lastRow td').removeClass ('bbl bbr');
    }
}

/* Convert an array of a "x:y" and "am/pm" time into a 24-hour time as
   a string.  */

function convertTo24Hr (time)
{
  var hour = parseInt (time[0].split (':')[0]);
  var pm = time[1].toLowerCase () == 'pm';

  if (hour == 12 && pm)
    ;
  else if (hour == 12)
    hour = 0;
  else if (pm)
    hour += 12;

  return (hour < 10 ? '0' + hour : hour) + ':' + time[0].split (':')[1];
}

function intToTime (i, capitalize)
{
  var hour = Math.floor (i / 60);
  var min = i - hour * 60;
  var ampm = 'AM';

  if (i == 0 || i == 1440)
    return capitalize ? 'Midnight' : 'midnight';
  else if (i == 720)
    return capitalize ? 'Noon' : 'noon';
  if (hour == 12)
    ampm = 'PM';
  else if (hour > 12)
    ampm = 'PM', hour -= 12;

  return (hour.toString () + ':'+ ((min < 10) ? '0' : '') + min.toString ()
	  + ' ' + ampm);
}

function findTimeClose ()
{
  $('#findTime').hide ();
  $('#findTimeMain').empty ();
  $('.findTimeTooltip').hide ();
}

function findTimeSlide (event, ui)
{
  adjustSlider ($(this), ui);
}

function adjustSlider ($this, ui)
{
  var $tip1 = $('#tip1'), $tip2 = $('#tip2');
  var offset1 = $this.children ('.ui-slider-handle').first ().offset ();
  var offset2 = $this.children ('.ui-slider-handle').last ().offset ();
  var left = $this.offset ().left;
  var right = left + $this.width ();

  $tip1.show ().text (intToTime (ui.values[0], true));
  $tip2.show ().text (intToTime (ui.values[1], true));

  var tip1Left = Math.max (offset1.left - $tip1.width (), left);
  var tip2Left = Math.min (offset2.left, right - $tip2.width ());

  if (tip1Left + $tip1.width () + 18 > tip2Left)
    {
      $tip1.text ($tip1.text () + ' -');
      if (tip2Left < offset2.left)
	tip1Left = tip2Left - $tip1.width () - 5;
      else
	tip2Left = tip1Left + $tip1.width () + 5;
    }

  $tip1.css ('top', offset1.top - 25).css ('left', tip1Left);
  $tip2.css ('top', offset2.top - 25).css ('left', tip2Left);
  $('.findTimeCenter', $this.parent ()).html ('&nbsp;');
}
  
function findTimeStop (event, ui)
{
  var daytime = new Date ($('#findTimeDP').datepicker ('getDate'));

  adjustSlider ($(this), ui);
  daytime.setHours (Math.floor (ui.values[0] / 60));
  daytime.setMinutes (ui.values[0] % 60);
  $('#startTime').datetimepicker ('setTime', daytime)
		 .datetimepicker ('setDate', daytime);

  daytime.setHours (Math.floor ((ui.values[1] - ui.values[0]) / 60));
  daytime.setMinutes ((ui.values[1] - ui.values[0]) % 60);
  $('#duration').datetimepicker ('setTime', daytime);
}
  
function findTimeResult (data)
{
  if (data.unknowns.length)
    $('#findTimeErrors').text ("The following participants don't have Google Calendars: " + data.unknowns.join (', ') + '.').show ();
  else
    $('#findTimeErrors').hide ();

  $('#findTimeMain').empty ();
  if (data.intervals.length == 0)
    $('#findTimeMain').append ('<h2>No available periods.<h2>');
  else
    {
      $('#findTimeMain').append ('<div id="findTimeInner"></div>');
      $.each (data.intervals, function (index, value)
	      { $(('<h9>Click for ' + intToTime (value[0]) + ' to '
		   + intToTime (value[1]) + '</h9>'))
		  .appendTo ('#findTimeInner');
		$('<div><center class="findTimeCenter">'
		  + 'Move slider handles to set start and end times.'
		  + '</center><div class="findTimeSlide"></div></div>')
		  .appendTo ('#findTimeInner').find ('.findTimeSlide')
		  .slider ({range: true, min: value[0], max: value[1],
			    step: 5, values: [value[0], value[1]],
			    slide: findTimeSlide, stop: findTimeStop}); });
      $('#findTimeInner').accordion ({active: false, collapsible: true,
				      beforeActivate: function () 
					 { $('#tip1, #tip2').hide () }});
    }
}

function findTimeSelect (text, inst)
{
  $('#tip1, #tip2').hide ();
  $.getJSON ('get_avail.php?date=' + text,
	     $('.email').serialize (), findTimeResult);
}

function setupNewConf ()
{
  $(function ()
    {
      $('#maxUsers').spinner ({min: 2, max:25});
      $('#recurPrd').spinner ({min: 2, max:52});
      $.datepicker._gotoToday = function (id)
	{
	  var inst = this._getInst($(id)[0]);
	  var $dp = inst.dpDiv;

	  this._base_gotoToday(id);
	  var tp_inst = this._get(inst, 'timepicker');
	  var orig = inst.input.prop ('defaultValue');
	  var time = (inst.settings.timeOnly
		      ? new Date(0, 0, 0, parseInt (orig.split (':')[0]),
				 parseInt (orig.split (':')[1]), 0, 0)
		      : new Date (orig));
	  inst.selectedDay = date.getDate();
	  inst.drawMonth = inst.selectedMonth = date.getMonth();
	  inst.drawYear = inst.selectedYear = date.getFullYear();
	  this._setTime(inst, time);
	  this._notifyChange(inst);
	  this._adjustDate($(id));
	  $('.ui-datepicker-today', $dp).click();
	};
      $('#confOwner').not('[readonly]')
		     .autocomplete ({source: 'autosuggest.php?field=email'});

      var duration = ($('#duration').val ());
      $("#duration").datetimepicker ({timeOnlyTitle: 'Conference Length',
				      timeText: 'Duration', stepMinute: 15,
				      hourMax: 5, hourGrid: 1, minuteGrid: 15,
				      timeOnly: true,
				      hour: parseInt (duration.split (':')[0]),
				      minute: parseInt (duration.split (':')[1]),
				      currentText: 'Reset',
				      showAnim: 'slideDown'});

      var orig = $('#startTime').val ();
      var date = new Date (orig);
      var minDate = new Date ();

      minDate.setHours (0);
      minDate.setMinutes (0);
      minDate.setSeconds (0);
      $('#startTime').attr ('last_time',
			    orig.split (' ').slice (-2).join (' '));
      $('#startTime').datetimepicker ({dateFormat: 'DD, MM d, yy',
				       altField: '#altTime',
				       altFormat: 'yy-mm-dd',
				       hideIfNoPrevNext: true, minDate: minDate,
				       numberOfMonths: 2,
				       showAnim: 'slideDown',
				       timeFormat: 'hh:mm tt',  hourGrid: 4,
				       minuteGrid: 15, stepMinute: 5,
				       alwaysSetTime: false,
				       currentText: 'Reset',
				       altFieldTimeOnly: false,
				       altTimeFormat: 'HH:mm',
				       altSeparator: ' ',
				       hour: date.getHours (),
				       minute: date.getMinutes ()});

      $('#startTime, #duration')
	.change(function ()
		{
		  var startVal = $('#startTime').val ();
		  if (startVal.indexOf (':') < 0
		      && $('#startTime').attr ('last_time'))
		    {
		      startVal += ' ' + ($('#startTime').attr ('last_time'));
		      $('#startTime').val (startVal);
		    }

		  var time = $('#startTime').val ().split (' ').slice (-2);

		  $('#altTime').val ($('#altTime').val ().split (' ')[0]
				     + ' ' + convertTo24Hr (time));

		  $(this).closest ('span').tooltip ('disable');
		  setTimeout (function ()
			      {
				$('#startTime, #duration').closest ('span')
				  .tooltip ('enable');
			      }, 5000);
		});
      $('#findTimeDP').datepicker ({hideIfNoPrevNext: true, minDate: 0,
				    onSelect: findTimeSelect,
				    dateFormat: 'yy-mm-dd'});
      $('#closeButton').button ({icons: {primary: 'ui-icon-close'},
				 text: false});
      $('#findTime').hide ();
    });
}

function dynamicLoad (selector, url, nomsg)
{
  if (nomsg == undefined || !nomsg)
    $(selector).html ('<center><h1>... please wait ...</h1></center>');

  $(selector).load (encodeURI (url));
}

function DisplayProperties (obj)
{
  var names = '';

  for (var name in obj) names += name + '\n';

  alert (names);
}
