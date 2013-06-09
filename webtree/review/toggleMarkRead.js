function ajaxToggleMarkRead(toggleObj)
{
  // Get the submission-ID and current markRead status, the subId is obtained
  // from the element id ('toggleNNN'), and we use the rel attribute to hold
  // the current markRead status
  var subId = toggleObj.id.substring(6); // get the submission-ID
  var markRead = toggleObj.rel;          // get the markRead status

  // The discuss button image is inside an anchor sibling of the toggle object
  var discuss = $(toggleObj).siblings('a').children('img');

  // Prepare the URL string for the Ajax call
  var url='toggleMarkRead.php?subId='+subId+'&markRead='+markRead+'&ajax=true';

  // make the actual call
  $.get(url, /*callback=*/function(data) {
    if (data.markRead=='1') {
      discuss.attr('src','../common/Discuss1.gif'); // toggle the button pic
    } else {
      discuss.attr('src','../common/Discuss2.gif');
    }
    toggleObj.rel = data.markRead; // record the new status
  });
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
