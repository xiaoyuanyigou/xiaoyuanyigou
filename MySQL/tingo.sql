/*
 Navicat Premium Data Transfer

 Source Server         : 校园易购-正式
 Source Server Type    : MySQL
 Source Server Version : 50555
 Source Host           : 116.62.128.181
 Source Database       : tingo

 Target Server Type    : MySQL
 Target Server Version : 50555
 File Encoding         : utf-8

 Date: 10/29/2018 11:42:28 AM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `tg_address`
-- ----------------------------
DROP TABLE IF EXISTS `tg_address`;
CREATE TABLE `tg_address` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '地址表ID',
  `user_id` int(11) NOT NULL COMMENT '地址所属于用户的ID',
  `roughly_address` varchar(100) NOT NULL COMMENT '省市县地址',
  `name` varchar(20) NOT NULL COMMENT '收货人姓名',
  `tel` varchar(20) NOT NULL COMMENT '收货人手机号',
  `detail_address` varchar(255) NOT NULL COMMENT '详细地址',
  `status` int(1) NOT NULL COMMENT ',是否是默认地址（-1：已经删除，0：不是，1：默认地址）',
  `time` datetime NOT NULL COMMENT '添加或者修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_admin_user`
-- ----------------------------
DROP TABLE IF EXISTS `tg_admin_user`;
CREATE TABLE `tg_admin_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL COMMENT '用户名',
  `password` varchar(150) NOT NULL COMMENT '密码',
  `image_path` varchar(150) NOT NULL COMMENT '头像路径',
  `tel` int(12) DEFAULT NULL COMMENT '手机号',
  `eamil` varchar(30) DEFAULT NULL COMMENT '邮箱',
  `status` int(1) NOT NULL COMMENT '状态',
  `register_time` datetime NOT NULL COMMENT '注册时间',
  `open_id` varchar(100) DEFAULT NULL COMMENT '绑定的微信openid',
  `string` varchar(20) DEFAULT NULL COMMENT '验证字符串',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_asset`
-- ----------------------------
DROP TABLE IF EXISTS `tg_asset`;
CREATE TABLE `tg_asset` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '资产表ID',
  `merchant_id` int(11) NOT NULL COMMENT '商家ID',
  `count` decimal(10,2) NOT NULL COMMENT '变更金额',
  `change_id` varchar(50) NOT NULL COMMENT '变更相关表ID_标志：123_o（订单表ID为123）；222_w（提现表ID为222）',
  `reason` varchar(255) NOT NULL COMMENT '资金变更原因',
  `before_balance` decimal(11,2) NOT NULL COMMENT '变更前余额',
  `after_balance` decimal(11,2) NOT NULL COMMENT '变更后余额',
  `time` datetime NOT NULL COMMENT '变更时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_cart`
-- ----------------------------
DROP TABLE IF EXISTS `tg_cart`;
CREATE TABLE `tg_cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '唯一ID',
  `pro_id` int(11) NOT NULL COMMENT '商品ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `time` datetime NOT NULL COMMENT '时间',
  `status` int(1) NOT NULL COMMENT '状态1表示正常，0表示没用（比如说加入购物车后下单了）',
  `standard_id` int(11) NOT NULL COMMENT '规格表ID',
  `num` int(11) NOT NULL COMMENT '商品数量',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_discount`
-- ----------------------------
DROP TABLE IF EXISTS `tg_discount`;
CREATE TABLE `tg_discount` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '优惠ID',
  `name` varchar(100) NOT NULL COMMENT '优惠名称',
  `condition` varchar(100) NOT NULL COMMENT '优惠条件',
  `content` varchar(100) NOT NULL COMMENT '优惠内容（怎么优惠）',
  `status` int(1) NOT NULL COMMENT '状态（1：正常；-1：删除）',
  `time` datetime NOT NULL COMMENT '时间',
  `condition_explain` varchar(255) NOT NULL COMMENT '条件的文字说明',
  `content_explain` varchar(255) NOT NULL COMMENT '活动内容的文字说明',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_focus`
-- ----------------------------
DROP TABLE IF EXISTS `tg_focus`;
CREATE TABLE `tg_focus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `index` int(11) NOT NULL,
  `pro_id` int(11) NOT NULL,
  `img_path` varchar(150) NOT NULL,
  `time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_merchant`
-- ----------------------------
DROP TABLE IF EXISTS `tg_merchant`;
CREATE TABLE `tg_merchant` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商家ID',
  `shop_name` varchar(100) NOT NULL COMMENT '店铺名称',
  `real_name` varchar(100) NOT NULL COMMENT '商家真实名字',
  `logo_path` varchar(255) NOT NULL COMMENT '店铺logo',
  `tel` varchar(12) NOT NULL COMMENT '商家电话',
  `open_id` varchar(100) NOT NULL COMMENT '商家在校园易购的openid',
  `address` varchar(255) NOT NULL COMMENT '店铺详细地址',
  `status` int(1) NOT NULL COMMENT '店铺状态（0：待审核；1：正常；2：禁用）',
  `latitude` varchar(20) NOT NULL COMMENT '纬度',
  `longitude` varchar(20) NOT NULL COMMENT '经度',
  `pay` varchar(10) NOT NULL COMMENT '支持的支付方式（1：只支持微信支付；2：只支持货到付款；1,2：微信支付货到付款都支持）',
  `time` datetime NOT NULL COMMENT '申请时间',
  `email` varchar(50) DEFAULT NULL COMMENT '商家邮箱',
  `balance` decimal(10,2) NOT NULL COMMENT '商家的余额',
  `area` varchar(100) NOT NULL COMMENT '店铺省市县地址',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_order`
-- ----------------------------
DROP TABLE IF EXISTS `tg_order`;
CREATE TABLE `tg_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订单ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `address_id` int(11) NOT NULL COMMENT '地址表ID',
  `merchant_id` int(11) NOT NULL COMMENT '商户ID',
  `order_number` varchar(30) NOT NULL COMMENT '订单编号',
  `original_price` decimal(10,2) NOT NULL COMMENT '订单原价',
  `price` decimal(10,2) NOT NULL COMMENT '订单实际价格',
  `remark` varchar(255) DEFAULT NULL COMMENT '顶大备注',
  `buy_time` datetime DEFAULT NULL COMMENT '提交订单时间',
  `deliver_time` datetime DEFAULT NULL COMMENT '发货时间',
  `full_time` datetime DEFAULT NULL COMMENT '完成时间',
  `status` int(1) NOT NULL COMMENT '订单状态（-1：未提交；0：未支付；1：已付款；2：已发货；3：已完成；4：退款中；5：退款完成；6：已取消；7：退货中；8：退货完成）',
  `pay` int(1) DEFAULT NULL COMMENT '支付方式（1：微信支付；2：货到付款）',
  `tracking_num` varchar(40) DEFAULT NULL COMMENT '快递单号',
  `receive_type` int(1) DEFAULT NULL COMMENT '收货类型（1：用户确认收货，2：系统自动收货，3：商家点完成）',
  `out_trade_no` varchar(50) NOT NULL COMMENT '微信支付的out_trade_no',
  `open_id` varchar(100) DEFAULT NULL COMMENT '第三方平台的家长的openid',
  `student_id` int(11) DEFAULT NULL COMMENT '学生ID',
  `send` int(11) DEFAULT NULL COMMENT '是否向家长发消息（0：不发；1：发）',
  `p_id` int(11) DEFAULT NULL COMMENT '平台ID',
  `save_time` datetime NOT NULL COMMENT '这条记录产生的时间',
  `tracking_company` varchar(50) DEFAULT NULL COMMENT '快递公司',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=122 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_order_pro`
-- ----------------------------
DROP TABLE IF EXISTS `tg_order_pro`;
CREATE TABLE `tg_order_pro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `pro_id` int(11) NOT NULL COMMENT '商品ID',
  `standard_id` int(11) NOT NULL COMMENT '规格ID',
  `discount_id` varchar(50) NOT NULL DEFAULT '0' COMMENT '该规格参加活动的ID，多个活动这样计算（1，2）',
  `num` int(11) NOT NULL COMMENT '该条记录的商品数量',
  `merchant_id` int(11) NOT NULL COMMENT '店铺ID',
  `sale_per_price` decimal(10,2) NOT NULL COMMENT '没优惠的销售单价',
  `original_per_price` decimal(10,2) DEFAULT NULL COMMENT '没优惠的销售原价',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_pay_record`
-- ----------------------------
DROP TABLE IF EXISTS `tg_pay_record`;
CREATE TABLE `tg_pay_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appid` varchar(30) DEFAULT NULL,
  `nonce_str` varchar(50) DEFAULT NULL,
  `mch_id` varchar(20) DEFAULT NULL,
  `body` varchar(128) DEFAULT NULL,
  `notify_url` varchar(50) DEFAULT NULL,
  `open_id` varchar(100) DEFAULT NULL,
  `out_trade_no` varchar(100) DEFAULT NULL,
  `spbill_create_ip` varchar(50) DEFAULT NULL,
  `total_fee` int(11) NOT NULL,
  `trade_type` varchar(20) NOT NULL,
  `complete_time` datetime DEFAULT NULL,
  `return_code` varchar(20) DEFAULT NULL,
  `result_code` varchar(20) DEFAULT NULL,
  `return_msg` varchar(50) DEFAULT NULL,
  `status` int(1) NOT NULL,
  `order_ids` varchar(11) NOT NULL COMMENT '订单ID组成的字符串（1,2,3）',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `timestamp` varchar(50) DEFAULT NULL COMMENT '时间戳',
  `request_time` datetime NOT NULL COMMENT '支付请求时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_pro_standard`
-- ----------------------------
DROP TABLE IF EXISTS `tg_pro_standard`;
CREATE TABLE `tg_pro_standard` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '唯一ID',
  `pro_id` int(11) NOT NULL COMMENT '商品ID',
  `name` varchar(50) NOT NULL COMMENT '规格名称',
  `sale_price` decimal(10,2) NOT NULL COMMENT '该规格下的销售价格',
  `time` datetime NOT NULL COMMENT '时间',
  `status` int(1) NOT NULL COMMENT '没有添加商品的有一个默认规格，有多种规格的第一个是默认规格（-1：已删除，1：默认，0：不默认）',
  `inventory` int(11) NOT NULL COMMENT '该规格下的库存',
  `sale_num` int(11) NOT NULL DEFAULT '0' COMMENT '销售数量',
  `original_price` decimal(10,2) NOT NULL COMMENT '商品的原价',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=572 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_product`
-- ----------------------------
DROP TABLE IF EXISTS `tg_product`;
CREATE TABLE `tg_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商品ID',
  `name` varchar(50) NOT NULL COMMENT '商品名称',
  `sort_id` int(11) NOT NULL COMMENT '分类ID（暂时留着，后面去掉）',
  `time` datetime NOT NULL COMMENT '上架时间',
  `status` int(1) NOT NULL COMMENT '商品状态（0：已下架，1：已上架）',
  `merchant_id` int(11) NOT NULL DEFAULT '1' COMMENT '店铺ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=210 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_product_image`
-- ----------------------------
DROP TABLE IF EXISTS `tg_product_image`;
CREATE TABLE `tg_product_image` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '唯一ID',
  `pro_id` int(11) NOT NULL COMMENT '商品ID',
  `img_path` varchar(100) NOT NULL COMMENT '图片路径',
  `status` int(1) NOT NULL COMMENT '状态（1：默认图片，0：不是默认）',
  `time` datetime NOT NULL COMMENT '时间',
  `admin_user_id` int(11) NOT NULL DEFAULT '1' COMMENT '管理员ID',
  `merchant_id` int(11) NOT NULL DEFAULT '1' COMMENT '商家ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=875 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_product_image_descript`
-- ----------------------------
DROP TABLE IF EXISTS `tg_product_image_descript`;
CREATE TABLE `tg_product_image_descript` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pro_id` int(11) NOT NULL,
  `img_path` varchar(200) NOT NULL,
  `status` int(1) NOT NULL,
  `time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1615 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_product_module`
-- ----------------------------
DROP TABLE IF EXISTS `tg_product_module`;
CREATE TABLE `tg_product_module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `index` int(11) NOT NULL,
  `pro_id` int(11) NOT NULL,
  `time` datetime NOT NULL,
  `img_path` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_product_sort`
-- ----------------------------
DROP TABLE IF EXISTS `tg_product_sort`;
CREATE TABLE `tg_product_sort` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商品分类中间表ID',
  `product_id` int(11) NOT NULL COMMENT '商品ID',
  `sort_id` int(11) NOT NULL COMMENT '分类ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_refund`
-- ----------------------------
DROP TABLE IF EXISTS `tg_refund`;
CREATE TABLE `tg_refund` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '退款表ID',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `reason` varchar(255) DEFAULT NULL COMMENT '退款原因',
  `count` decimal(10,2) NOT NULL COMMENT '退款金额',
  `apply_time` datetime NOT NULL COMMENT '用户申请时间',
  `reply` varchar(255) DEFAULT NULL COMMENT '商家回复内容',
  `reply_time` datetime DEFAULT NULL COMMENT '商家回复时间',
  `status` int(1) NOT NULL COMMENT '退款状态（0：刚申请；1：退款成功；2：退款失败）',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_return`
-- ----------------------------
DROP TABLE IF EXISTS `tg_return`;
CREATE TABLE `tg_return` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `name` varchar(100) NOT NULL COMMENT '退货人姓名',
  `tel` varchar(15) NOT NULL COMMENT '退货人手机号',
  `status` int(1) NOT NULL COMMENT '退货状态（0：申请退货；1：退货成功；2：退货失败）',
  `tracking_number` varchar(50) NOT NULL COMMENT '退货的快递单号',
  `reason` varchar(255) NOT NULL COMMENT '退货原因',
  `apply_time` datetime NOT NULL COMMENT '申请时间',
  `reply_time` datetime DEFAULT NULL COMMENT '回复时间',
  `reply` varchar(255) DEFAULT NULL COMMENT '商家回复内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_search_record`
-- ----------------------------
DROP TABLE IF EXISTS `tg_search_record`;
CREATE TABLE `tg_search_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `search_name` varchar(50) NOT NULL COMMENT '搜索的关键字',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `time` datetime NOT NULL COMMENT '搜索时间',
  `status` int(1) NOT NULL COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_sort`
-- ----------------------------
DROP TABLE IF EXISTS `tg_sort`;
CREATE TABLE `tg_sort` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `sort_name` varchar(30) NOT NULL COMMENT '分类名称',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父级分类ID',
  `status` int(1) NOT NULL COMMENT '状态',
  `time` datetime NOT NULL COMMENT '最后一次修改时间',
  `recommend` int(1) DEFAULT NULL COMMENT '是否推荐到首页（0：不推荐，1：推荐）',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_sort_module`
-- ----------------------------
DROP TABLE IF EXISTS `tg_sort_module`;
CREATE TABLE `tg_sort_module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `index` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `img_path` varchar(150) NOT NULL,
  `time` datetime NOT NULL,
  `sort_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_standard_discount`
-- ----------------------------
DROP TABLE IF EXISTS `tg_standard_discount`;
CREATE TABLE `tg_standard_discount` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `standard_id` int(11) NOT NULL COMMENT '规格ID',
  `discount_id` int(11) NOT NULL COMMENT '优惠ID',
  `merchant_id` int(11) NOT NULL COMMENT '店铺ID',
  `time` datetime NOT NULL,
  `status` int(1) NOT NULL COMMENT '状态（正常：1，已删除：0）',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=417 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_title_module`
-- ----------------------------
DROP TABLE IF EXISTS `tg_title_module`;
CREATE TABLE `tg_title_module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `first_sort_id` int(11) NOT NULL,
  `index` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_title_module_product`
-- ----------------------------
DROP TABLE IF EXISTS `tg_title_module_product`;
CREATE TABLE `tg_title_module_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title_module_id` int(11) NOT NULL,
  `index` int(11) NOT NULL,
  `img_path` varchar(255) NOT NULL,
  `time` datetime NOT NULL,
  `pro_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_user`
-- ----------------------------
DROP TABLE IF EXISTS `tg_user`;
CREATE TABLE `tg_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户唯一ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `img_path` varchar(200) NOT NULL COMMENT '头像路径',
  `sex` int(1) NOT NULL COMMENT '性别，1：男，2：女',
  `open_id` varchar(100) NOT NULL COMMENT '校园易购下用户微信的openID',
  `tel` varchar(15) DEFAULT NULL COMMENT '用户手机号',
  `email` varchar(30) DEFAULT NULL COMMENT '用户email',
  `country` varchar(30) NOT NULL COMMENT '用户微信所在国家',
  `province` varchar(30) NOT NULL COMMENT '用户微信所在省',
  `city` varchar(30) NOT NULL COMMENT '用户微信所在市',
  `status` int(1) NOT NULL COMMENT '用户状态',
  `unionid` varchar(150) DEFAULT NULL COMMENT '用于以后的其他平台，同一个用户',
  `time` datetime NOT NULL COMMENT '初次进入时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6790 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_weixin`
-- ----------------------------
DROP TABLE IF EXISTS `tg_weixin`;
CREATE TABLE `tg_weixin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wx_name` varchar(255) DEFAULT NULL,
  `wx_original_id` varchar(255) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `status` int(11) DEFAULT '1',
  `app_id` varchar(255) DEFAULT NULL,
  `app_secret` varchar(255) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `access_token` varchar(255) DEFAULT NULL,
  `access_token_upd_time` datetime DEFAULT NULL,
  `jsapi_ticket` varchar(255) DEFAULT NULL,
  `jsapi_ticket_upd_time` datetime DEFAULT NULL,
  `template_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tg_withdraw`
-- ----------------------------
DROP TABLE IF EXISTS `tg_withdraw`;
CREATE TABLE `tg_withdraw` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '提现表ID',
  `merchant_id` int(11) NOT NULL COMMENT '申请提现的商家ID',
  `count` decimal(10,0) NOT NULL COMMENT '提现金额',
  `apply_time` datetime NOT NULL COMMENT '申请时间',
  `status` int(1) NOT NULL COMMENT '提现的状态（0：申请中；1：审核通过；2：审核没通过）',
  `check_time` datetime DEFAULT NULL COMMENT '审核时间',
  `admin_user_id` int(11) DEFAULT NULL COMMENT '审核的管理员ID',
  `reason` varchar(255) NOT NULL COMMENT '提现的理由和核审不通过的理由',
  `after_balance` decimal(10,0) NOT NULL COMMENT '提现后剩余的余额，如果不加，同时提现多笔，提现后余额计算比较复杂',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Triggers structure for table tg_pay_record
-- ----------------------------
DROP TRIGGER IF EXISTS `change_status`;
delimiter ;;
CREATE TRIGGER `change_status` AFTER UPDATE ON `tg_pay_record` FOR EACH ROW BEGIN
if new.status!=old.status and new.status=1 then
      UPDATE tg_order SET status=1 WHERE out_trade_no=new.out_trade_no;
end if;
END
 ;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;
