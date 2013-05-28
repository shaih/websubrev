/**
   ui.js provides some user interface convenience
**/
/*jslint browser: true */
/*global $, updateBox */
(function () {
    "use strict";
    var pad, setup_subtract, setup_multiple_inputs, setup_download, 
        setup_watch, post_comment, add_form, setup_reply, setup_post,
        get_posts_id, get_posts, render_posts, setup_discussion,
        setup_assign, setup_status, setup_page_select, set_save, set_done,
        setup_forms;

    pad = function (str, max) {
        return str.length < max ? pad("0" + str, max) : str;
    };
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
    setup_watch = function () {
        $(".watch").on("click", function () {
            var data = {subId: $(this).attr("data-subId")};
            var savethis = $(this);
            $.ajax({
                url: "ajaxWatch.php",
                data: data,
                success: function (res) {
                    var klass;
                    if (res === 1) {
                        klass = "open";
                    } else {
                        klass = "close";
                    }
                    savethis.attr("class", "watch "+klass);
                },
                dataType:"json"
            });
        });
    };
    post_comment = function (frm, parentId, callback) {
        var subId = $('#sub-id').attr("data-id");
        var data = {
            subId: subId,
            parent: parentId,
            subject: frm.elements.subject.value,
            comments: frm.elements.comments.value
        };
        $.ajax({
            url: "ajaxPost.php",
            type:"POST",
            data: data,
            success: function(res) {
                callback(res);
            },
            dataType:"json"
        });
    };
    add_form = function(itm) {
        var parent = itm.parentNode;
        var postId = $(parent).attr("data-id");
        var subject = $(parent).attr("data-subject");

        if(!(/^Re:/.exec(subject))) {
            subject = "Re: "+ subject;
        }

        //add box
        var reply = $("<form style='margin-top:20px'>");
        var title = $("<span>Subject:</span>");
        subject = $("<input type='text' name='subject' value='"+
                    subject+
                    "'>"
                   );

        var comment =
            $("<textarea style='width:100%' rows='9' name='comments'>");

        var btn =
            $("<button type='button'>Submit</button>");

        btn.on("click", function () {
            var frm = this.parentNode;
            post_comment(frm, postId, function() {
                $(frm).remove();
                get_posts(function(res){
                    $(".discussion").empty();
                    render_posts(res);
                });
            });
        });

        reply.append(title, subject, $("<br/>"), comment, btn);

        $(parent).append(reply);

        //bind action to submit button
        $(itm).off("click");
        $(itm).on("click", function() {
            reply.remove();
            $(itm).on("click", function() {
                add_form(this);
            });
        });
    };
    
    setup_reply = function () {
        $(".reply").on("click", function() {
            add_form(this);
        });
    };

    setup_post = function () {
        $(".post-btn").on("click", function () {
            var frm = this.parentNode;
            post_comment(frm, null, function() {
                frm.elements.subject.value = "";
                frm.elements.comments.value= "";
                get_posts(function(results) {
                    $(".discussion").empty();
                    render_posts(results);
                });
            });
        });
    };
    
    get_posts_id = function (subId, callback) {
        $.ajax({
            url: "ajaxDiscuss.php",
            data: {subId: subId},
            success: function(res) {
                callback(res);
            },
            dataType:"json"
        });
    };
    
    get_posts = function (callback) {
        var subId = $("#sub-id").attr("data-id");
        get_posts_id(subId, callback);
    };

    render_posts = function (results) {
        var container = $('.discussion');
        if(!container) {
            return;
        }
        var subId = container.attr("data-subId");
        var width = container.attr("data-width");
        var can_post = container.attr("data-active");
        
        if(subId <= 0) {
            return;
        }
        $.each(results.posts, function (i, post) {
            var depth = post.depth * 20;
            var content =
                $("<div class='post' style='position: relative; left: "+
                  depth+"px; width:"+
                  (width-(depth))+"px' data-id='"+
                  post.postId+"'>");
            content.attr("data-subject", post.subject);
            //date
            var date = new Date(post.whenEntered*1000);
            var date_container = 
                $("<div class='date' style=\"margin-right:10px; float: right;\">");
            var month = parseInt(date.getMonth(), 10) + 1;
            var minutes = pad(date.getMinutes().toString(), 2);

            date_container.html(date.getDate()+
                                "-"+
                                month +
                                " "+date.getHours()+
                                ":"+minutes
                               );
            
            var reply = 
                $("<a class='reply' style='float:right' href='javascript:;'>Reply</a>");



            var edit =
                $("<a class='edit' style='float:right; margin-left: 10px;' target='_blank' href='editPost.php?postId="+post.postId+"'>Edit</a>");
            
            var subject = $("<span class='sbjct'>");
            subject.html("&#8722;&nbsp;"+post.subject);
            
            
            var comment = 
                $("<div class='comment' style='position:relative; left:12px;top:6px'>");
            var name = $("<span style='float:right; margin-right:10px ' >"+post.name+"</span>");
            
            if (post.postId > results.lastSaw) {
                subject = $("<b>").append(subject);
                date_container = $("<b>").append(date_container);
                name = $("<b>").append(name);
            }
            
            comment.html(post.comments);
            //Put it all together
            if(post.depth === 0) {
                content.append($("<hr />"));
            }

            if(window.params.edit) {
//		if(parseInt(post.mine))
		if(parseInt(post.mine) || results.is_chair)
		    content.append(edit);
            }

            if(can_post) {
                content.append(reply);
            }
            content.append(date_container);
            content.append(name);
            content.append(subject);
            content.append(comment);
            container.append(content,$("<br />"));
        });

        setup_reply();
    };
    
    setup_discussion = function () {
        if($(".discussion").length === 0) {
            return;
        }
        
        get_posts(render_posts);
    };
    set_save = function () {
        $(".notice").remove();
        var box = $("<div class=\"saving notice\">Saving</div>");
        box.appendTo("body");
    };
    set_done = function() {
        $(".notice").remove();
        var box = $("<div class=\"saved notice\">Saved</div>");
        var btn = $("<button class='remove-notice' type='button'>X</button>");
        btn.on("click", function () {
            $(this.parentNode).remove();
        });
        btn.appendTo(box);
        box.appendTo('body');
    };
    setup_assign = function () {

        var changes = {};        

        var commit_changes = function() {
            var data = {changes: changes};

            changes = {};
            set_save();
            $.ajax({
                url:"ajaxMatrix.php",
                data: data,
                type: "POST",
                success: function(res) {
                    if(!res.prefs) {
                        return;
                    }
                    $.each(res.prefs, function(subId, p) {
                        $.each(p, function(revId, q) {
                            var box = $("#chk_"+subId+"_"+revId);
                            var change = false; 
                            var assign = parseInt(q.assign, 10);
                            
                            if(assign === 1) {
                                if(!box.prop("checked")) {
                                    change = true;
                                }
                                box.prop("checked", true);
                            } else if (assign === 0) {
                                if(box.prop("checked")) {
                                    change = true;
                                }
                                box.prop("checked", false);
                            } else if (assign === -1) {
                                box.remove();
                            }
                            
                            if(change && box.get(0)) {
                                updateBox(box.get(0));
                            }
                        });
                    });
                    set_done();
                },
                dataType: "json"
            });
        };
        
        if($(".submit-assignment").length > 0) {
            $(document).on("keypress", function(e) {
                if(e.which === 13) {
                    commit_changes();
                }
            });
        }
        
        $(".assignment").on("change", function () {
            var key = $(this).attr("id");
            changes[key] = $(this).prop("checked") ? 1 : 0;
            $(this).blur();
        });
        
        $(".submit-assignment").on("click", commit_changes);
    };
    
    setup_status = function () {
        var changes = {};
        
        var commit_changes = function () {
            var data = {changes: changes};
            changes = {};
            set_save();            
            $.ajax({
                url: "ajaxStatus.php",
                data: data,
                type: "POST",
                success: function (res) {
                    
                    if(!res.data) {
                        return;
                    }
                    //Update all the inputs.
                    $.each(res.data, function(name, status) {
			//alert(name+':'+status);
                        var checked = "input[name=\""+name+"\"]:checked";
                        var next = 
                            "[name=\""+name+"\"][value=\""+status+"\"]";
                        
                        $(checked, '#setStatus').prop("checked", false);
                        $(next, '#setStatus').prop("checked", true);
                    });
                    
                    //Update the count.
                    $.each($(".summary"), function(i, itm) {
                        var name = $(itm).attr("data-status");
                        var count = 0;
                        if(res.stats[name]) {
                            count = res.stats[name];
                        }
                        $(itm).html(count);
                    });
                    set_done();
                }
            });
        };
        
        if($("#setStatus").length > 0) {
            $(document).on("keypress", function(e){
                if(e.which === 13) {
                    commit_changes();
                }
            });
        }
        
        $("#setStatus [name^=\"scrsubStts\"]").on("change", function () {
            var input = $(this);
            var name = input.attr("name");
            changes[name] = input.val();

            var status = input.attr("data-acronym");
            
            //Change the icon.
            
            var row = $(this.parentNode.parentNode);
            var cell = $(".scratch", row);
            if(cell.children().length === 0) {
                return;
            }
            
            var child = $(cell.children()[0]);
            
            var img = "../common/"+ status + ".gif";
            child.attr("src", img);
            child.attr("title", "Status: "+ input.val());
            child.attr("alt", "["+status+"]");
            child.attr("class", status + " scratch");
        });
        
        $("#setStatus [name=\"scrSubmit\"]").on("click", commit_changes);
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
        setup_watch();
        setup_discussion();
        setup_post();
        setup_assign();
        setup_status();
        setup_page_select();
        setup_forms();
    });
}).call(this);
