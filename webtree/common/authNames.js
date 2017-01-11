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
  var n2add = 1; // how many authors to add
  if (typeof(e.data.n2add) != 'undefined') n2add = parseInt(e.data.n2add);

  var nAuthors = parseInt(this.rel,10);  // how many authors we have so far
  // find the last author
  var lastAuthor = $(this).prev('.authorList').children('li:last-child');
  // alert(lastAuthor.prop("nodeName"));
  for (var i=0; i<n2add; i++) {
    var newAuthor = lastAuthor.clone(false); // add another author
    newAuthor.children().removeClass('required error');// not required nor error
    newAuthor.find('input,hidden').val(''); // remove input values
    newAuthor.find('.ui-helper-hidden-accessible').remove();// remove helper if exists
    newAuthor.find('.author').autocomplete(autoComParams); // set autocomplete
    newAuthor.find('.authLink').hide();         // hide link
    newAuthor.find('.authID').change(changeID); // update link with authID param
    newAuthor.insertAfter(lastAuthor); // finally place it after the last one
    lastAuthor = newAuthor;
  }
  nAuthors += n2add
  this.rel = nAuthors;
  setLastURLparam($(this),nAuthors+n2add); // update the link
}

function setHandlers($)
{
  var nToAdd = 1; // how many fields to add when clicking the more-authors link
  if (typeof(numToAdd) != 'undefined') {
      nToAdd = numToAdd; // if this was defined externally, then use it
  }

  $('.moreAuthors').click({'n2add':nToAdd},moreAuthors); // add more authors
  $('.author').autocomplete(autoComParams); // autocomplete author names
  $('.authID').change(changeID);            // update link with authID param
}
$(setHandlers); // call the setHandlers function when the document is ready
