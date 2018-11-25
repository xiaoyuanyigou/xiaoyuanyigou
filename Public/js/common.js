//获取参数 
function GetQueryString(name){
     var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
     var r = window.location.search.substr(1).match(reg);
     if(r!=null)
     	return decodeURI(r[2]); 
     return null;
}
// $.ajax({
// 	type:'post',
//     cache:false,
//     data:{},
//     url:"",
//     dataType:"json",
//     beforeSend:function(){
//			$(".body").append('<img class="timg" src="__PUBLIC__/images/timg.gif"  />');
//     },
//     success:function(data){
//     },
//     complete: function() {
//     }
// });