=== Yubico Code Test in Wordpress ===
Contributors: ew01

This Wordpress plugin has been written in response to the PHP Developer technical challenge from Yubico.

Instructions for the challenge can be found in the pdf sent to GitHub User ew01.

== Description ==
This plugin has an API endpoint that posts a json array to it's second API endpoint which then displays the json.

Future updates will submit that json to the database as a new record, and eventually make other checks as well to fulfill the requirements of the challenge.

== Frequently Asked Questions ==
N/A

== Changelog ==
= 0.0.2 =
No major changes, just a quick commit to show some of the code I put in place when designing a new process.
Currently working on the check to make sure the customer has not ordered the same cart again with in a short time, specifically working on the time stamp,
and making sure everything stays in the same timezone for proper comparison.
= 0.0.2 =
* Added tables to handle the following data types: Customer, Addresses, Products, Orders
* The posted json array is now processed into a Customer and Order table
* Order process does the following as of this version
** Check for existing Customer, if customer does not exist, create. Customer name is pulled from bill to address.
*** When looking for existing customer, we are looking for the email address, assuming we are not allowing the same email address on multiple customer records.
** Create the order
*** Add the Customer record ID to the order based on the new customer record, or the found record.
*** Check tha the payment auth is new
*** Insert cart as json into the appropriate field.
*** Insert billing and shipping address as json into the appropriate field.

= 0.0.1 =
In this first version/commit, the API has been constructed with the bare essentials to post the test json and to accept it and display as proof of concept.


