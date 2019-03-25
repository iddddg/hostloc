# hostloc
hostloc 全球主机交流论坛 刷分网页(html) 刷分脚本(php)

html网页直接扔在web服务器访问就行了
php的话放定时任务起就行了，不建议网页访问，可能超时。例：`0 6 * * * php /home/www/index.php >> /home/www/hostloc.log`

## New
论坛偶尔防CC误伤，所以增加了签到(登录)失败的推送通知

两个渠道的SCKEY获取方法

### Server酱
1. 打开[http://sc.ftqq.com](http://sc.ftqq.com)
2. 使用GitHub账号一键登录
3. 在发送消息界面获取到你的SCKEY


### TgBot
1. Telegram关注 @onePushBot
2. 发送/start即可获取到你的SCKEY

粘贴到index.php文件中对应的位置

后面考虑做一个自动签到平台，问题用户放心把账号放我这吗？显然不会，再说吧

就这样溜了
