function toggleDiscussButton(imageObj)
{
  if (imageObj.attr('src')=='../common/Discuss2.gif')
      imageObj.attr('src','../common/Discuss1.gif');
    else
      imageObj.attr('src','../common/Discuss2.gif');
}

function ajaxToggleMarkRead(toggleObj)
{
  // Get the submission-ID and current markRead status, the subId is obtained
  // from the element id ('toggleNNN'), and we use the rel attribute to hold
  // the current markRead status
  var subId = toggleObj.id.substring(6); // get the submission-ID
  var markRead = toggleObj.rel;          // get the markRead status

  // The discuss button image is inside an anchor sibling of the toggle object
  var discuss = $(toggleObj).siblings('a').children('img');

  // toggle the picture even before making the call for quick response
  toggleDiscussButton(discuss);

  // Prepare the URL string for the Ajax call
  var url='toggleMarkRead.php?subId='+subId+'&markRead='+markRead+'&ajax=true';

  // make the actual call, then record the new status
  $.ajax({type: 'GET',
	  url:'toggleMarkRead.php',
	  data: 'subId='+subId+'&markRead='+markRead+'&ajax=true',
	  // on success, record the new status in the rel attribute
          success:function(data) {toggleObj.rel=data.markRead;},
	  // on failure, toggle back the pucture and complain to the user
	  error: function(data) {
	      toggleDiscussButton(discuss);
	      alert('Cannot toggle read for submission '+subId+', communication to server failed');
	  }});
//  $.get(url, /*callback=*/function(data){toggleObj.rel=data.markRead;});
}

// Add a click handler for the entire document
$(document).ready(function() { $(document).click(function(e) {
    var whoClicked = e.target;

    if (whoClicked.id.substring(0,6)=='toggle') {  // handle toggle elements
      ajaxToggleMarkRead(whoClicked); // handle the toggle
      e.preventDefault();             // do not navigate away from this page
    }
  }); // end of click handler
});
