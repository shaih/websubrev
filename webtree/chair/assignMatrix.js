/** assignMatrix.js: manipulate the assignment matrix interface
 **/

function initMatrix()
{
    $('.jsEnabled').show();
    // Check for the hidden recompute box before recomputing everything
    var recomp = document.getElementById('recompMatrix');
    if (recomp==null || !recomp.checked) return true;

    clickableNote('Recomputing matrix: this could take some time');
    var revId;
    var subId;
    // initialize a records for all reviewers
    for (revId=minRevId; revId<=maxRevId; revId++)
        reviewers[revId] = {'load': 0, 'wanted': 0, 'match': 0};

    for (subId=minSubId; subId<=maxSubId; subId++) {
        // check that the submission is actually on the page
        if (document.getElementById('subSum_'+subId)==null) continue;
        var subsum = 0;
	for (revId=minRevId; revId<=maxRevId; revId++) {
            chk = document.getElementById('chk_'+subId+'_'+revId);
            if (chk==null) continue;

            // update the reviewer's record
            pref = document.getElementById('prf_'+subId+'_'+revId);
            pref = (pref!=null)? pref.innerHTML: 3;
            if (chk.checked) {
                subsum++;
                reviewers[revId].load++;
                if (pref > 3) reviewers[revId].match++;
                else if (pref < 3) reviewers[revId].match--;
            }
            if (pref>3) reviewers[revId].wanted++;
	}
        document.getElementById('subSum_'+subId).innerHTML = subsum;
    }

    // update the headers and smilies on the page
    for (revId=minRevId; revId<=maxRevId; revId++) updateColumn(revId);

    $(".notice").remove(); // remove the clickable note

    return true;
}

function updateBox(box)
{
    // Something changed, but the server does not know about it. Remember
    // this so we know to re-compute all if the page is ever reloaded
    var recomp = document.getElementById('recompMatrix');
    if (recomp) {
	var saveRecomp = recomp.checked;
	recomp.checked= "checked";
    }

    // Get the submission-ID, reviewer-ID, and reviewer preference
    var i = box.name.indexOf('_',2);     // the name is a_subId_revId
    var subId = box.name.substring(2,i);
    var revId = box.name.substring(i+1);
    var pref = document.getElementById('prf_'+subId+'_'+revId);
    if (pref!=null) pref = pref.innerHTML;
    else            pref = 3;

    // update the reviewer's record
    var subSum = document.getElementById('subSum_'+subId);
    var sum = subSum.innerHTML;
    if (box.checked) { // checkbox checked
        sum++;
	reviewers[revId].load++; 
	if (pref>3) reviewers[revId].match++;
	else if (pref<3) reviewers[revId].match--;
    } else {            // checkbox cleared
        sum--;
	reviewers[revId].load--; 
	if (pref>3) reviewers[revId].match--;
	else if (pref<3) reviewers[revId].match++;
    }

    // update load/happiness everywhere on the page
    updateColumn(revId);
    subSum.innerHTML = sum;

    // make an asynchronous call to the server to update this assignment. If
    // succeeds then we are synchronyzed again and can reset the recomp value.
    var assign = box.checked? 1 : 0;
    data = 'checkbox=true&revId='+revId+'&subId='+subId+'&assign='+assign;
    $.post('ajaxMatrix.php', data, 
	   function(){ if (recomp!=null) recomp.checked= saveRecomp; }
	  );
    return true;
}

function updateColumn(revId)
{
    // check that the reviewer is actually on this page
    if (document.getElementById('hdr'+revId+'_0')==null) return;

    var load = reviewers[revId].load;
    var match = reviewers[revId].match;
    var wanted = reviewers[revId].wanted;
    var avg1 = (load>0)  ? (match/load)  : null;
    var avg2 = (wanted>0)? (match/wanted): null;
    var happy = null;
    if (avg1!=null && avg2!=null) {
	happy = Math.round(Math.max(avg1,avg2)*100);
	if (happy<0) happy=0;
	else if (happy>100) happy=100;
    }

    var i;
    for (i=0; i<numHdrIdx; i++) {
	var th = document.getElementById('hdr'+revId+'_'+i);
	if (th != null) {
            if (happy==null) {
		th.innerHTML = load;
		th.title = 'Assigned: '+load;
	    }
	    else {
		th.innerHTML = load+'('+happy+')';
		th.title = 'Assigned: '+load+', happy: '+happy+'%';
	    }
	}
    }
    var src;
    if (happy == null)  src = '../common/empty.gif';
    else if (happy>85) src = '../common/laugh.gif';
    else if (happy>75) src = '../common/ok.gif';
    else if (happy>65) src = '../common/sad.gif';
    else                src = '../common/angry.gif';
    for (i=0; i<2; i++) {
        var smily = document.getElementById('smily'+revId+'_'+i);
        if (smily != null) {
            smily.src = src;
            if (happy == null) smily.title ='';
            else               smily.title ='happy: '+happy+'%'; 
        }
    }    
}
