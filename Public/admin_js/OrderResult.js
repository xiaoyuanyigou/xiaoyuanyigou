$(document).ready(function(){//订单页面，给模板赋值
    $.post("getOrder",{},function(data){
        num(data);
    });
});
/**
 * [分页]
 * @type {Number}
 */
var currentstatus=0; //设置全局变量来获取状态
var currentPage=1;
var pagestatus=1;//status为0或为空时的初始页
var pagestatus1=1;var pagestatus2=1;var pagestatus3=1;var pagestatus4=1;var pagestatus5=1;
var pagestatus6=1;var pagestatus7=1;
var pagenum=0;//status为0或为空时的初始页总数
var pagenum1=0;var pagenum2=0;var pagenum3=0;var pagenum4=0;var pagenum5=0;
/**
 * [idnexcls description] 实现选项卡功能
 * @param  {[type]} e [description]
 * @return {[type]}   [description]   
 */
function idnexcls(e){
        var status=e.getAttribute("status");
       currentstatus=status;
        // alert(status+"status");
        $(document).ready(function(){
            $("tbody").empty();
            $.ajax({
            url:"getOrder",
            type: 'get',
            data:{status:status},
            dataType:'json',
            async : false,
            success: function(data){
                num(data);
            },
            error: function(){
                alert("没有数据");
            }
        });
    });
}
/*
按条件查找订单
 */

function query(e){
     var user_id=$('#user_id').val();
     var order_number=$('#order_number').val();
     var start=$('#start').val();
     var end=$('#end').val();
        $("tbody").empty();
        $.ajax({
            url:'getOrder',
            type: 'post',
            data:{
                user_id:user_id,
                order_number:order_number,
                start:start,
                end:end
            },
            dataType:'json',
            async : false,
            success: function(data){
                // alert(result(data));
                $('tbody').append(num(data));
                //alert(data);
            },
            error: function(){
                alert("获取信息失败");
            }
        });
}

/**
 * [num description] 封装前端代码，以便调用
 * @param  {[type]} data [description]
 * @return {[type]}      [description]
 */
function num(data){
    var obj = eval("("+data+")");
    pagenum=obj.pagenum;
    if (obj.status==0||obj.status=="") {
      pagestatus=obj.page;
    }
    if (obj.status==1) {
      pagestatus1=obj.page;
    }
    if (obj.status==2) {
      pagestatus2=obj.page;
    }
    if (obj.status==3) {
      pagestatus3=obj.page;
    }
    if (obj.status==4) {
      pagestatus4=obj.page;
    }
    if (obj.status==5) {
      pagestatus5=obj.page;
    }
    if (obj.status==6) {
      pagestatus6=obj.page;
    }
    if (obj.status==7) {
      pagestatus6=obj.page;
    }
            if(obj.result==2){
                alert("缺少参数！");
            }
            if(obj.result==0){
                alert("出错了");
            }
            /*
            如果result=1代表异步成功，获取数据
             */
            if(obj.result==1){
               var tb="";
               var status='';
               $.each(obj.data,function(idx,item){
               var button='';
               tb+="<tr><td></td>";
               tb+="<td id='ordernumber"+item.id+"'>"+item.order_number+"</td>";//订单号
               tb+="<td id='username"+item.id+"'>"+item.name+"</td>";//用户名
               tb+="<td ><p>"+item.roughly_address+"/"+item.detail_address+"</p></td>";//地址

                if(item.remark==null){
                     tb+="<td></td>";
                }else
                tb+="<td><p>"+item.remark+"</p></td>";//防止备注为空时显示undefined
                tb+="<td>"+item.buy_time+"</td>";//下单时间
                if(item.deliver_time==null){////防止发货时间为空时显示undefined
                     tb+="<td id='deliver_time"+item.id+"'></td>";
                }else
                tb+="<td id='deliver_time"+item.id+"'>"+item.deliver_time+"</td>";

                /*
                 从数据库取出status的值，判断订单状态
                 */
                if(item.status==0)status='未付款';
                else if(item.status==1){
                    status='未发货';
                    button="<button class='btn btn-info' onclick='deliverOrder(\""+item.id+"\")' id='deliver"+item.id+"'>发货</button>"
                }
                else if(item.status==2)status='已发货';
                else if(item.status==3)status='已完成';
                /*
                 特殊状态：未发货、退款中，需要为其添加处理按钮button,并为按钮添加点击事件
                 */
                else if(item.status==4){
                    status='退款中';
                    button="<button class='btn btn-danger' onclick='dealOrder(\""+item.id+"\")' id='deal"+item.id+"'>处理</button>"
                }else if(item.status==5){
                    status='已退款';
                    button='';
                }else{
                    status='';
                    button='';
                }
                /*
                   订单状态及订单详情按钮
                 */
                tb+="<td id='orderStatus"+item.id+"'>"+status+"</td>"+
                    "<td><button class='btn btn-success' style='margin-right:10px'  onclick='orderInfo(\""+item.id+"\")'>订单详情</button>"+button+"</td></tr>";


               })//$.each
               // 将tb追加到tbody标签里
               $('tbody').html(tb);

               
               //分页
               if (obj.status==0) {
                if (obj.page<obj.pagenum){
                    div="<span><a onclick='page(1)'>首页</a></span><span><a onclick='pagedel()'><上一页</a></span><span>当前页："+obj.page+"</span><span>共"+obj.pagenum+"页</span><span><a onclick='pageadd()'>下一页></a></span><span><a onclick='page(pagenum)'>尾页</a></span>"
                }else{
                    div="<span><a onclick='page(1)'>首页</a></span><span><a onclick='pagedel()'><上一页</a></span><span>当前页："+obj.page+"</span><span>共"+obj.pagenum+"页</span><span><a onclick='last()'>下一页></a></span><span><a onclick='page(pagenum)'>尾页</a onclick='page(pagenum)'></span>"
                }
               }
               if (obj.status==1) {
                if (obj.page<obj.pagenum){
                    div="<span><a onclick='page(1)'>首页</a></span><span><a onclick='pagedel()'><上一页</a></span><span>当前页："+obj.page+"</span><span>共"+obj.pagenum+"页</span><span><a onclick='pageadd()'>下一页></a></span><span><a onclick='page(pagenum)'>尾页</a></span>"
                }else{
                    div="<span><a onclick='page(1)'>首页</a></span><span><a onclick='pagedel()'><上一页</a></span><span>当前页："+obj.page+"</span><span>共"+obj.pagenum+"页</span><span><a onclick='last()'>下一页></a></span><span><a onclick='page(pagenum)'>尾页</a onclick='page(pagenum)'></span>"
                }
               }
               if (obj.status==2) {
                if (obj.page<obj.pagenum){
                    div="<span><a onclick='page(1)'>首页</a></span><span><a onclick='pagedel()'><上一页</a></span><span>当前页："+obj.page+"</span><span>共"+obj.pagenum+"页</span><span><a onclick='pageadd()'>下一页></a></span><span><a onclick='page(pagenum)'>尾页</a></span>"
                }else{
                    div="<span><a onclick='page(1)'>首页</a></span><span><a onclick='pagedel()'><上一页</a></span><span>当前页："+obj.page+"</span><span>共"+obj.pagenum+"页</span><span><a onclick='last()'>下一页></a></span><span><a onclick='page(pagenum)'>尾页</a onclick='page(pagenum)'></span>"
                }
               }
               if (obj.status==3) {
                if (obj.page<obj.pagenum){
                    div="<span><a onclick='page(1)'>首页</a></span><span><a onclick='pagedel()'><上一页</a></span><span>当前页："+obj.page+"</span><span>共"+obj.pagenum+"页</span><span><a onclick='pageadd()'>下一页></a></span><span><a onclick='page(pagenum)'>尾页</a></span>"
                }else{
                    div="<span><a onclick='page(1)'>首页</a></span><span><a onclick='pagedel()'><上一页</a></span><span>当前页："+obj.page+"</span><span>共"+obj.pagenum+"页</span><span><a onclick='last()'>下一页></a></span><span><a onclick='page(pagenum)'>尾页</a onclick='page(pagenum)'></span>"
                }
               }
               if (obj.status==4) {
                if (obj.page<obj.pagenum){
                    div="<span><a onclick='page(1)'>首页</a></span><span><a onclick='pagedel()'><上一页</a></span><span>当前页："+obj.page+"</span><span>共"+obj.pagenum+"页</span><span><a onclick='pageadd()'>下一页></a></span><span><a onclick='page(pagenum)'>尾页</a></span>"
                }else{
                    div="<span><a onclick='page(1)'>首页</a></span><span><a onclick='pagedel()'><上一页</a></span><span>当前页："+obj.page+"</span><span>共"+obj.pagenum+"页</span><span><a onclick='last()'>下一页></a></span><span><a onclick='page(pagenum)'>尾页</a onclick='page(pagenum)'></span>"
                }
               }
               if (obj.status==5) {
                if (obj.page<obj.pagenum){
                    div="<span><a onclick='page(1)'>首页</a></span><span><a onclick='pagedel()'><上一页</a></span><span>当前页："+obj.page+"</span><span>共"+obj.pagenum+"页</span><span><a onclick='pageadd()'>下一页></a></span><span><a onclick='page(pagenum)'>尾页</a></span>"
                }else{
                    div="<span><a onclick='page(1)'>首页</a></span><span><a onclick='pagedel()'><上一页</a></span><span>当前页："+obj.page+"</span><span>共"+obj.pagenum+"页</span><span><a onclick='last()'>下一页></a></span><span><a onclick='page(pagenum)'>尾页</a onclick='page(pagenum)'></span>"
                }
               }
               if (obj.status==6) {
                if (obj.page<obj.pagenum){
                    div="<span><a onclick='page(1)'>首页</a></span><span><a onclick='pagedel()'><上一页</a></span><span>当前页："+obj.page+"</span><span>共"+obj.pagenum+"页</span><span><a onclick='pageadd()'>下一页></a></span><span><a onclick='page(pagenum)'>尾页</a></span>"
                }else{
                    div="<span><a onclick='page(1)'>首页</a></span><span><a onclick='pagedel()'><上一页</a></span><span>当前页："+obj.page+"</span><span>共"+obj.pagenum+"页</span><span><a onclick='last()'>下一页></a></span><span><a onclick='page(pagenum)'>尾页</a onclick='page(pagenum)'></span>"
                }
               }
               if (obj.status==7) {
                if (obj.page<obj.pagenum){
                    div="<span><a onclick='page(1)'>首页</a></span><span><a onclick='pagedel()'><上一页</a></span><span>当前页："+obj.page+"</span><span>共"+obj.pagenum+"页</span><span><a onclick='pageadd()'>下一页></a></span><span><a onclick='page(pagenum)'>尾页</a></span>"
                }else{
                    div="<span><a onclick='page(1)'>首页</a></span><span><a onclick='pagedel()'><上一页</a></span><span>当前页："+obj.page+"</span><span>共"+obj.pagenum+"页</span><span><a onclick='last()'>下一页></a></span><span><a onclick='page(pagenum)'>尾页</a onclick='page(pagenum)'></span>"
                }
               }

                //分页结束

                // $("#address").css({"overflow":"hidden","white-space":"nowrap","text-overflow":"ellipsis"});
               $("#operation").html(div);
               $("#operation span").css({"margin-left":"20px","padding":"5px","border":"1px solid #ccc"});
               $("#operation").css({"text-align":"center","margin":"0 auto","font-size":"16px","margin-top":"50px","margin-buttom":"100px"});

            }//result==1

}//function num

function last(){
    alert("这是最后一页了！");
}
// 点击页数加一
function pageadd(){
    // currentPage++;//页数加一
    if (currentstatus==0||currentstatus=="") {
       pagestatus++;//页数加一
        currentPage=pagestatus;
        // alert(currentPage);
    }else if(currentstatus==1){
        pagestatus1++;//页数加一
        currentPage=pagestatus1;
    }else if(currentstatus==2){
        pagestatus2++;//页数加一
        currentPage=pagestatus2;
    }else if(currentstatus==3){
        pagestatus3++;//页数加一
        currentPage=pagestatus3;
    }else if(currentstatus==4){
        pagestatus4++;//页数加一
        currentPage=pagestatus4;
    }else if(currentstatus==5){
        pagestatus5++;//页数加一
        currentPage=pagestatus5;
    }else if(currentstatus==6){
        pagestatus6++;//页数加一
        currentPage=pagestatus6;
    }else if(currentstatus==7){
        pagestatus7++;//页数加一
        currentPage=pagestatus7;
    }
    // alert("2当前页数为："+currentPage);

        var url1="getOrder";
        $.ajax({
        url:url1,
        type: 'get',
        data:{'page':currentPage,'status':currentstatus},
        dataType:'json',
        async : false,
        success: function(data){
            num(data);
        },
        error:function(){
            alert("数据出错！");
        }
    });

    }
    function page(page){
        currentPage=page;

        var url1="getOrder";
        $.ajax({
        url:url1,
        type: 'get',
        data:{'page':currentPage,'status':currentstatus},
        dataType:'json',
        async : false,
        success: function(data){
            // alert("传页成功！");
            num(data);
        },
        error:function(){
            alert("传页失败！");
        }
    });

    }
//点击页数减一
function pagedel(){
    // var page1=2;
    // alert(currentPage);
    if(currentPage>1){
        if (currentstatus==0||currentstatus=="") {
        pagestatus--;//页数减一
        currentPage=pagestatus;
    }else if(currentstatus==1){
        pagestatus1--;//页数减一
        currentPage=pagestatus1;
    }else if(currentstatus==2){
        pagestatus2--;//页数减一
        currentPage=pagestatus2;
    }else if(currentstatus==3){
        pagestatus3--;//页数减一
        currentPage=pagestatus3;
    }else if(currentstatus==4){
        pagestatus4--;//页数减一
        currentPage=pagestatus4;
    }else if(currentstatus==5){
        pagestatus5--;//页数减一
        currentPage=pagestatus5;
    }else if(currentstatus==6){
        pagestatus6--;//页数减一
        currentPage=pagestatus6;
    }else if(currentstatus==7){
        pagestatus7--;//页数减一
        currentPage=pagestatus7;
    }
        var status = $("#bottom").val();

        var url1="getOrder";
        $.ajax({
        url:url1,
        type: 'get',
        data:{'page':currentPage,'status':currentstatus},
        dataType:'json',
        async : false,
        success: function(data){
            // alert("传页成功！");
            num(data);
        },
        error:function(){
            alert("传页失败！");
        }
    });

    }else{
        currentPage=1;
        alert("这是第一页了！");
    }

}



 /*
 订单详情
  */
function orderInfo(idAttr){

   $.ajax({
       url:'checkOrderAdmin',
       data:{order_id:idAttr},
       type:'post',
       success:function(data){
        var obj=eval("("+data+")") ;
        //alert(data);
        var init=obj.data[0];//多个商品有共同的属性：订单号、收货人、手机号、下单时间、发货时间。这里取出一个展示即可

        /*var status="";
        if(init.status==0)status='未付款';
        else if(init.status==1)status='未发货';
        else if(init.status==2)status='已发货';
        else if(init.status==3)status='已完成';
        else if(init.status==4)status='退款中';
        else status='退款成功';*/

        //图片    商品名称    规格  数量  原价  销售价 总价
        layer.open({
            type: 2,
            title: '订单详情',
            shadeClose: true,
            shade: 0.8,
            area: ['650px', '80%'],
            content:['checkOrder?order_id='+idAttr],
            success:function(layero,index){
                   if(obj.result==1){
                   var tb="";
                   var status='';
                   var pay=0;

                   $.each(obj.data,function(idx,item){
                     var total=item.num*item.sale_price;
                     var index=idx+1;
                     tb+="<tr>";
                     tb+="<td>"+index+"</td>";
                     tb+="<td><img src='"+item.img_path+"' style='width:50px;height:50px'></td>";
                     tb+="<td  style='word-break : break-all; overflow:hidden;'>"+item.pname+"</td>";

                     tb+="<td>"+item.sname+"</td>";
                     tb+="<td>"+item.num+"</td>";
                     tb+="<td>"+item.original_price+"</td>";
                     tb+="<td>"+item.sale_price+"</td>";
                     tb+="<td>"+total+"</td></tr>";
                     pay+=total;
               })
                    $("tbody", layero.find("iframe")[0].contentWindow.document).html(tb);
                    $("#status", layero.find("iframe")[0].contentWindow.document).html($('#orderStatus'+idAttr).text());
                    $("#tel", layero.find("iframe")[0].contentWindow.document).html(init.tel);
                    $("#order_num", layero.find("iframe")[0].contentWindow.document).html(init.order_number);
                    $("#buyer", layero.find("iframe")[0].contentWindow.document).html(init.name);
                    $("#address", layero.find("iframe")[0].contentWindow.document).html(init.roughly_address+' / '+init.detail_address);
                    $("#pay", layero.find("iframe")[0].contentWindow.document).html(pay);

        }

    }
});

}
})
}

/*
发货
 */
function deliverOrder(idAttr){
   var tracking_num;
   layer.prompt({title: '请输入运单编号'},function(val, index){
     //layer.msg('得到了'+val);
     tracking_num = val;
     $.ajax({
        url:'deliver',
        type:'post',
        data:{order_id:idAttr,tracking_num:tracking_num},
        success:function(data){
           var obj = eval("("+data+")");
           if(obj.result==1){
            layer.msg("订单编号:"+$('#ordernumber'+idAttr).text()+"发货成功");
          }else {
            layer.msg("发货失败!");
          }
          $('#orderStatus'+idAttr).html("已发货");
                $('#deliver_time'+idAttr).html(obj.data.deliver_time);
                $('#deliver'+idAttr).remove();
                /*
                左侧导航栏的信息做出相应改变
                 */
                var deliver_count=$('#deliver_count', window.parent.document).html()-1;
                if(deliver_count==0){
                      $('#deliver_count', window.parent.document).remove();
                }else{
                    $('#deliver_count', window.parent.document).html(deliver_count);
                     //选项卡中的数字也要改变
                    $('#deliver_num').html(deliver_count);
                }
                var count=$('#count', window.parent.document).html()-1;
                if(count==0){
                      $('#count', window.parent.document).remove();
                }else $('#count', window.parent.document).html(count);
        }
     });

     layer.close(index);
     });
        }


/*
处理退款
 */
function dealOrder(idAttr){
$.ajax({
    url:'refundHandle',
    type:'post',
    data:{order_id:idAttr},
    success:function(data){
        var item=eval("("+data+")").data[0];
        var status=item.status;
        var oper = new Array();
        if(status==0){
            oper[0] = "同意";
            oper[1] = "拒绝";
        }

        layer.open({
            type: 2,
            title: '退款详情',
            shadeClose: true,
            shade: 0.8,
            area: ['380px', '90%'],
            content:['refund?order_id='+idAttr],
            btn:oper,//仅有未处理的订单才有按钮
            success:function(layero,index){
                var username= $('#username'+idAttr).text();
                //var agreeBtn="<button class='btn btn-success'  style='margin-right:30px' id='agree"+idAttr+"'>同意</button>";
                //var refuseBtn="<button class='btn btn-warning'  onclick='refuse(\""+idAttr+"\")'>拒绝</button>";
                //<img src='__PUBLIC__/admin_upload/shoes1.jpg' height='70px'>
                $("#d_name", layero.find("iframe")[0].contentWindow.document).html(username);
                $("#d_pro_name", layero.find("iframe")[0].contentWindow.document).html(item.name1);
                $("#d_standeard", layero.find("iframe")[0].contentWindow.document).html(item.name2);
                $("#d_reason", layero.find("iframe")[0].contentWindow.document).html(item.reason);
                $("#d_refund_time", layero.find("iframe")[0].contentWindow.document).html(item.refund_time);
                $("#d_price", layero.find("iframe")[0].contentWindow.document).html(item.sale_price+"*"+item.num+"="+item.sale_price*item.num);
                //$("#operation", layero.find("iframe")[0].contentWindow.document).append(agreeBtn+refuseBtn);

            },//end success
            /*同意按钮*/
            yes:function(index,layero){
                var price=item.sale_price*item.num;
                $.ajax({
                url:"refundHandle",
                type:'post',
                data:{order_id:idAttr,operation:1},
                success:function(data){
                var item=eval("("+data+")").data;
                if(item.msg=='操作成功！'){
                alert(item.msg+'退款金额:'+price);
                $('#orderStatus'+idAttr).html("退款成功");
                $('#deal'+idAttr).remove();//去掉处理按钮
                //$('#deliver_tab').remove();
                var deal_count=$('#deal_count', window.parent.document).html()-1;
                if(deal_count==0){
                      $('#deal_count', window.parent.document).remove();
                }else{
                    $('#deal_count', window.parent.document).html(deal_count);
                    $('#deal_num').html(deal_count);
                }
                var count=$('#count', window.parent.document).html()-1;
                if(count==0){
                      $('#count', window.parent.document).remove();
                }else $('#count', window.parent.document).html(count);

                }else alert(item.msg);

               }
            })//end ajax for yes

                layer.close(index);
            },
            /*拒绝按钮*/
            btn2:function(index,layero){

                var reasonInfo='<center><form style="margin-top:30px;"><span style="height:148px;margin-left:20px;float:left;">请选择拒绝原因：</span><textarea id="input" name="reply" ></textarea><br><input type="button" id="replyBtn" class="btn btn-success" value="提交" ></form></center>';

                layer.open({
                  type: 1,
                  skin: 'layui-layer-rim', //加上边框
                  closeBtn: 1, //不显示关闭按钮
                  area: ['320px', '240px'], //宽高
                  content: reasonInfo,
                  success:function(layero,index){

                    layero.find("#replyBtn").click(function(){
                        var reply=layero.find("#input");
                        $.ajax({
                         url:"refuse",
                         type:'post',
                         data:{order_id:idAttr,reply:reply.val()},
                         success:function(data){
                            var deliverBtn="<button class='btn btn-info' onclick='deliverOrder(\""+item.id+"\")' id='deliver"+item.id+"'>发货</button>";
                            var obj=eval("("+data+")");
                            alert(obj.data.msg);
                            layer.close(index);//关闭窗口
                            $('#orderStatus'+idAttr).html("待发货");//拒绝退款，处于待发货状态
                            $('#deal'+idAttr).replaceWith(deliverBtn);//替换成发货按钮
                            var deal_count=$('#deal_count', window.parent.document).html()-1;
                            if(deal_count==0){
                             $('#deal_count', window.parent.document).remove();
                            }else{
                             $('#deal_count', window.parent.document).html(deal_count);
                             $('#deal_num').html(deal_count);
                            }
                             var count=$('#count', window.parent.document).html()-1;
                             if(count==0){
                             $('#count', window.parent.document).remove();
                            }else $('#count', window.parent.document).html(count);
                           // $('#deliver_tab').html(" ");

                         }
                        })//end ajax for btn2

                      })//end click
                     }
                   })

                   }    //end btn2
               })
             }//end success
          });
    }

