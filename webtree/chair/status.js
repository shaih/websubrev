/** status.js: Ajax support for setting the status of submissions
 **/

var st2code = {'None':'NO', 'Reject':'RE', 'Perhaps Reject':'MR',
	       'Needs Discussion':'DI', 'Maybe Accept':'MA', 'Accept':'AC'};
var code2st = {'NO':'None', 'RE':'Reject', 'MR':'Perhaps Reject',
	       'DI':'Needs Discussion','MA':'Maybe Accept','AC':'Accept'};

$(document).ready(function() {
  $('.jsEnabled').show();
  $('.statusRadio').change(function(e) {    // handle radio-button changes
      if (this.name.substring(0,10)!='scrsubStts')
	  return true;
      var subId = this.name.substring(10);  // name is scrsubSttsNNN
      var newStatus = $(this).filter(':checked').val();// The selected value
      var stCodeObj = document.getElementById('sc'+subId)
      var oldStatus = stCodeObj.title;

      // update the scratch status at the server
      $.post('ajaxStatus.php', 
	     {'subId':subId, 'status':newStatus, 'updateOne':'true'},
	     function(data) { // callback on success
		 // data = {status: <newStatus>, html: <status HTML code>}
		 newStatus = data.status; // just to make sure

		 // update the status mark for this submission
		 stCodeObj.innerHTML = data.html;
		 stCodeObj.title = newStatus;

		 // update the number of submissions from each status
		 var newSumObj = document.getElementById('scSum'+st2code[newStatus]);
		 var oldSumObj = document.getElementById('scSum'+st2code[oldStatus]);
		 oldSumObj.innerHTML = parseInt(oldSumObj.innerHTML,10) -1;
		 newSumObj.innerHTML = parseInt(newSumObj.innerHTML,10) +1;
	     }); 
  });
});
