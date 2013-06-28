//表情数据
var emotion_data = '<a href="javascript:;" title="[呵呵]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/eb/smile.gif"></a><a href="javascript:;" title="[嘻嘻]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/c2/tooth.gif"></a><a href="javascript:;" title="[哈哈]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/6a/laugh.gif"></a><a href="javascript:;" title="[爱你]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/7e/love.gif"></a><a href="javascript:;" title="[晕]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/a4/dizzy.gif"></a><a href="javascript:;" title="[泪]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/d8/sad.gif"></a><a href="javascript:;" title="[馋嘴]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/b8/cz_org.gif"></a><a href="javascript:;" title="[抓狂]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/4d/crazy.gif"></a><a href="javascript:;" title="[哼]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/19/hate.gif"></a><a href="javascript:;" title="[可爱]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/9c/tz_org.gif"></a><a href="javascript:;" title="[怒]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/57/angry.gif"></a><a href="javascript:;" title="[汗]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/13/sweat.gif"></a><a href="javascript:;" title="[困]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/8b/sleepy.gif"></a><a href="javascript:;" title="[害羞]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/05/shame_org.gif"></a><a href="javascript:;" title="[睡觉]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/7d/sleep_org.gif"></a><a href="javascript:;" title="[钱]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/90/money_org.gif"></a><a href="javascript:;" title="[偷笑]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/7e/hei_org.gif"></a><a href="javascript:;" title="[酷]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/40/cool_org.gif"></a><a href="javascript:;" title="[衰]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/af/cry.gif"></a><a href="javascript:;" title="[吃惊]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/f4/cj_org.gif"></a><a href="javascript:;" title="[闭嘴]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/29/bz_org.gif"></a><a href="javascript:;" title="[鄙视]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/71/bs2_org.gif"></a><a href="javascript:;" title="[挖鼻屎]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/b6/kbs_org.gif"></a><a href="javascript:;" title="[花心]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/64/hs_org.gif"></a><a href="javascript:;" title="[鼓掌]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/1b/gz_org.gif"></a><a href="javascript:;" title="[失望]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/0c/sw_org.gif"></a><a href="javascript:;" title="[思考]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/e9/sk_org.gif"></a><a href="javascript:;" title="[生病]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/b6/sb_org.gif"></a><a href="javascript:;" title="[亲亲]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/8f/qq_org.gif"></a><a href="javascript:;" title="[怒骂]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/89/nm_org.gif"></a><a href="javascript:;" title="[太开心]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/58/mb_org.gif"></a><a href="javascript:;" title="[懒得理你]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/17/ldln_org.gif"></a><a href="javascript:;" title="[右哼哼]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/98/yhh_org.gif"></a><a href="javascript:;" title="[左哼哼]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/6d/zhh_org.gif"></a><a href="javascript:;" title="[嘘]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/a6/x_org.gif"></a><a href="javascript:;" title="[委屈]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/73/wq_org.gif"></a><a href="javascript:;" title="[吐]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/9e/t_org.gif"></a><a href="javascript:;" title="[可怜]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/af/kl_org.gif"></a><a href="javascript:;" title="[打哈气]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/f3/k_org.gif"></a><a href="javascript:;" title="[做鬼脸]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/88/zgl_org.gif"></a><a href="javascript:;" title="[握手]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/0c/ws_org.gif"></a><a href="javascript:;" title="[耶]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/d9/ye_org.gif"></a><a href="javascript:;" title="[good]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/d8/good_org.gif"></a><a href="javascript:;" title="[弱]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/d8/sad_org.gif"></a><a href="javascript:;" title="[不要]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/c7/no_org.gif"></a><a href="javascript:;" title="[ok]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/d6/ok_org.gif"></a><a href="javascript:;" title="[赞]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/d0/z2_org.gif"></a><a href="javascript:;" title="[来]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/40/come_org.gif"></a><a href="javascript:;" title="[蛋糕]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/6a/cake.gif"></a><a href="javascript:;" title="[心]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/6d/heart.gif"></a><a href="javascript:;" title="[伤心]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/ea/unheart.gif"></a><a href="javascript:;" title="[钟]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/d3/clock_org.gif"></a><a href="javascript:;" title="[猪头]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/58/pig.gif"></a><a href="javascript:;" title="[咖啡]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/64/cafe_org.gif"></a><a href="javascript:;" title="[话筒]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/1b/m_org.gif"></a><a href="javascript:;" title="[干杯]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/bd/cheer.gif"></a><a href="javascript:;" title="[绿丝带]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/b8/green.gif"></a><a href="javascript:;" title="[蜡烛]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/cc/candle.gif"></a><a href="javascript:;" title="[微风]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/a5/wind_org.gif"></a><a href="javascript:;" title="[月亮]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/b9/moon.gif"></a><a href="javascript:;" title="[织]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/41/zz2_org.gif"></a><a href="javascript:;" title="[围观]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/f2/wg_org.gif"></a><a href="javascript:;" title="[威武]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/70/vw_org.gif"></a><a href="javascript:;" title="[奥特曼]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/bc/otm_org.gif"></a><a href="javascript:;" title="[宅]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/d7/z_org.gif"></a><a href="javascript:;" title="[帅]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/36/s2_org.gif"></a><a href="javascript:;" title="[跳舞花]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/70/twh_org.gif"></a><a href="javascript:;" title="[围脖]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/3f/weijin_org.gif"></a><a href="javascript:;" title="[温暖帽子]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/f1/wennuanmaozi_org.gif"></a><a href="javascript:;" title="[手套]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/72/shoutao_org.gif"></a><a href="javascript:;" title="[雪]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/00/snow_org.gif"></a><a href="javascript:;" title="[雪人]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/d9/xx2_org.gif"></a><a href="javascript:;" title="[左抱抱]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/54/left_org.gif"></a><a href="javascript:;" title="[右抱抱]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/0d/right_org.gif"></a><a href="javascript:;" title="[礼物]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/c4/liwu_org.gif"></a><a href="javascript:;" title="[爱心传递]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/c9/axcd_org.gif"></a><a href="javascript:;" title="[照相机]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/33/camera_org.gif"></a><a href="javascript:;" title="[落叶]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/79/yellowMood_org.gif"></a><a href="javascript:;" title="[白云]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/ff/y3_org.gif"></a><a href="javascript:;" title="[给力]"><img width="22px" height="22px" src="http://img.t.sinajs.cn/t3/style/images/common/face/ext/normal/c9/geili_org.gif"></a>';

//上传图片数据
var img_upload_data = '<form id="imguploadform" action="../../raw/upload_img.php" method="post" enctype="multipart/form-data" target="form-target">' +
                        '<input id="imgtext" type="text"/>' +
                        '<input id="imgbutton" class="btn btn-info" type="button" value="选择图片"/>' +
                        '<input id="imgfile" type="file" name="imgfile" size="35.5" onchange="startUpload();">' +
                        '<span id="show_status"></span> ' +
                      '</form>' +
                      '<iframe style="width:0; height:0; border:0;display: none" name="form-target"></iframe>';

function startUpload() {
    document.getElementById("imgtext").value=document.getElementById("imgfile").value;
    var status = '正在上传中...';
    $("show_status").html(status);
    $("#imguploadform").submit();
    return true;
}

function stopUpload(imgurl){
    $("#show_status").html("上传成功");
    $("#show_status").parent().parent().parent().prev().find('span').html(imgurl);
}

function toggleById(id)
{
    $('#' + id).slideToggle(100);
}
function checkText(id, btnid, showid, count) {
	var v = $.trim( $('#' + id).val() );
	var left = count - getSinaWeiboTextLength(v);
	if (left >= 0)
    {
        $('#' + showid).html('还能输入<em>'+left+'</em>字');
        $('#' + btnid).removeClass('disabled');
        $('#' + btnid).removeAttr('disabled');
    }
	else
    {
        $('#' + showid).html('已超出<em style="color:red;">'+Math.abs(left)+'</em>字');
        $('#' + btnid).addClass('disabled');
        $('#' + btnid).attr('disabled','disabled');
    }
    return left>=0 && v;
}

/* 获取新浪微博文本长度，需要考虑把url作为短url来处理
 * 1)目前新浪微博的的短URL中的url id部分是7位，
 * 2)匹配时，增加了一个空格，所以要考虑补充空格，同时要考虑url是原字符串中的最右内容
 * 3)如果是http://weibo.com/ 下的url，则不做短url处理
 * */
function getSinaWeiboTextLength(str)
{
    //url正则匹配
    var reg = /((http|https):\/\/(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|"|\'|:|\<|$|\.\s)/gi;
    str = str.replace(reg,function(url){
        var last_char = url.charAt(url.length -1 );
        if(last_char !=' '){last_char='';}
        var new_url = url;
        if(url.indexOf('http://weibo.com/') != 0)
        {
            new_url = 'http://t.cn/1234567' + last_char;
        }
        return new_url;
    });

    var matcher = str.match(/[^\x00-\xff]/g);
    var wlen  = (matcher && matcher.length) || 0;
    return Math.ceil((str.length + wlen)/2);
}

function calWbText(text, count) {
	var cLen=0;
	var matcher = text.match(/[^\x00-\xff]/g), wlen  = (matcher && matcher.length) || 0;
	return Math.floor((count*2 - text.length - wlen)/2);
}

function insertTopic(topic, id){
	if(!topic) topic = "请在这里输入自定义话题";
	
	var inputor = document.getElementById(id);
	var hasCustomTopic = new RegExp('#请在这里输入自定义话题#').test(inputor.value);
	var text = topic, start=0,end=0;
	
	inputor.focus();
	
	if (document.selection) {
		var cr = document.selection.createRange();
		//获取选中的文本
		text = cr.text || topic;
	
		//内容有默认主题，且没选中文本
		if (text == topic && hasCustomTopic) {
			start = RegExp.leftContext.length + 1;
			end   =   topic.length;
		}
		//内容没有默认主题，且没选中文本
		else if(text == topic) {
			cr.text = '#' + topic + '#';
			start = inputor.value.indexOf('#' + topic + '#') + 1;
			end   = topic.length;
		}
		//有选中文本
		else {
			cr.text = '#' + text + '#';
		}
	
		if (text == topic) {
			cr = inputor.createTextRange();
			cr.collapse();
			cr.moveStart('character', start);
			cr.moveEnd('character', end);
		}
	
		cr.select();
	}
	else if (inputor.selectionStart || inputor.selectionStart == '0') {
		start = inputor.selectionStart;
		end = inputor.selectionEnd;
	
		//获取选中的文本
		if (start != end) {
			text = inputor.value.substring(start, end);
		}
	
		//内容有默认主题，且没选中文本
		if (hasCustomTopic && text == topic) {
			start = RegExp.leftContext.length + 1;
			end = start + text.length;
		}
		//内容没有默认主题，且没选中文本
		else if (text == topic) {
			inputor.value = inputor.value.substring(0, start) + '#' + text + '#' + inputor.value.substring(end, inputor.value.length);
			start++;
			end = start + text.length;
		}
		//有选中文本
		else {
			inputor.value = inputor.value.substring(0, start) + '#' + text + '#' + inputor.value.substring(end, inputor.value.length);
			end = start = start + text.length + 2;
		}
	
		//设置选中范
		inputor.selectionStart = start;
		inputor.selectionEnd = end;
	}
	else {
		inputor.value += '#' + text + '#';
	}
	
	checkText(id, 140);
}


function showFace(showid) {
	$("#" + showid + "_wrap").slideToggle(100);//toggle
        $("#" + showid + "_wrap").html(emotion_data);
        $("#" + showid + "_wrap a").click(function(){
            insertFace(showid, $(this).attr("title"));
        });

    //doane();
	
//	$(document.body).click(function(e) {
//		$("#" + showid + "_wrap").hide();
//	});
//	$(document.body).scroll(function(e) {
//		$("#" + showid + "_wrap").hide();
//	});
	
}

function showImgUpload(showid) {
    $("#" + showid + "_img").slideToggle(100);//toggle
    $("#" + showid + "_img").html(img_upload_data);
}

function doane(event) {
	e = event ? event : window.event;
	if(!e) e = getEvent();
	if(e && $.browser.msie) {
		e.returnValue = false;
		e.cancelBubble = true;
	} else if(e) {
		e.stopPropagation();
		e.preventDefault();
	}
}
function getEvent() {
	if(document.all) return window.event;
	func = getEvent.caller;
	while(func != null) {
		var arg0 = func.arguments[0];
		if (arg0) {
			if((arg0.constructor  == Event || arg0.constructor == MouseEvent) || (typeof(arg0) == "object" && arg0.preventDefault && arg0.stopPropagation)) {
				return arg0;
			}
		}
		func=func.caller;
	}
	return null;
}


function insertFace(showid, text) {
	var obj = document.getElementById(showid);
	selection = document.selection;
	checkFocus(showid);
	if(!isUndefined(obj.selectionStart)) {
		var opn = obj.selectionStart + 0;
		obj.value = obj.value.substr(0, obj.selectionStart) + text + obj.value.substr(obj.selectionEnd);
	} else if(selection && selection.createRange) {
		var sel = selection.createRange();
		sel.text = text;
		try{sel.moveStart('character', -strlen(text));}
		catch(e){}
	} else {
		obj.value += text;
	}
	
	var maxlen = 140;
	checkText(showid, maxlen);
}

function isUndefined(variable) {
	return typeof variable == 'undefined' ? true : false;
}

function checkFocus(target) {
	var obj = document.getElementById(target);
	if(!obj.hasfocus) {
		obj.focus();
	}
}


//邮箱验证
function checkEmail(id){
    var email = $("#" + id).val();
    var RegExp = /^[\w+._-]+@[\w_-]+(\.[\w]+)+$/gi;
    if(RegExp.test(email)){
        $("#" + id).addClass('true');
        $("#" + id).next().html("<img src='../../../images/icon_ok.png'/>");
    }else{
        $("#" + id).removeClass('true');
        $("#" + id).next().html("<img src='../../../images/icon_error.png'/>请输入正确的邮箱地址");
    }
}

//密码验证
function checkPasswordConfirm(passwordid,passwordconfirmid){
    var password = $("#" + passwordid).val();
    var passwordConfirm = $("#" + passwordconfirmid).val();

    if((password != passwordConfirm) || password == '' || passwordConfirm == '' || password.length < 6 || password.length > 30 || passwordConfirm.length < 6 || passwordConfirm.length > 30){
        $("#" + passwordid).removeClass('true');
        $("#" + passwordid).next().html("<img src='../../../images/icon_error.png'/>密码由6-30位字母和数字组成,区分大小写");
        $("#" + passwordconfirmid).next().html("<img src='../../../images/icon_error.png'/>请按照要求输入密码");
    }else{
        $("#" + passwordid).addClass('true');
        $("#" + passwordconfirmid).next().html("<img src='../../../images/icon_ok.png'/>");
        $("#" + passwordid).next().html("<img src='../../../images/icon_ok.png'/>");
    }
}

//联系人姓名验证
function checkName(lastNameid,firstNameid){
    var lastName = $("#" + lastNameid).val();
    var firstName = $("#" + firstNameid).val();
    if(lastName == '' || firstName == ''){
        $("#" + firstNameid).removeClass('true');
        $("#" + firstNameid).next().html("<img src='../../../images/icon_error.png'/>请输入正确的联系人姓名");
    }else{
        $("#" + firstNameid).addClass('true');
        $("#" + firstNameid).next().html("<img src='../../../images/icon_ok.png'/>");
    }
}

//支付宝帐号验证
function checkAlipay(id){
    var alipay = $("#" + id).val();
    var RegExp = /^([\w+._-]+@[\w_-]+(\.[\w]+)+)|([0-9]{11})$/gi;
    if(RegExp.test(alipay)){
        $("#" + id).addClass('true');
        $("#" + id).next().html("<img src='../../../images/icon_ok.png'/>");
    }else{
        $("#" + id).removeClass('true');
        $("#" + id).next().html("<img src='../../../images/icon_error.png'/>请输入支付宝账户");
    }
}

////公司或店铺主页验证
//function checkHomepage(id){
//    var site = $("#" + id).val();
//    var RegExp = /^.+\..+$/gi;
//    if(RegExp.test(site)){
//        $("#" + id).addClass('true');
//        $("#" + id).next().html("<img src='../../../images/icon_ok.png'/>");
//    }else{
//        $("#" + id).removeClass('true');
//        $("#" + id).next().html("<img src='../../../images/icon_error.png'/>请输入公司或店铺主页");
//    }
//}

//QQ号码验证
function checkQQ(id){
    var qq = $("#" + id).val();
    var RegExp = /^[0-9]{5,12}$/gi;
    if(RegExp.test(qq)){
        $("#" + id).addClass('true');
        $("#" + id).next().html("<img src='../../../images/icon_ok.png'/>");
    }else{
        $("#" + id).removeClass('true');
        $("#" + id).next().html("<img src='../../../images/icon_error.png'/>请输入QQ号码");
    }
}

//手机验证
function checkMobile(id){
    var mobile = $("#" + id).val();
    var RegExp = /^[0-9]{11}$/gi;
    if(RegExp.test(mobile)){
        $("#" + id).addClass('true');
        $("#" + id).next().html("<img src='../../../images/icon_ok.png'/>");
        return true;
    }else{
        $("#" + id).removeClass('true');
        $("#" + id).next().html("<img src='../../../images/icon_error.png'/>请输入11位手机号码");
        return false;
    }
}

//验证码验证
function checkAuthCode(id){
    var authcode =  $("#" + id).val();
    var RegExp = /^[0-9]{4}$/gi;
    if(RegExp.test(authcode)){
        $("#" + id).addClass('true');
        $("#" + id).next().html("<img src='../../../images/icon_ok.png'/>");
    }else{
        $("#" + id).removeClass('true');
        $("#" + id).next().html("<img src='../../../images/icon_error.png'/>请输入4位验证码");
    }
}
//验证码超时时间
var settime = 60;
var timeout = 1;
var time;
var checktime;
function intervalBegin(){
    $("#authCode").html("发送成功，"+ (settime-timeout) +"秒后重新获取");
    timeout++;
}

function check(){
   if(timeout > settime){
       window.clearInterval(time);
       window.clearInterval(checktime);
       $("#authCode").html("获取验证码");
       $("#authCode").removeAttr('disabled');
       timeout = 1;
   }
}

//获取验证码
function getAuthCode(mobileid,authcodeid){
    flag = checkMobile(mobileid);
    if(flag){
        var mobile = $("#"+mobileid).val();
        $("#"+authcodeid).attr('disabled', 'disabled');
        $("#"+authcodeid).html("正在发送");
//        var URL_NAME = window.location.href;
//        URL_NAME = URL_NAME.substr(0,URL_NAME.lastIndexOf("\/")+1);
        var post_data = {'mobile':mobile};
        $.ajax({
            type:'post',
            url: 'http://' + window.location.host + '/api/message/sms/send_auth_code',
            data:post_data,
            datatype:'JSON',
            async:false,
            success:function(data){
                data = eval('(' +  data + ')');
                if(data.errno == 0){
                    $("#"+authcodeid).html("发送成功，"+ settime+"秒后可重新获取");
                    $("#"+authcodeid).attr('disabled', 'disabled');
                    time = setInterval(intervalBegin,1000);
                    checktime = setInterval(check,500);
                }else{
                    $("#"+authcodeid).html("发送失败，重新获取");
                    $("#"+authcodeid).removeAttr('disabled');
                }
            }
        });
    }else {
        $("#" + mobileid).focus();
    }
}
//提交前检查
function checkSubmit(type){
    switch(type){
        case "tuike":
            checkEmail("inputUsername");
            checkPasswordConfirm("inputPassword","inputPasswordConfirm");
            checkName('inputLastName','inputFirstName');
            checkQQ('inputQQ');
            checkMobile("inputMobile");
            checkAuthCode("inputAuthCode");
            if( ($("#inputUsername").hasClass('true')) && ($("#inputPassword").hasClass('true')) && ($("#inputFirstName").hasClass('true')) && ($("#inputQQ").hasClass('true')) && ($("#inputMobile").hasClass('true')) && ($("#inputAuthCode").hasClass('true'))){
                return true;
            }else{
                return false;
            }
            break;
        case "advertiser":
            checkEmail("inputUsername");
            checkPasswordConfirm("inputPassword","inputPasswordConfirm");
            checkName('inputLastName','inputFirstName');
            checkMobile("inputMobile");
            checkAuthCode("inputAuthCode");
            if( ($("#inputUsername").hasClass('true')) && ($("#inputPassword").hasClass('true')) && ($("#inputFirstName").hasClass('true')) && ($("#inputMobile").hasClass('true')) && ($("#inputAuthCode").hasClass('true'))){
                return true;
            }else{
                return false;
            }
            break;
        default :
            return false;
            break;
    }
    return false;
}