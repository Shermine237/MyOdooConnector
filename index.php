
<?php
	//Enable debug
	/*ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);*/
	
	//---------------------------------------------------------------------------------------
	// odoo data
	require_once('ripcord-master/ripcord.php');
	
	$odoo_url = "https://siaptest.odoo.com";
	$odoo_db = "siaptest";
	$odoo_username = "charlie.yossa@sample.com";
	$odoo_password = "1e67784626d33e72c9dbe8ea48e221d56313ee32";
	
	// woocommerce data
	require __DIR__ . '/vendor/autoload.php';
	//use Automattic\WooCommerce\Client;
	//use Automattic\WooCommerce\HttpClient\HttpClientException;
	
	$woo_url = 'https://sample.info';
	$woo_consumer_key = 'ck_96734546235a0be1fe0bde48af5bbf128367e8ba';
	$woo_consumer_secret = 'cs_f796c3159489387e975a1b8fff0fce8827b3d8d6';
	
	// server data
	$connector_location = $_SERVER['DOCUMENT_ROOT'].'/MyOdooConnector';
	$connector_link ='https://sample.info/MyOdooConnector';

	//--------------------------------------------------------------------------
	
	//My functions
	function odoo_Authentication($url, $db, $username, $password)
	{
		$common = ripcord::client($url.'/xmlrpc/2/common');
		$uid = $common->authenticate($db, $username, $password, array());
		if (empty($uid))
		{
			echo "Erreur d'authentification sur Odoo";
			exit();
		}
		return $uid;
	}
	
	function odoo_Create_user($name, $login, $password)
	{
		global $odoo_url, $odoo_db, $odoo_username, $odoo_password;

		$admin_uid = odoo_Authentication($odoo_url, $odoo_db, $odoo_username, $odoo_password);
		$models = ripcord::client($odoo_url.'/xmlrpc/2/object');
		$user_id = $models->execute_kw
		(
			$odoo_db,
			$admin_uid,
			$odoo_password,
			'res.users',
			'create',
			array
			(
				array
				(
					'name' => $name,
					'login' => $login,
					'company_ids' => [1],
					'company_id' => 1,
					'new_password' => $password
				)
			)
		);
		if (empty($user_id))
		{
			echo "La reation de l'utilisateur a echoue";
			exit();
		}
		return $user_id;
	}

	function odoo_Create_Invoice($woo_products_list)
	{
		global $odoo_url, $odoo_db, $odoo_username, $odoo_password;

		$user_uid = odoo_Authentication($odoo_url, $odoo_db, $odoo_username, $odoo_password);
		$models = ripcord::client($odoo_url.'/xmlrpc/2/object');
		$invoice_id = $models->execute_kw
		(
			$odoo_db,
			$user_uid,
			$odoo_password,
			'sale.order',
			'create',
			array
			(
				array
				(
					'partner_id' => 1,
					'validity_date' => "2021-08-09",
					'state' => 'sale',
					'order_line' => [
										[
											0, 0, ['product_id' => 2, 'product_uom_qty' => 2, 'price_unit' => 10.00]
										],
										[
											0, 0, ['product_id' => 5, 'product_uom_qty' => 4, 'price_unit' => 20.00]
										]
									]
				)
			)
		);
		if (empty($invoice_id))
		{
			echo "Erreur de creation de la facture sur Odoo";
			exit();
		}
		return $invoice_id;
	}
	
	function woo_Authentication($url, $consumer_key, $consumer_secret)
	{
		$woo_options = [
			'wp_api' => true,
			'version' => 'wc/v3',
			'query_string_auth' => true // Force Basic Authentication as query string true and using under HTTPS
		];
		$woocommerce = new \Automattic\WooCommerce\Client ($url, $consumer_key, $consumer_secret, $woo_options);
		if (empty($woocommerce))
		{
			echo "Erreur d'authentification sur WooCommerce";
			exit();
		}
		return $woocommerce;
	}
	
	function clearTmp($folder)
	{
		$files = glob($folder.'*'); // get all file names
		foreach($files as $file)
		{
			if(is_file($file))
			{
			unlink($file); // delete file
			}
		}
	}
	
	function convBase64ToImg($img_base64, $path)
	{
		global $connector_location, $connector_link;

		if( !empty($img_base64) )
		{
			//get extension
			switch ($img_base64[0]) //determine extension with first caracter of base64
			{
				case '/':
				$extension = '.jpeg';
				break;
				case 'i':
				$extension = '.png';
				break;
				case 'R':
				$extension = '.gif';
				break;
				case 'U':
				$extension = '.webp';
				break;
				case 'J':
				$extension = '.pdf';
				break;
				default:
				$extension = '.unknown';
			}
			
			$image_name = md5(uniqid()).$extension;	//Generate name
			
			$store_at = $connector_location.$path.$image_name;	//to store image datas
			$decoded_string = base64_decode( $img_base64 );		//decoding
			file_put_contents( $store_at, $decoded_string );	// store data
			
			return $connector_link.$path.$image_name;
		}

		return $connector_link.'/default.jpg';
	}
	
	function create_product($woocommerce, $p_name, $p_regular_price, $p_qty, $p_imageBase64, $p_sku, $p_short_description ='')
	{
		$data = 
		[
			'name' => $p_name,
			'type' => 'simple',
			'regular_price' => sprintf($p_regular_price),
			'manage_stock' => True,
			'stock_quantity' => intval($p_qty),
			'short_description' => $p_short_description,
			'images' => 
			[
				[
					'src' => convBase64ToImg($p_imageBase64, '/images_tmp/')
				]
			],
			'sku' => sprintf($p_sku)/*,
			'description' => '',
			'categories' => 
			[
				[
					'id' => 9
				],
				[
					'id' => 14
				]
			]*/
		];

		$woocommerce->post('products', $data);
	}

	function update_product($woocommerce, $product_id, $p_regular_price, $p_qty, $p_imageBase64)
	{
		$data =
		[
			'regular_price' => sprintf($p_regular_price),
			'stock_quantity' => intval($p_qty)/*,
			'images' => 
			[
				[
					'src' => convBase64ToImg($p_imageBase64, '/images_tmp/')
				]
			]*/
		];

		$woocommerce->put('products/'.$product_id, $data);
	}

	function delete_product($woocommerce, $product_id)
	{
		$woocommerce->delete('products/'.$product_id, ['force' => true]);
	}
	
	//--------------------------------------------------------------------            

	// Authentication to odoo
	$odoo_uid = odoo_Authentication($odoo_url, $odoo_db, $odoo_username, $odoo_password);

	// read odoo database
	$models = ripcord::client($odoo_url.'/xmlrpc/2/object');
	$odoo_products = $models->execute_kw
	(
		$odoo_db,
		$odoo_uid,
		$odoo_password,
		'product.template',
		'search_read',
		array(),
		array
		(
			'fields'=>array
			(
				'id', 'name', 'list_price', 'qty_available', 'categ_id', 'image_1920'/*, 'standard_price', 'qty_available', 'default_code', 'product_tag_ids'*/
			)
		)
	);

	// Authentication to woocommerce
	$woocommerce = woo_Authentication($woo_url, $woo_consumer_key, $woo_consumer_secret);
	
	// sync odoo - woocommerce
	$woo_products = $woocommerce->get('products'); //Get all woocommerce products
	$woo_list_products_names = array(); //To store all products names
	foreach ($woo_products as $woo_product)
	{
		array_push($woo_list_products_names, $woo_product->name);
	}
	echo '<br/>';

	$odoo_list_products_names = array(); //To store all odoo products name
	foreach ($odoo_products as $odoo_product)
	{
		$odoo_product = json_decode(json_encode($odoo_product));
		array_push($odoo_list_products_names, $odoo_product->name);
		
		if (in_array($odoo_product->name, $woo_list_products_names))
		{
			foreach ($woo_products as $woo_product)
			{
				if ($woo_product->name == $odoo_product->name)
				{
					if ($woo_product->regular_price != sprintf($odoo_product->list_price) || $woo_product->stock_quantity != intval($odoo_product->qty_available))
					{
						update_product
						(
							$woocommerce,
							$woo_product -> id,
							$odoo_product -> list_price,
							$odoo_product -> qty_available,
							$odoo_product -> image_1920
						);
						echo $woo_product->name.' mit a jour<br/>';
					}
				}
			}
		}
		else
		{
			if(implode($odoo_product->categ_id) == '4ParaPharcie' && $odoo_product->qty_available > 0)
			{
				create_product
				(
					$woocommerce,
					$odoo_product -> name,
					$odoo_product -> list_price,
					$odoo_product -> qty_available,
					$odoo_product -> image_1920,
					$odoo_product -> id
				);
				echo $odoo_product -> name.' cree<br/>';
			}
		}
		
	}
	
	$woo_products = $woocommerce->get('products');
	foreach ( $woo_products as $woo_product )
	{
		if ( ! in_array($woo_product->name, $odoo_list_products_names))
		{
			delete_product($woocommerce, $woo_product->id);
		}
	}
	// clear cache and clean tmp files
	clearTmp('images_tmp/');
