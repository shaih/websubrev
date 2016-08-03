function toggleImage(imageObj, img1, img2)
{
  if (imageObj.attr('src')==img1)
      imageObj.attr('src',img2);
  else
      imageObj.attr('src',img1);
}

function ajaxToggle(toggleObj, imageObj, img1, img2, url, subId)
{
  // toggle the picture even before making the call for quick response
  toggleImage(imageObj, img1, img2);

  // make the actual call, then record the new status
  $.ajax({type: 'GET',
	  url: url,
	  data: 'subId='+subId+'&current='+toggleObj.rel+'&ajax=true',
	  // on success, record the new status in the rel attribute
          success:function(data) {toggleObj.rel=data.current;},
	  // on failure, toggle back the picture and complain to the user
	  error: function(data) {
	      toggleImage(imageObj, img1, img2);
	      alert('Cannot toggle, communication to server failed. Submission-ID '+subId);
	  }});
}

function toggleMarkRead(toggleObj)
{
  // The discuss button image is inside an anchor sibling of the toggle object
  var discuss = $(toggleObj).siblings('a').children('img');
  var disImage1 = '../common/Discuss1.gif';
  var disImage2 = '../common/Discuss2.gif';

  // Get the submission-ID and current markRead status, the subId is obtained
  // from the element id ('toggleReadNNN'), and we use the rel attribute to
  // hold the current markRead status
  var subId = toggleObj.id.substring(10); // get the submission-ID

  ajaxToggle(toggleObj, discuss, disImage1, disImage2,
	     'toggleMarkRead.php', subId);
}
/*  var markRead = toggleObj.rel;          // get the markRead status

  // toggle the picture even before making the call for quick response
  toggleImage(discuss,disdussImage1,disdussImage2);

  // make the actual call, then record the new status
  $.ajax({type: 'GET',
	  url:'toggleMarkRead.php',
	  data: 'subId='+subId+'&markRead='+markRead+'&ajax=true',
	  // on success, record the new status in the rel attribute
          success:function(data) {toggleObj.rel=data.markRead;},
	  // on failure, toggle back the picture and complain to the user
	  error: function(data) {
	      toggleImage(discuss,disdussImage1,disdussImage2);
	      alert('Cannot toggle read for submission '+subId+', communication to server failed');
	  }});
*/

function toggleWatch(eyeObj)
{
  // The eye image is inside the toggle object
  var parent = $(eyeObj).parent().get(0);
  var openEye = '../common/openeye.gif';
  var shutEye = '../common/shuteye.gif';

  // Get the submission-ID and current watch status, the subId is obtained
  // from the element id ('toggleWatchNNN'), and we use the rel attribute
  // to hold the current watch status
  var subId = eyeObj.id.substring(11); // get the submission-ID
  ajaxToggle(parent, $(eyeObj), openEye, shutEye, 'toggleWatch.php', subId);
}

function submitTags(e)
{
  var tagsDiv = $(this).parent();
  var postData= $(this).serialize()+'&ajax=true';//tell server we're using ajax
  var inputTags = $(this.tags);
  var spanTags = $(this).siblings('a').children('div').children('span');
  clickableNote('Saving');   // display a 'Saving' box until the call returns
  $.post(this.action, postData, function(data) { // callback on success
      $(".notice").remove(); // remove the 'Saving' box
      inputTags.val(data);   // data is just the new list of tags

      if (!data) { // data is empty
	spanTags.css('color', 'gray');
	data = 'Click to add tags: tag1; tag2; ...';
      } else {
	spanTags.css('color', 'black');	
      }
      spanTags.html(data);
      toggleTags(tagsDiv); // hide the form, show the gray box
  });
  e.preventDefault(); 
}

function toggleTags(tagsDiv)
{
  var link = tagsDiv.children('a');
  var form = tagsDiv.children('form');
  if (form.is(':hidden')) {
    form.show();
    link.hide();
    form.get(0).tags.focus(); // children('input:text').focus();
  } else {
    form.hide();
    link.show();
  }
}

// Add a click handler for the entire document
$(document).click(function(e) {
    var whoClicked = e.target;
    if ($(whoClicked).is('.noHandle')) return true; // do not handle

    // handle toggle elements
    if (whoClicked.id.substring(0,10)=='toggleRead') {
      toggleMarkRead(whoClicked); // handle the toggle
      e.preventDefault();         // do not navigate away from this page
    }
    else if (whoClicked.id.substring(0,11)=='toggleWatch') {
      toggleWatch(whoClicked); // handle the toggle
      e.preventDefault();      // do not navigate away from this page
    }
    else if ($(whoClicked).closest('div.showTags').length>0) {
      if (whoClicked.nodeName!='INPUT') {
        toggleTags($(whoClicked).closest('div.showTags'));
        e.preventDefault();      // do not navigate away from this page
      }
    }
}); // end of click handler

$(document).ready(function() {
  $('form.tagsForm').submit(submitTags);
});
