<?php

require_once './dbconfig.php';

$xml_file_name = "products.xml";

try {
	$pdo = new PDO(
		"mysql:host={$host};dbname={$dbname}",
		$username,
		$password,
	);


} catch (PDOException $pe) {
	die("Could not connect to the database $dbname :" . $pe->getMessage());
	exit;
}

$dom = new DOMDocument('1.0', 'utf-8');
$dom->formatOutput = true;
$root = $dom->createElement('root');

$item_query = buildQuery($pdo, "SELECT * FROM product");

while ($row = $item_query->fetch()) {
	$item_node = $dom->createElement('item');
	$model = $row['model'];
	$model_node = $dom->createElement('model', $model);

	$item_node->appendChild($model_node);

	$status = $row['status'];
	$status_node = $dom->createElement('status', $status);

	$item_node->appendChild($status_node);

	$name_node = $dom->createElement('name');

	$product_id = $row['product_id'];

	$lang_query = buildQuery($pdo, "SELECT * FROM product_description WHERE product_id={$product_id}");


	$descriptions = [];

	while ($item_description = $lang_query->fetch()) {
		$lang = getLanguage($item_description['language_id']);
		$description_text = $item_description['description'];
		if (strlen($description_text) > 200) {
			$description_text = wordwrap($description_text, 197);
			$description_text = substr(
				$description_text,
				0,
				strpos($description_text,"\n")) . '...';
		}

		$descriptions[] = [
			'lang' => $lang,
			'description' => $description_text
		];

		$local_name_node = $dom->createElement($lang, $item_description['name']);
		$name_node->appendChild($local_name_node);
	}

	$item_node->appendChild($name_node);

	$description_node = $dom->createElement('description');

	foreach ($descriptions as $item) {
		$local_description_node = $dom->createElement($item['lang'], $item['description']);

		$description_node->appendChild($local_description_node);
	}
	$item_node->appendChild($description_node);

	$quantity_node = $dom->createElement('quantity', $row['quantity']);
	$item_node->appendChild($quantity_node);

	$ean_node = $dom->createElement('ean', $row['ean']);
	$item_node->appendChild($ean_node);

	$image_url_node = $dom->createElement('image_url',
		'https://www.webdev.lv/' . $row['image']);
	$item_node->appendChild($image_url_node);

	$date = date("d-m-Y", strtotime($row['date_added']));

	$date_node = $dom->createElement('date_added', $date);
	$item_node->appendChild($date_node);

	$price = $row['price'];
	$price_node = $dom->createElement('price',
		number_format($price + $price * 21 / 100, 2));
	$item_node->appendChild($price_node);

	$special_query = buildQuery($pdo, "SELECT * FROM product_special WHERE product_id={$product_id}");

	if ($special_query) {
		$special = $special_query->fetch();
		$date = strtotime($special['date_end']);
		if ($date >= time()) {
			$date = date("d-m-Y", $date);

			$price = $special['price'];
			$special_price_node = $dom->createElement('special_price',
				number_format($price + $price * 21 / 100, 2));
			$item_node->appendChild($special_price_node);
		}
	}

	$category_query = buildQuery($pdo, "SELECT * FROM category_description WHERE category_id in (SELECT category_id from product_to_category WHERE product_id={$product_id} AND language_id=1)");
	$category = $category_query->fetch();

	$category_node = $dom->createElement('category', $category['name']);
	$item_node->appendChild($category_node);


	$category_id = $category['category_id'];

	$category_path_id_query = buildQuery($pdo, "SELECT path_id FROM category_path WHERE category_id={$category_id}");
	$categories_id = [];
	foreach ($category_path_id_query->fetchAll() as $id) {
		$categories_id[] = $id['path_id'];
	}

	$categories_id = implode(', ', $categories_id);

	$category_names_query = buildQuery($pdo, "SELECT name FROM category_description WHERE language_id=1 AND category_id IN ({$categories_id}) ORDER BY category_id");

	$category_names = [];
	foreach ($category_names_query->fetchAll() as $category) {
		$category_names[] = $category['name'];
	}

	$category_names = implode(" >> ", $category_names);


	$Full_category_node = $dom->createElement('full_category', $category_names);
	$item_node->appendChild($Full_category_node);

	$root->appendChild($item_node);
}


$dom->appendChild($root);
$dom->save($xml_file_name);


function buildQuery(PDO $pdo, string $query)
{
	try {
		$q = $pdo->query($query);

		$q->setFetchMode(PDO::FETCH_ASSOC);
		return $q;

	} catch	(Exception $e) {
		echo "Unexpected error:" . $e->getMessage();
	}
	return false;
}

function getLanguage($id): string
{
	switch ($id) {
		case 1:
			return 'lv';
		case 2:
			return 'en';
		case 3:
			return 'ru';
	}
	return '';
}
