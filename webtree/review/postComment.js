function clickableMsg(text)
{
  $(".notice").remove();
  text = text || 'Saved';
  var box = $("<div class=\"saved notice\">"+text+"</div>");
  var btn = $("<button class='remove-notice' type='button'>X</button>");
  btn.on("click", function () {
    $(this.parentNode).remove();
  });
  btn.appendTo(box);
  box.appendTo('body');
}

function ajaxPostComment(form)
{
  //tell server we're using ajax, and the last postID on this page
  var postData= $(form).serialize()+'&ajax=true&lastSaw='
	+window.params.lastSaw+'&pageWidth='+window.params.pageWidth;

  var theURL = form.action.split("?")[0]; // remove query parameters
  var parentDiv = $(form).parent();       // div containing the form
  var grandpaDiv = $(parentDiv).parent(); // div containing all the discussion

  // Put a yellow box saying that w are saving the post
  $(".notice").remove();
  var box = $("<div class=\"saving notice\">Saving</div>");
  box.appendTo("body");
  // make an Ajax call to set the status
  $.ajax({type: 'POST',
	  url: theURL,
	  data: postData,
	  success: function(post) {
	    $(".notice").remove();
	    if (form.id == 'replyToX') { // Replicate the last div
	      parentDiv.before(post.html);
	      $(form.subject).val('');
	    }
	    else {
              form.className="hidden";
	      parentDiv.after(post.html);
	    }
	    window.params.lastSaw = post.postId;
	    $('textarea').val('');
	    if (post.hasNew) {
	      clickableMsg('Discussion board has new messages, refresh this page to get them');
	    }
	  },
	  error: function() { // on failure, complain to the user
	    $(".notice").remove();
	    alert('Cannot post comment, communication to server failed');
	  }
  });
  return false;
}
