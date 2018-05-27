=== Plugin Name ===
Contributors: tifosi
Donate link: https://on.tinternet.co.uk
Tags: eskimo, eskimoepos, eskimo api, epos, api, rest, wp rest, wp rest api, rest api, e-commerce
Requires at least: 4.4
Tested up to: 4.9
Stable tag: 4.9
Author: Stephen Betley
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The EskimoEPOS WordPress plugin integrates the EskimoEPOS e-commerce system into Woocommerce via connection to it's API via the WordPress REST API.

== Description ==

The EskimoEPOS WordPress plugin integrates the EskimoEPOS e-commerce system into Woocommerce via connection to it's API via the WordPress REST API.

- EPOS Product import 
- EOS Category import
- Simple and Variable product import
	- Stock management
	- SKU
	- 2-way WebID synchronisation
- Woocommerce Order EPOS export
	- Automatic EPOS stock update (by EPOS) from Woocommerce order lines
- Stock, Price & Tax product synchronisation (EPOS to Woocommerce)

The plugin uses WordPress best-practices and cutting-edge functionality to integrate the EskimoEPOS API into Woocommerce.
This uses the WordPress REST API to generate custom API Endpoints which map to the Eskimo EPOS system API core functionality.

Requires: WordPress 4.4+, Woocommerce 3+, PHP 5.4+

Example: http://domainxxx.com/wp-json/eskimo/v1/order-create/xxx

Analysis: Broken down this url comprises 3 parts:
http://domainxxx.com/wp-json/	- The path to the WordPress instance REST API base url
eskimo/v1						- The Eskimo plugin namespace. All custom endpoints are tied to this namespace
/order-create/xxx				- The API endpoint. In this example order-create and a woocommerce order ID (xxx)

All urls are permalink based and require that the WordPress permalinks are active i.e other than the WordPress query string default. WP-Admin > Settings > Permalink.

== Installation ==

1. Upload the `eskimo` directory to the `/wp-content/plugins/` directory or use the integrated plugin installer
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Woocommerce > Settings > Eskimo and add the Eskimo Username & Password
4. Set other API settings & enable the endpoints

== Eskimo Admin ==
The plugin creates a new 'Eskimo' tab in the Woocommerce settings panel: WP-Admin > Woocommerce > Settings. 

The EskimoEPOS settings tab contains API fields for the Eskimo API URL, and your Eskimo Username and Password. 
There are also additional custom Web_ID fields which prepend a value to the generated Eskimo EPOS product and category Web_ID fields, and Order ExternalReference field. 
These are used by the plugin as validation during import of EskimoEPOS products and categories, and export of Woocommerce orders, to avoid duplicates.

The Woocommerce EskimoEPOS REST API endpoints can also be enabled/disabled. 
It is recommended that this is disabled and only enabled when requiring to process an import, unless automatic order/stock synchronisation is required (see below).

Settings:
API Enabled		- Enable Woocommerce EskimoEPOS REST API Endpoints
API Username	- EskimoEPOS API Username
API Password	- EskimoEPOS API Password

Category Web_ID Prefix	- Prepended to category ID to create category Web_ID
Product Web_ID Prefix	- Prepended to category ID to create product Web_ID
Customer Web_ID Prefix	- Prepended to order ID & customer IDs to create a unique external reference

== Using the REST API ==
When the Eskimo REST API endpoints are made available, through the setting above, this generates a number of custom urls which can be used to connect to the EskimoEPOS API. 
These are detailed below:

== API Calls ==

https://domainurl.com/wp-json/eskimo/v1/categories 
https://domainurl.com/wp-json/eskimo/v1/category/(id) 
https://domainurl.com/wp-json/eskimo/v1/child-categories/(id) 
https://domainurl.com/wp-json/eskimo/v1/categories-update 
https://domainurl.com/wp-json/eskimo/v1/categories-reset 
https://domainurl.com/wp-json/eskimo/v1/categories-meta 
https://domainurl.com/wp-json/eskimo/v1/category-update/(id)/(value) 

https://domainurl.com/wp-json/eskimo/v1/category-products/(start)/(records) 
https://domainurl.com/wp-json/eskimo/v1/category-products-all 

https://domainurl.com/wp-json/eskimo/v1/products/(start)/(records)
https://domainurl.com/wp-json/eskimo/v1/products-all 
https://domainurl.com/wp-json/eskimo/v1/products-modified/(route)/(modified)/(start)/(records)
https://domainurl.com/wp-json/eskimo/v1/product/(id) 
https://domainurl.com/wp-json/eskimo/v1/products-update 
https://domainurl.com/wp-json/eskimo/v1/products-reset/(start)/(records)
https://domainurl.com/wp-json/eskimo/v1/product-update/(id)/(value) 
https://domainurl.com/wp-json/eskimo/v1/product-import/(type)/(id)

https://domainurl.com/wp-json/eskimo/v1/customer/(id)
https://domainurl.com/wp-json/eskimo/v1/customer-exists/(email) 
https://domainurl.com/wp-json/eskimo/v1/customer-insert/(id)
https://domainurl.com/wp-json/eskimo/v1/customer-update/(id)
https://domainurl.com/wp-json/eskimo/v1/customer-titles 

https://domainurl.com/wp-json/eskimo/v1/order/(id) 
https://domainurl.com/wp-json/eskimo/v1/order-insert/(id)
https://domainurl.com/wp-json/eskimo/v1/order-methods
https://domainurl.com/wp-json/eskimo/v1/order-search/customer/(id)
https://domainurl.com/wp-json/eskimo/v1/order-search/type/(type)
https://domainurl.com/wp-json/eskimo/v1/order-search/date/(route)/(date)/(date)

https://domainurl.com/wp-json/eskimo/v1/skus/(start)/(records)
https://domainurl.com/wp-json/eskimo/v1/skus-modified/(path)/(route)/(modified)/(start)/(records)/(import)
https://domainurl.com/wp-json/eskimo/v1/sku/id
https://domainurl.com/wp-json/eskimo/v1/sku-product/id/(import)

== API Call Details ==

** http://domainurl.com/wp-json/eskimo/v1/categories **
Imports EskimoEPOS Categories into Woocommerce. Restricted to 2-levels: Parent - Child. 

For every EskimoEPOS category imported into Woocommerce a unique Web_ID is generated which is exported to the EskimoEPOS category Web_ID field.
This is set as a custom Woocommerce meta-data value tied to the Woocommerce category. This is used to map EskimoEPOS categories to Woocommerce categories.

The Web_ID value is displayed as a column in the Categories listing page, single category edit page. 
Also as a field in the custom EskimoEPOS tab in the Product Data panel of the Woocommerce product edit page. 

Return value: JSON {"route":"categories","params":"all","nonce":"0753674aae","result":[]} 
Result is an empty array for no import, or displays an array list [ 'Eskimo_Category_ID' => {Eskimo_Category_ID}, 'Web_ID' => {Web_ID} ]
Used to update EskimoEPOS system Web_IDs.

** http://domainurl.com/wp-json/eskimo/v1/category/xxx **
Imports a single EskimoEPOS category into Woocommerce categories. The url takes the EskimoEPOS category ID as the parameter.

By default EskimoEPOS categories are pipe (|) delimited, e.g. 001|MAC||. This character is not a recommended unencoded url character. 
To keep urls clean and avoid manual encoding of the | character the urls replace the | character with a dash (-) e.g. 130|product -> 130-product. 
These are internally translated by the plugin, and stored as their original EskimoEPOS pipe delimited versions.

When the EskimoEPOS category is imported into Woocommerce a uniqie Web_ID setting is generated. 
This is exported to the EskimoEPOS category Web_ID field of the particular category. 
It is also set as a custom meta-data value tied to the Woocommerce category. 
The Web_ID setting is used to map the EskimoEPOS category to the Woocommerce category, and as validation for future category imports to avoid duplicates.

Return value: JSON {"route":"category","params":"cat_id: 130|product","nonce":"0753674aae","result":false}
Result is false for no import, or an array [ 'Eskimo_Category_ID' => {Eskimo_Category_ID}, 'Web_ID' => {Web_ID} ] 
Used to update the EskimoEPOS category Web_ID

** http://domainurl.com/wp-json/eskimo/v1/child-categories/xxx **
Imports child categories of a a single EskimoEPOS category into Woocommerce. The url takes the EskimoEPOS parent category ID as the parameter.

As with the /category/xxx endpoint the EskimoEPOS category url format should use dashes instead of pipe characters in the category ID e.g. 130|product -> 130-product.

As with the single category a Web_ID is automatically generated. This is exported to the EskimoEPOS system category. 
Also set as meta-data for the Woocommerce category, and used to map EskimoEPOS parent category to Woocommerce category. Used as validation for future imports to avoid duplicates.

Return value: JSON {"route":"child-category","params":"cat_id: 6|product","nonce":"0753674aae","result":[]}
Result is an empty array for no import, or displays an array list [ 'Eskimo_Category_ID' => {Eskimo_Category_ID}, 'Web_ID' => {Web_ID} ]
Used to update EskimoEPOS system Web_IDs.

** http://domainurl.com/wp-json/eskimo/v1/categories-update **
Updates the EskimoEPOS Category Web_IDs from the Woocommerce category values

This is generally a one-off API call. Useful when moving from am EskimoEPOS
test database to the live version where the 2 databases are copies of each
other. Avoids the need to reimport all the categories.

Return value: JSON {"route":"categories-update","params":"all","nonce":"0753674aae","result":[]}
Result is false for no categories, or an array list [ 'Eskimo_Category_ID' => {Eskimo_Category_ID}, 'Web_ID' => 0 ] 
Used to update the EskimoEPOS category Web_ID.

** http://domainurl.com/wp-json/eskimo/v1/categories-reset **
Resets all EskimoEPOS system Web_IDs. Warning, this can not be undone.

By default once an EskimoEPOS category is imported to Woocommerce a Web_ID is generated and exported to the EskimoEPOS system. 
This is also set as meta-data for the Woocommerce categor. This provides an entry point validation for subsequent imports. 
If all categories are required to be reimported e.g. when setting up a new Wordpress/Woocommerce instance then all Web_IDs need to be reset. 
Due to EskimoEPOS limitations the default reset value is 0 instead of null.

If a single category needs to be re-imported then use the single category endpoint below.

Return value: JSON {"route":"categories-reset","params":"all","nonce":"0753674aae","result":[]}
Result is false for no import, or an array list [ 'Eskimo_Category_ID' => {Eskimo_Category_ID}, 'Web_ID' => 0 ] 
Used to update the EskimoEPOS category Web_ID. Note the Web_ID is set to 0

** http://domainurl.com/wp-json/eskimo/v1/categories-meta **
Updates the Woocommerce EPOS category meta field from the EskimoEPOS Category values

Each Woocommerce category when imported from EskimoEPOS sets the EskimoEPOS
category ID as a custom term meta data field against the Woocommerce category
term. This is used to map EskimoEPOS categories against Woocommerce categories
for reference and to avoid import duplication.

This is generally a one-off API call. Useful when moving from am EskimoEPOS
test database to the live version where the 2 databases are copies of each
other. Avoids the need to reimport all the categories. It overwrites existing
Woocommerce values for the EskimoEPOS Category ID.

Return value: JSON {"route":"categories-meta","params":"all","nonce":"0753674aae","result":[]}
Result is false for no categories, or an array list [ 'Eskimo_Category_ID' => {Eskimo_Category_ID} ] 

** http://domainurl.com/wp-json/eskimo/v1/category-update/xxx/yyy **
Resets a single EskimoEPOS system category Web_ID. Warning, this can not be undone.

This allows an EskimoEPOS category Web_ID to be reset, or updated to a new value.
For example if the existing Woocommerce category Web_ID value has been manually changed. 

By default once an EskimoEPOS category is imported to Woocommerce a Web_ID is generated and exported to the EskimoEPOS system. 
This is also set as meta-data for the Woocommerce category. This provides validation entry-point for subsequent imports. 

If a category is to be reimported then the Web_ID value should be 0. 
As for single category import above, the EskimoEPOS category should use dashes in place of the pipe character. For example: 130|product -> 130-product.

Update: http://domainurl.com/wp-json/eskimo/v1/category-update/130-product/cxc-1234
Reset:  http://domainurl.com/wp-json/eskimo/v1/category-update/130-product/0

Return value: {"route":"category-update","params":"cat_id: 6|product","nonce":"0753674aae","result": false}
Result is false for no import, or an array [ 'Eskimo_Category_ID' => {Eskimo_Category_ID}, 'Web_ID' => {Web_ID} ] 
Used to update the EskimoEPOS category Web_ID. If the category Web_ID is to be reset then the Web_ID value is 0.

** http://domainurl.com/wp-json/eskimo/v1/category-products/xxx/yyy **
Imports all products by category and range. Deprecated, DO NOT USE. Use /products end-point below.

Historical implementation of the EskimoEPOS /category-products API url. Replaced by the more suitable /products API call. 
Imports by batch arange, with a start & end point, represented by xxx & yyy.

Return value: JSON {"route":"category-products","params":"range","range":"{start},{records}","nonce":"0753674aae","result":[]}
Result is false for no import, or an array [ 'Eskimo_Identifier' => {eskimo_identifier}, 'Web_ID' => {Web_ID} ];

** http://domainurl.com/wp-json/eskimo/v1/category-products-all **
Imports all products by category and range. Deprecated, do NOT use. Use /products-all below.

Historical implementation of the EskimoEPOS /category-products API url. Replaced by the more suitable /products-all API call. 
Imports all products by internal batches. Warning, very resource intensive!!

Return value: JSON {"route":"category-products-all","params":"range","range":"1,20","nonce":"0753674aae","result":[]}
Result is false for no import, or an array [ 'Eskimo_Identifier' => {eskimo_identifier}, 'Web_ID' => {Web_ID} ];

** http://domainurl.com/wp-json/eskimo/v1/products/xxx/yyy **
Imports all EskimoEPOS products by batch range, with a start & record count, represented by xxx & yyy. 
For example, importing 20 records starting at record 100: http://domainurl.com/wp-json/eskimo/v1/products/20/100.

Note, there is a limit on the number of records that can be imported in a batch. This is set to 50 for resource purposes. 
This represents the parent product count. If a product has variations e.g. 60 variations, all these will be imported and the import will increment by 1. 
For shops with lots of product variations small batch numbers are recommended. 

When EskimoEPOS products are imported into Woocommerce a Web_ID is generated and exported to the EskimoEPOS system. 
This is set in the relevent EskimoEPOS product Web_ID field, and used for validation & mapping for future imports. 
It is also set as meta-data for the imported Woocommerce product. Already imported products will be skipped during future imports.

The product and category Web_ID values for the Woocommerce product can be seen in the Product Listing page in Wordpress Admin > Products.
Also in the custom Eskimo tab in the Product Edit page product data section.
  
Return value: JSON {"route":"products","params":"range","range":"{start},{records}","nonce":"0753674aae","result":[]}
Result is false for no import, or an array list [ 'Eskimo_Identifier' => {eskimo_identifier}, 'Web_ID' => {Web_ID} ] 
Used for update of EskimoEPOS product Web_IDs;

** http://domainurl.com/wp-json/eskimo/v1/products-all **
Imports all Eskimo EPOS products. Note this is highly resource intensive. 
For imports containing a lot of products and/or product variations use the product batch import above. 
Should be used for small ( ~100s ) imports of products only, and simple or limited variations products only.

Uses internal batch importing, importing from the start to the finish. 
As each product is imported the Web_ID field is generated as below. 
The means that this can be run multiple times. Already imported products will be skipped.

When an EskimoEPOS product is imported into Woocommerce a Web_ID is generated and exported to the EskimoEPOS system. 
This is set in the EskimoEPOS product Web_ID field, and used for validation & mapping for future imports. 
It is also set as meta-data for the Woocommerce product, and dispayed in the Product Listing and Product Edit Wordpress Admin pages, as above.

Return value: JSON {"route":"products-all","params":"all","nonce":"0753674aae","result":[]}
Result is false for no import, or an array list [ 'Eskimo_Identifier' => {eskimo_identifier}, 'Web_ID' => {Web_ID} ] 
Used for update of EskimoEPOS product Web_IDs;

** http://domainurl.com/wp-json/eskimo/v1/products-modified/{route}/{modified}/{start}/{records} **
Retrieves a list of products modified by selected parameters. No import or data storage.

Whenever a product is bought via the EPOS system, or as a WebOrder and inserted into the EskimoEPOS system this sets the modified status on the
product, whether simple or variation. This API call can retrieved these for reference or further data manipulation.

Parameters:
'route': one of - seconds, minutes, hours, days, weeks,  months,  timestamp
'modified' : positive integer
'start': positive integer ( optional)
'records': positive integer ( optional)

Return value: JSON {"route":"products-modified","params":"all","nonce":"0753674aae","result":[]}
Result is false for no import, or an array list [ 'Eskimo_Identifier' => {eskimo_identifier}]


** http://domainurl.com/wp-json/eskimo/v1/product/xxx **
Imports a single EskimoEPOS product into Woocommerce. The url takes the EskimoEPOS product ID as the parameter.

By default EskimoEPOS products are pipe (|) delimited, e.g. 001|MAC||. This character is not a recommended unencoded url character. 
To keep urls clean and avoid manual encoding of the | character the urls replace the | character with a dash (-) e.g. 001|MAC|| -> 001-MAC--. 
These are internally translated by the plugin and stored as their original EskimoEPOS versions.

When the EskimoEPOS product is imported into Woocommerce a Web_ID is generated and exported to the EskimoEPOS system. 
This is exported to the EskimoEPOS product Web_ID field. It is also set as meta-data for the Woocommerce product, and displayed in the Product Listing and Product Edit Wordpress Admin pages. 
This is used for validation & mapping for future imports. Already imported products will be skipped in future imports.

Return value: JSON {"route":"product","params":"prod_id: {prod_id}","nonce":"0753674aae","result":[]}
Result is false for no import, or an array [ 'Eskimo_Identifier' => {eskimo_identifier}, 'Web_ID' => {Web_ID} ] for update of EskimoEPOS product Web_ID;

** http://domainurl.com/wp-json/eskimo/v1/products-update **
Updates the EskimoEPOS Product Web_IDs from the Woocommerce product values

This is generally a one-off API call. Useful when moving from am EskimoEPOS
test database to the live version where the 2 databases are copies of each
other. Avoids the need to reimport all the products. Note this works at the
product level and not the SKU level.

Return value: JSON {"route":"categories-update","params":"all","nonce":"0753674aae","result":[]}
Result is false for no categories, or an array list [ 'Eskimo_Category_ID' => {Eskimo_Category_ID}, 'Web_ID' => 0 ] 
Used to update the EskimoEPOS category Web_ID.

** http://domainurl.com/wp-json/eskimo/v1/products-reset/xxx/yyy **
Resets EskimoEPOS product Web_IDs. Processes by batch range, with start and record count. Warning, this can not be undone.

This allows a batch of product Web_IDs to be reset. Due to limitations with the EskimoEPOS system the reset value is 0 and not null, the original field value.

This would allow for reimporting of all products from EskimoEPOS to Woocommerce, for example if creating a new instance of WordPress / Woocommerce. 
For single product reimport use the product import below. For product record updates, use the product update below.

Return value: JSON {"route":"products-update","params":"range","range":"{start},{records}","nonce":"0753674aae","result":[]}
Result is false for no import, or an array list [ 'Eskimo_Identifier' => {eskimo_identifier}, 'Web_ID' => 0 ] 
Usedfor update of EskimoEPOS product Web_IDs. Note the reset value is 0;

** http://domainurl.com/wp-json/eskimo/v1/product-update/xxx/yyy **
Resets a single EskimoEPOS product Web_ID. Warning, this can not be undone.

By default EskimoEPOS products are pipe (|) delimited, e.g. 001|MAC||. This character is not a recommended unencoded url character. 
To keep urls clean and avoid manual encoding of the | character the urls replace the | character with a dash (-) e.g. 001|MAC|| -> 001-MAC--. 
These are internally translated by the plugin and stored as their original EskimoEPOS versions.

This allows a product Web_ID to be reset, or updated to a new value, for example if the existing Woocommerce product Web_ID value has been manually changed. 
If a product is to be reimported then the value should be 0. As above, the product should use a dash in place of the pipe character. For example:

Update: http://domainurl.com/wp-json/eskimo/v1/category-update/001-000001/cxp-1234-000
Reset:  http://domainurl.com/wp-json/eskimo/v1/category-update/001-000001/0

Return value: JSON {"route":"product-update","params":"prod_id:{prod_id}","prod_value: {prod_value}","nonce":"0753674aae","result":[]}
Result is false for no import, or an array [ 'Eskimo_Identifier' => {eskimo_identifier}, 'Web_ID' => {Web_ID} ] 
Used for update of EskimoEPOS product Web_IDs. Reset value is 0, Update value non-zero string;

** http://domainurl.com/wp-json/eskimo/v1/product-import/(type)/xxxx **
This allows partial import and update of an EskimoEPOS product into an existing Woocommerce product record by EskimoEPOS product ID.

This allows the update of stock qty, price, or tax values of a Woocommerce product from the equivalent EskimoEPOS record. 
The type of update is taken from the first parameter, and the selected EskimoEPOS record the second parameter, for example:
http://domainurl.com/wp-json/eskimo/v1/product-import/stock/001-000001
http://domainurl.com/wp-json/eskimo/v1/product-import/price/001-000001
http://domainurl.com/wp-json/eskimo/v1/product-import/tax/001-000001

By default EskimoEPOS products are pipe (|) delimited, e.g. 001|MAC||. This character is not a recommended unencoded url character. 
To keep urls clean and avoid manual encoding of the | character the urls replace the | character with a dash (-) e.g. 001|MAC|| -> 001-MAC--. 
These are internally translated by the plugin and stored as their original EskimoEPOS versions.

The Woocommerce value is selectively updated from the EskimoEPOS record. 
Useful to avoid full reimporting of the EskimoEPOS product to Woocommerce, for example if a product price has changed, or after a stock take, or change of tax status.

Return value: JSON {"route":"product-import","path":"{prod_type},"params":"prod_id:{prod_id}","nonce":"0753674aae","result":[]}
Result is false for no import, or an array [ 'Eskimo_Identifier' => {eskimo_identifier}, 'Web_ID' => {Web_ID} ];

** http://domainurl.com/wp-json/eskimo/v1/customer/xxx **
Imports an EskimoEPOS customer into Woocommerce by EskimoEPOS customer ID. 

In reality it is not particularly relevent to import an EskimoEPOS cutomer into Woocommerce, unless they are later to generate Web sales. 
Even in this situation due to EskimoEPOS having limited fields which don't exactly map the Woocommerce equivalents it is recommended that the customer is manually created through the WordPress Admin UI. 
The customer-update endpoint below can then be used to generate a customer ExternalReference to map Woocommerce customer to EskimoEPOS customer. Note that the key used for validation is the email address.

Return value: JSON {"route":"customer","params":"ID:{cust_id}","nonce":"0753674aae","result":[]}
Result is false for no import, or a string 'User ID[{$user_id}] Username[{$username}]';

** http://domainurl.com/wp-json/eskimo/v1/customer-exists/xxx **
Interrogates the EskimoEPOS system to test whether a customer exists.

This is done by customer email as the primary key, for example:
https://domainurl.com/wp-json/eskimo/v1/customer-exists/email@domainurl.com

Return value: JSON 
{"route":"customer-exists","params":"Email: trutex@macclesfield.com","nonce":"c77e17e4f6","result":false}

Result is false for no customer and true for an existing customer.

** http://domainurl.com/wp-json/eskimo/v1/customer-insert/xxx **
Exports a Woocommerce customer to EskimoEPOS by Woocommerce customer ID.

This takes a WordPress/Woocommerce user - containing standard WordPress user details, and Woocommerce billing and shipping details and exports to EskimoEPOS. Validation is by email address. If this is already present in the EskimoEPOS system then import will fail.

Return value: JSON {"route":"customer-create","params":"ID:{cust_id}","nonce":"0753674aae","result":[]}
Result is false for no import, or a string 'ID[{$user_id}] EPOS ID[{$epos_customer_id}]';

** http://domainurl.com/wp-json/eskimo/v1/customer-update/xxx **
Updates an EskimoEPOS customer's record on the EskimoEPOS system if previously exported from Woocommerce, or if the EskimoEPOS user already existed. 
The validation is done via the customer email address.

Takes a WordPress/Woocommerce user - containing standard WordPress user details, and Woocommerce billing and shipping details. 
Exports to EskimoEPOS. Validation is by email address. If this is already present in the EskimoEPOS system then the update will overwrite all valid fields with the exported record. 
Note, this cannot be undone.

Return value: JSON {"route":"customer-update","params":"ID:{cust_id}","nonce":"0753674aae","result":[]}
Result is false for no import, or a string 'ID[{$user_id}] EPOS ID[{$epos_customer_id}]';

** http://domainurl.com/wp-json/eskimo/v1/customer-titles **
Retrieves a list of customer user titles. No import

Reference only list of customer emails as set in the EskimoEPOS system

Return value: JSON: {"route":"customer_titles","params":"none","nonce":"c77e17e4f6","result":[{"TitleID":1,"TitleValue":"Mr","Active":true}]}

** http://domainurl.com/wp-json/eskimo/v1/order/xxx **
Retrieve an EskimoEPOS order. No import, reference only. No real world implementation for this. 
Takes the EskimoEPOS order ID as the parameter. 

Return value: JSON {"route":"order","params":"Order ID:{order_id}","nonce":"0753674aae","result":[]}
Result is false for no import, or a string 'Order ID[{order_id}]';

** http://domainurl.com/wp-json/eskimo/v1/order-insert/xxx **
Exports a Woocommerce order to EskimoEPOS by woocommerce order ID. Takes the woocommerce order ID as the parameter. 

An autogenerated unique ExternalIdentifier field consisting of the Eskimo customer API prefix, EskimoEPOS customer ID, 
Woocommerce customer ID, and order number / ID is generated to map the Woocommerce order to the EskimoEPOS order. 

This is set in the EskimoEPOS order ExternalIdentifier field, and also as meta data for the Woocommerce order. 
It is used as validation for future exports. If this field is already filled in the Woocommerce order the export will fail.

The EskimoEPOS system is responsible for real-time tracking of the stock levels from the exported order. 
Products from the order lines should update the EskimoEPOS stock qty. The Woocommerce stock level can then be updated as required by using the /product-update endpoint as above.

Return value: JSON {"route":"order_create","params":"Order ID: #26319","nonce":"911c636231","result":"ID[26319] EPOS WebOrder ID[cxcu-001-000004-5-26319]"} In this case customer prefix: cxcu-, eskimo epos customer: 001-000004, Woocommerce customer ID: 5, Woocommerce order ID: 26319. 

** https://domainurl.com/wp-json/eskimo/v1/order-methods **
Retrieves a list of EskimoEPOS order fulfilments methods. These match the
Woocommerce shipping methods

Return value: JSON {"route":"order_methods","params":"none","nonce":"c77e17e4f6","result":[{"ID":1,"Active":true,"Description":"Click and collect","Rates":[{"ID":1,"BasketWeightFrom":null,"BasketWeightTo":null,"BasketWeightMeasure":null,"BasketValueFrom":null,"BasketValueTo":null,"BasketItemsCountFrom":1,"BasketItemsCountTo":99999,"NetChargeToCustomer":0,"GrossChargeToCustomer":0,"TaxCode":{"TaxID":2,"TaxDescription":"Zero Rated","TaxRate":0},"Active":true}],"CourierCompany":null}]}

** https://domainurl.com/wp-json/eskimo/v1/order-search/customer/xxx **
Retrieve a list of orders from the EskimoEPOS system by customer

Takes the EskimoEPOS customer ID e.g. 001-000009

Return: JSON:
{"route":"order-search","path":"customer","params":"Customer ID:
#001-000009","nonce":"a44d017e9f","result":[]}

Empty for no orders found or an EskimoEPOS Order object containing order,
order line, and customer data

** https://domainurl.com/wp-json/eskimo/v1/order-search/type/xxx **
Retrieve a list of orders from the EskimoEPOS system by type

Takes the EskimoEPOS customer ID e.g. 001-000009

Return: JSON:
{"route":"order-search","path":"type","params":"Type ID: #2","nonce":"a44d017e9f","result":[]}

Empty for no orders found or an EskimoEPOS Order object containing order,
order line, and customer data

** https://domainurl.com/wp-json/eskimo/v1/order-search/date/{route}/{date_from}/{date_to}/ **
Retrieve a list of orders from the EskimoEPOS system by type

Takes the EskimoEPOS date route, and one or two dates:

Valid route parameters: from, to, range. Date should be in mysql format:
YYYY-mm-dd. From and To take a single date, range takes two dates. e.g.

https://domainurl.com/wp-json/eskimo/v1/order-search/date/range/2018-01-01/2018-01-31
https://domainurl.com/wp-json/eskimo/v1/order-search/date/from/2018-01-01
https://domainurl.com/wp-json/eskimo/v1/order-search/date/to/2018-01-01

Return: JSON:
{"route":"order-search","path":"date","method":"range","params":"From: 2018-01-01 To: 2018-01-31","nonce":"a44d017e9f","result":"DATA: No Results Returned"}

** https://domainurl.com/wp-json/eskimo/v1/skus/(start)/(records) **
Retrieve a list of SKUs with a start and record count. No import.

Get a list of SKU's and checks if these have been imported. Returns a synopsis
list of skus that have not been imported. These can be used to import the
parent product.

{"route":"skus","params":"range","range":"1,25","nonce":"a44d017e9f","result":"DATA: No Product SKUs To Process"}

Empty for no SKUs to process, or a data set of SKUs to import

** https://domainurl.com/wp-json/eskimo/v1/skus-modified/(path)/(records)/(modified)/(start)/(records)/(import) **
Retrieve a list of SKUs which have been modified by date range

Valid parameters:
'path' 		- all, stock, price
'route' 	- seconds, minutes, hours, days, weeks, months, timestamp
'modified'	- positive integer
'start' 	- positive integer
'records'	- positive integer
'import'	- positive integer 0|1

By default the API call retrieves the first 250 SKUs from the query by
selected parameters. The return value is s synopsis of parent EskimoEPOS
product ID, and SKU stock qty, price, and tax code. e.g.

https://domainurl.com/wp-json/eskimo/v1/skus-modified/all/days/1

Can be used in partneship with the product-import/xxx API call to import
selected product data by product.

Return: JSON :
{"route":"skus-modified","params":"range","range":"all,days,1,1,1000,0","nonce":"a44d017e9f","result":[{"Eskimo_Product_Identifier":"1|AIBO||","SKU":"183590","StockAmount":1,"SellPrice":6.25,"TaxCodeID":1}]}

** https://domainurl.com/wp-json/eskimo/v1/sku/xxx **
Retrieve an SKU by ID & import if not already attached to a Woocommerce
product.

Gets an SKU's details from the EskimoEPOS system. If the SKU has already been
imported no further processing done, otherwise the SKU is imported via the parent product

{"route":"sku","path":"code","params":"sku_id: 183590","nonce":"a44d017e9f","result":"DATA: No Product SKUs To Process"}

Empty result if not imported, else a data set on the sku - product details.

** https://domainurl.com/wp-json/eskimo/v1/sku-product/xxx/(import) **
Retrieve a list of a products SKUs and optionally import

By default EskimoEPOS categories are pipe (|) delimited, e.g. 001|MAC||. This character is not a valid unencoded url character. To keep urls clean and avoid manual encoding of the | character the urls replace the | character with a dash (-) e.g. 130|product -> 130-product. 

These are internally translated by the plugin, and stored as their original EskimoEPOS pipe delimited versions.

Return: JSON:
{"route":"sku","path":"product","params":"prod_id: 1|mac||, import:
0","nonce":"a44d017e9f","result":[{"eskimo_product_identifier":"1|MAC||","trade_customer_id":null,"sku_code":"24128030","Style_Reference":"MAC","ColourID":"BP","ColourName":"Blue
Stripe","Size":"30","CostPrice":9,"SellPrice":20,"StockAmount":8,"TaxCodeID":2,"PersonalisationPrompt":null}]}

== Screenshots ==

== Changelog ==

= 1.1 =
* Add customer and order import
* Various code optimisations and updates

= 1.0 =
* Initial plugin. Basic product & category import
