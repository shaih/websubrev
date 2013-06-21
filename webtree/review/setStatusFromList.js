function ajaxSetStatus(form)
{
  // Get the submission-ID from the element id ('sttsFormNNN'), and the new
  // status from the form itself
  var subId = form.id.substring(8);  // get the submission-ID
  var postData = $(form).serialize()+'&ajax=true';//tell server we're using ajax
  var theURL = form.action.split("?")[0]; // remove query parameters

  // Put a yellow box saying that w are saving the status
  $(".notice").remove();
  var box = $("<div class=\"saving notice\">Saving</div>");
  box.appendTo("body");
  // make an Ajax call to set the status
  $.ajax({type: 'POST',
	  url: theURL,
	  data: postData,
	  success: function(changes) {
	    $(".notice").remove();
	    if (changes[subId])
	      $('#statCode'+subId).html(changes[subId]);
	  },
	  error: function() { // on failure, complain to the user
	    $(".notice").remove();
	    alert('Cannot change status of submission '+subId+', communication to server failed');
	  }
  });
  return false;
}
