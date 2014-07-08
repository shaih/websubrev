/**
   ui.js provides some user interface convenience
**/
/*jslint browser: true */
/*global $, updateBox */

function clickableNote(text)
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

function savingNote(text)
{
  $(".notice").remove();
  text = text || 'Saving';
  var box = $("<div class=\"saving notice\">"+text+"</div>");
  box.appendTo("body");
}

function checkform(e) // jquery function, e is the onsubmit event
{
  var errors = 0;
  if (typeof noCheck !== 'undefined' && noCheck) { // avoid check
    noCheck=false;
    return true;
  }
  $(".notice").remove();
  $(this).find('.required').map(function() { // check required fields
    if( !$(this).val() ) {
      $(this).addClass('error');
      errors++;
    } else {
      $(this).removeClass('error');
    }   
  });

  if (errors > 0) {
    e.preventDefault();
    $(this).find('.error').first().focus();
    clickableNote("Please fill all required fields");
  }
  else { // All required fields are there, check other stuff

    var authList = $("li.oneAuthor"); // check the list of authors, if exists
    if (authList.length > 0) {
      var frstAuth = authList.first().find('input.author');
      var frstName = frstAuth.val();
      // check if the first author name contains a list
      if (frstName.indexOf(' and ')>=0 || frstName.indexOf(';')>=0 || frstName.indexOf(',')>=0) {
	frstAuth.focus();
	var conf = confirm('First author-name appears to be a list, are you sure you have one author-name per line?');
	if (conf != true) e.preventDefault();
      }
    }
  }
}

(function () {
    "use strict";
    var setup_subtract, setup_multiple_inputs, setup_download, 
        setup_page_select, setup_forms;

    setup_subtract = function () {
        var list, count;
        $(".multiple-inputs .remove").on("click", function () {
            list = this.parentNode.parentNode;
            count = list.children.length;
            if (count > 2) {
                $(this.parentNode).remove();
            }
        });
    };
    setup_multiple_inputs = function () {
        $(".multiple-inputs .add").on("click", function () {
            var parent = this.parentNode;
            var name = $(parent).attr("data-name");
            var limit = parseInt($(parent).attr("data-limit"), 10);
            var index = parseInt($(parent).attr("data-index"), 10);
            if (limit) {
                var count = parent.children.length;
                if (count >= (limit + 1)) {
                    return;
                }
            }
            $(parent).attr("data-index", index + 1);
            //Create the new element
            var template = $(parent).attr("data-item-template");
            template = template.replace(/\|name\|/g, name);
            template = template.replace(/\|index\|/g, index);
            var item = $("<div class='item'>" + template + "</div>");
            item.insertBefore(this);
            setup_subtract();
        });
    };
    setup_download = function () {
        $(".select-all").on("click", function () {
            $.each($(".download"), function (id, itm) {
                $(itm).prop("checked", true);
            });
        });
        $(".deselect-all").on("click", function () {
            $.each($(".download"), function (i, itm) {
                $(itm).prop("checked", false);
            });
        });
        $(".download-btn").on("click", function () {
            var frm = $("<form style='display:none;'>");
            frm.attr("action", "archive.php");
            frm.attr("method", "POST");
            $.each($(".download"), function (i, itm) {
                if ($(itm).prop("checked")) {
                    frm.append($(itm).clone());
                }
            });
            frm.append($("<input type='submit' />"));
            frm.appendTo('body').submit();
        });
    };
    
    setup_page_select = function () {
        var update_input = function (container) {
            var name = container.attr("data-name");
            $(".hidden-selects", container).empty();
            $(".selected option", container).each(function(i, itm) {
      	        var hidden = 
          	    $("<input type='hidden' name='"+name+"[]' value='"+$(itm).val()+"'/>");
                $(".hidden-selects", container).append(hidden);
            });
        };
        
        $('.many-select .add').on("click", function () {
            var container = $(this.parentNode);
            $('.available option:selected', container).remove().appendTo('.selected', container);
            update_input(container);  
        });
        
        $('.many-select .remove').on("click", function () {
            var container = $(this.parentNode);     
     	    $('.selected option:selected', container).remove().appendTo('.available', container);
            update_input(container);
        });
    };
    
    setup_forms = function () {
        $(".send-form").on("click", function() {
            var frmId = $(this).attr("data-form");
            $("#"+frmId).submit();
        });
    };
    
    $(document).ready(function() {
        setup_multiple_inputs();
        setup_subtract();
        setup_download();
        setup_page_select();
        setup_forms();
	$('form').submit(checkform); // attach form validation to all forms
    });
}).call(this);

