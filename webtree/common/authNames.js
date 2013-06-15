function setLastURLparam(link, val)
{
  var url = link.attr('href');
  if (url!==undefined) {
    var lastEq = url.lastIndexOf("=");
    if (lastEq>0) url = url.substr(0,lastEq+1)+ val;
    else          url = url+ '?authorkey='+ val;
    link.attr('href',url);
  }
  return link;
}

var autoComParams = {
    'source': [{id:1, value:'@ABCD'},
	       {id:2, value:'@EFGH'},
	       {id:3, value:'@STUV'},
	       {id:4, value:'@WXYZ'}], 
    // replace the above with something useful, e.g. 'source': './query.php',

    'minLength': 3,
    'scrollHeight': 200,
    'contentType': "application/json; charset=utf-8",
    'max': 50,
    'select': function(event, ui) {
      var inp = $(this.parentNode).children('.authID');
      inp.val(ui.item.id); // insert the new author ID
      inp.change();        // trigger a change event
    }
};

function changeID()
{
  var inp = $(this);
  var value = inp.val().trim();
  var link = inp.parent().children('.authLink');
  var box = inp.parent().children('.authChk');
  // Check/uncheck box
  if (value) { // nonempty value
    if (link) setLastURLparam(link,value).show(); // Fix the link and show it
    if (box)  box.prop('checked','checked');      // check the checkbox
  } else {     // empty value
    if (link) link.hide();               // hide the link
    if (box)  box.prop('checked',false); // uncheck the checkbox
  }
}

function moreAuthors(e)
{
  e.preventDefault();
  var nAuthors = parseInt(this.rel,10);  // how many authors we have so far

  var lastAuthor = $('#authorList li:last-child'); // the last author
  // alert(lastAuthor.prop("nodeName"));
  var newAuthor = lastAuthor.clone(false); // add another author
  newAuthor.children('.required').removeClass('required'); // not required
  newAuthor.children('.error').removeClass('error');       // nor error
  newAuthor.find('.author').autocomplete(autoComParams);   // set autocomplete
  newAuthor.find('input,hidden').val('');   // remove input values
  newAuthor.insertAfter(lastAuthor); // finally place it after the last one

  this.rel = nAuthors +1;  // update the link
  setLastURLparam($(this),nAuthors+4);
}

function setHandlers($)
{
  $('.moreAuthors').click(moreAuthors);     // add more authors
  $('.author').autocomplete(autoComParams); // autocomplete author names
  $('.authID').change(changeID);            // update link with authID param
}
$(setHandlers); // call the setHandlers function when the document is ready
