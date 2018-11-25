window.onload=function(){
	setInterval(function(){
	 	var width = document.documentElement.clientWidth || document.body.clientWidth;
    	document.body.style.fontSize=width/24 + "px";
    },100);
}
//获取参数 
function GetQueryString(name){
     var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
     var r = window.location.search.substr(1).match(reg);
     if(r!=null)
     	return decodeURI(r[2]); 
     return null;
}
// $.ajax({
// 			type:'post',
// 	        cache:false,
// 	        data:{sortId:GetQueryString('sortId'),page:page,order:order,up:up},
// 	        url:"getAllSortBelongTheSecondSort",
// 	        dataType:"json",
// 	        beforeSend:function(){
// 	           add.append('<img class="timg" src="__PUBLIC__/images/timg.gif"  />');
// 	        },
// 	        success:function(data){
// 	        },
// 	        complete: function() {
// 	        	add.children(".timg").remove();
// 	        }
// 	    });