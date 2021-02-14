# Installation steps
- in your magento project do following on base dir: 

1) in root project folder open app/code/ (if in app folder is no "code" folder is needed to create it)

2) copy here Extracted folders fith files (Spro/AplazoPayment/)

3) enable module: php bin/magento module:enable Spro_AplazoPayment 

4) php bin/magento setup:upgrade
   
5) do project redeploy (bin/magento set:di:co bin/magento s:s:d locales)

- Go to Admin panel of website, Stores->Configutation->Sales->Payment Methods. Find there Aplazo Payment
- Obtain Api token and Merchant id in your Aplazo Account, put into corresponding config fields
- Enable payment method and clear cache.
- New method should appear on checkuout
