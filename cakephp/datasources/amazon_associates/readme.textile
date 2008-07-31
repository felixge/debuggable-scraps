h1. CakePHP Amazon Associates Data Source

A CakePHP datasource for interacting with the amazon associates API. You can do cool stuff like finding images for Movies, Books and other products with it and then earn commisions on referals via Amazon's associate program.

If you don't have an amazon API key you can get one here: Get "Amazon API key":https://www.amazon.com:443/gp/redirect.html/ref=sc_fe_r_1_3435361_1?location=https://aws-portal.amazon.com/gp/aws/developer/registration/index.html/103-7399647-0537426%3f&token=44658695A979E38DF758108C1B9D591E9BA586ED

h2. Usage / Docs

h3. Installing

1. Copy amazon_associates_source.php to app/models/datasources/<br>
2. Open your app/config/database.php and add the following:

<pre><code>
var $amazon = array(
	'datasource' => 'amazon_associates',
	'key' => 'your-amazon-key'
);
</code></pre>

3. Create app/controllers/amazon_controlle.php with the following code:

<pre><code>
class AmazonController extends AppController{
	var $uses = array();

	function index() {
		// Only needed if no Model has been loaded so far
		App::import('ConnectionManager');

		$amazon = ConnectionManager::getDataSource('amazon');
		$response = $amazon->find('DVD', array('title' => 'The Devil and Daniel Johnston'));
		debug($response);
		exit;
	}
}
</code></pre>

4. Access the _/amazon_ action of your app and look at the resultset.<br>
5. Take a look at the "available query options":http://docs.amazonwebservices.com/AWSECommerceService/2008-06-26/DG/index.html?USSearchIndexParamForItemsearch.html.

h2. Missing Features

* Implement missing "operations":http://docs.amazonwebservices.com/AWSECommerceService/2008-06-26/DG/index.html?CHAP_OperationListAlphabetical.html (fork contributions welcome!):
** BrowseNodeLookup
** CartAdd
** CartClear
** CartCreate
** CartGet
** CartModify
** CustomerContentLookup
** CustomerContentSearch
** Help
** ItemLookup
** ListLookup
** ListSearch
** SellerListingLookup
** SellerListingSearch
** SellerLookup
** SimilarityLookup
** TagLookup
** TransactionLookup
* Read associates tag from db config
* Possibly other things, see "Amazon API documentation":http://docs.amazonwebservices.com/AWSECommerceService/2008-06-26/DG/index.html?CHAP_ApiReference.html

h2. Known Bugs

None