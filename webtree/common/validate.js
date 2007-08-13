/** validate.js functions to validate user input in forms
 **/

function matchEmlPattern(txt)
{
  var pat1 = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,})+$/;
  var pat2 = /^[^@<>]*<\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,})+>$/;
  return ((txt=="") || pat1.test(txt) || pat2.test(txt));
}

function checkEmail( fld )
{
  fld.value = fld.value.replace(/^\s+/g,'').replace(/\s+$/g,''); // trim
  var pat1 = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,})+$/;
  var pat2 = /^[^@<>]*<\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,})+>$/;
  if (matchEmlPattern(fld.value)) return true;

  alert("Not a valid email format");
  fld.focus();
  fld.select();
  return false;
}

function checkEmailList( fld )
{
  var emlList = fld.value.split(",");
  for(i=0; i<emlList.length; i++) {
    var address = emlList[i];
    address = address.replace(/^\s+/g,'').replace(/\s+$/g,''); // trim
    if (!matchEmlPattern(address)) {
      alert("Not a valid mailing-list format");
      fld.focus();
      fld.select();
      return false;
    }
  }
  return true;
}

function checkInt( fld, mn, mx )
{
  if (typeof(mn) == 'undefined') { mn = -9999999; }
  if (typeof(mx) == 'undefined') { mx =  9999999; }

  fld.value = fld.value.replace(/^\s+/g,'').replace(/\s+$/g,''); // trim
  if (fld.value == "") return true;  // allow empty field

  var pat = /^-?[0-9]+$/;
  if((pat.test(fld.value)==false) || (fld.value<mn) || (fld.value>mx)) {
    alert("Field must contain an integet between "+mn+" and "+mx);
    fld.focus();
    fld.select();
    return false ;
  }
  return true ;
}
