
Asiabill Magento1 支付插件
=

插件安装
-

1、把app和skin两个目录上传到站点根目录。

2、清除magento缓存：System -> Cache Management

3、设置：Stores -> Configuration -> Sales -> Payment Methods 可以看到：Asiabill Creditcard

![image](https://files.gitbook.com/v0/b/gitbook-x-prod.appspot.com/o/spaces%2FcSYgMg71VCxeEVhWhVFp%2Fuploads%2FKhMI063oWtkX5BJJZuoF%2Fmagento1-admin-list.png?alt=media&token=1ab55452-d6cd-4f3e-beed-2f8c9bfe158e)

* Version：版本信息
* Enabled：是否开启
* Title：显示支付方式名称
* Mode：模式
  * test：测试
  * live：正式
* Test Mer No、Test Gateway No、Test Sign Key：测试账户信息，测试模式下使用，默认已设置
* Mer No、Gateway No、Sign Key：账户信息，非测试模式下使用
* Checkout Model：支付模式
  * In-page Checkout ：内嵌表单模式
* Elements Style：内嵌表单样式，单行/双行
* Order status when success：交易成功更新订单状态
* Order status when failure：交易失败更新订单状态
* Order status when pending：待处理订单更状态
* Payment from applicable countries：适用支付国家
* Payment from Specific countries：选择国家
* Sort order：排序


信用卡支付
-
* 在购物网站选择商品到结算页面
* 选择信用卡支付 ，支付页面将内嵌到商户支付列表中，有利于支付体验
* 直接在商户网站输入卡信息，点击Continue按钮

![images](https://files.gitbook.com/v0/b/gitbook-x-prod.appspot.com/o/spaces%2FcSYgMg71VCxeEVhWhVFp%2Fuploads%2FUu5fnHvBfX1AtdWhMSB6%2Fmagento1-inner-payment.png?alt=media&token=440f0002-c4b0-4bba-816c-531aa2e26963)

测试卡号
-
* 支付成功：4000020951595032
* 支付失败：4000000000009995
* 3D交易：4000002500003155
