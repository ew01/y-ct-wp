=== Yubico Code Test in Wordpress ===
Contributors: ew01

This Wordpress plugin has been written in response to the PHP Developer technical challenge from Yubico.

Instructions for the challenge can be found in the pdf sent to GitHub User ew01.

== Description ==
This plugin has an API endpoint that posts a json array to it's second API endpoint which then displays the json.

Future updates will submit that json to the database as a new record, and eventually make other checks as well to fulfill the requirements of the challenge.

== Suggestions for improvement ==
General:

I think a full module for customer profiles would be in order, while I did create a table to store addresses, I found that it would be more of a headache to try to store and associate
the addresses with a customer based on the simple json posts this api is designed to handle at this point. So instead I approached it from there as more of a guest checkout.
As a guest checkout process, we are ensuring there is no dupe order with in 5 minutes, and we are making sure the products being ordered are in fact products we have, as well as validating
the QTY being passed is a number. To make this possible, I created a line items table, and stored the line items from the sample json. This is now what any new post is compared to.
Further improvements to this would be the following: Inventory tracking so that products are not sold in excess of what is available, or in the cases where the products are made to order,
a notification system to alert the manufacturing department. I would also suggest having the price of the products stored in the line items table, so that if this json post were to also
be in charge of passing pricing, that could be verified as well.

Security:
While the API is currently using _real_escape to escape special characters before inserting any into the database, or in some cases just verifying a field is what it should be, some
regex could potentially be added to look for and remove any common sql injection patterns. Further, a proper user registration system, and storage of their addresses would also increase
security as we could then pull the user's information by record IDs, if those IDs are just numbers, they would be much easier to check for SQL injection.
The api that displays the users order status, while very simple, presents a possible security issue since it is not behind an authentication wall.
I believe the biggest step that could be taken is to add authentication, even if it is not the user authentication, there should be some kind of api key that is passed along so that
this api will only respond to authorized requests. As it stands now, anyone with enough knowledge could post data to this api, and get back the responses.
One other step would be to create a split API, one that is used for the full CRUD operation, and one that only reads. The read one would be used for the un-authenticated requests, and the
SQL statements in that part of the api must select fields intentionally, and not use SELECT *. This way, anywhere the results may be seen, it can only contain fields with less sensitive
information, such as the status of an order, and not all of the order details. While this would not stop a brute for attack for finding the unique view ids, it would prevent the returned data
from being anything more than a simple statement such as 'The order is in processing.' and the like. Further, having the APIs split, and at different URLs, we could effectively hide
the API that has full CRUD access, thus reducing the potential for being targeted by hackers, this is not to replace any other precautions, just an extra that could be worth taking.

== Frequently Asked Questions ==
N/A

== Changelog ==
= 0.0.4 =
* Added more handoffs to wpdb to run _real_escape with the SQL connection object. Now preps shipping and billing address.
* Check for allowed payment method.

= 0.0.3 =
* The test place order endpoint can now accept extra params to slightly modify the customer  (email), products, and payment auth to generate unique orders.
** https://dev.ellenburgweb.host/yct/yct_api/v1/place/ (customer=1&payment=1&product=1)
** Not using the extra params will create the sample json as an order
* We are now verifying the customer email is in valid format, and cleaning it up for sql insertion.
* Order Processor
** Now sorts the products by sku and looks for a dupe order with in the last 5 minutes. If found alerts the user.
** Check that all the products in the cart are real products in our database.
** Upon successful order creation, we generate a unique customer view id based on the id of the order record.
* Added a new endpoint that accepts a view id, and then displays the status for that order.
* All error checks and other results are now passed off to the response method to returning the json string to the requester.

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


