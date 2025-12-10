<?php
namespace CkcConfigurator\Options;

use CkcConfigurator\Configurator\Helper;
use CkcConfigurator\ProductFactory;

class Occultation extends ProductFactory {

	// Get all occultation selected for configurator
	static public function get($language_id, $id_product_attribute, $is_souple)
	{
		if (!$id_product_attribute) {
			return [];
		}
		$sql = 'SELECT name FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac LEFT JOIN ' . _DB_PREFIX_ . 'attribute_lang al ON (al.id_attribute = pac.id_attribute) WHERE id_product_attribute = ' . $id_product_attribute . ' AND id_lang = ' . $language_id;
		$names = \Db::getInstance()->ExecuteS($sql);

		foreach($names as $name) {
			if(strpos($name['name'], 'Ht ') !== false || strpos($name['name'], '1m') !== false || strpos($name['name'], '2m') !== false || strpos($name['name'], '1 m') !== false || strpos($name['name'], '2 m') !== false) {
				$height = floatval(str_replace('m', '.', str_replace('Ht ', '', $name['name'])));
			}
		}

		$category = new \Category($is_souple ? self::OCCULTATIONS_SOUPLES_ID : self::OCCULTATIONS_RIGIDES_ID);
		$products = $category->getProducts($language_id, 1, 1000000, null, null, false, true, false, 1, false);
		$items = [];

		foreach ($products as $product) {
			$combinations = static::getAttributesInformationsByProduct($product['id_product']);
			$heights = [];
			$variations_by_height = [];

			// Récupérer toutes les hauteurs disponibles
			foreach($combinations as $combination) {
				$current_height = floatval(str_replace(['Ht ', 'ht-', 'm'], ['', '', '.'], $combination['attribute']));
				if(($combination['group'] == 'Hauteur' || $combination['group'] == 'hauteur') && !in_array($current_height, $heights)) {
					$heights[] = $current_height;
				}
			}

			if (empty($heights)) {
				continue; // Pas de hauteur, on skip
			}

			sort($heights, SORT_NUMERIC);
			$wantedHeight = self::getClosest($height, $heights);

			// Récupérer toutes les déclinaisons à la hauteur voulue
			foreach($combinations as $combination) {
				$current_height = floatval(str_replace(['Ht ', 'ht-', 'm'], ['', '', '.'], $combination['attribute']));
				if(($combination['group'] == 'Hauteur' || $combination['group'] == 'hauteur') && ($current_height == $wantedHeight)) {
					$id_product_attribute = $combination['id_product_attribute'];
					if (!isset($variations_by_height[$id_product_attribute])) {
						$product_name = \Product::getProductName($product['id_product'], $id_product_attribute, $language_id);
						$price = Helper::getPrice($product['id_product'], $id_product_attribute, 1);

						// Retirer le titre du produit du nom (partie avant le premier " : ")
						$name_without_title = $product_name;
						$colon_pos = strpos($product_name, ' : ');
						if ($colon_pos !== false) {
							$name_without_title = substr($product_name, $colon_pos + 3);
						}

						// Récupérer l'image de la déclinaison
						$variation_image = self::getImageUrl(\Product::getCombinationImageById($id_product_attribute, $language_id));

						$variations_by_height[$id_product_attribute] = [
							"id_product_attribute" => $id_product_attribute,
							"name"                 => $product_name,
							"name_without_title"   => $name_without_title,
							"height"               => $current_height,
							"price"                => $price['total_price'],
							"price_formatted"      => \Tools::displayPrice($price['total_price']),
							"image_url"            => $variation_image,
						];
					}
				}
			}

			if (empty($variations_by_height)) {
				continue; // Pas de déclinaisons à cette hauteur
			}

			$variations = array_values($variations_by_height);
			$first_variation = $variations[0];
			$short_name = explode(' : ', $first_variation['name'])[0];
			$first_image = self::getImageUrl(\Product::getCombinationImageById($first_variation['id_product_attribute'], $language_id));

			// Récupérer les détails du produit pour la tooltip
			$product_details = self::getProductDetails($product['id_product'], $first_variation['id_product_attribute'], $language_id);

			// Toujours créer un item avec select pour un rendu uniforme (même avec une seule déclinaison)
			$items[] = [
				"id"                   => $product['id_product'],
				"image_url"            => $first_image,
				"name"                 => $short_name,
				"short_name"           => $short_name,
				"variations"           => $variations,
				"has_select"           => true,
				"product_details"      => $product_details,
			];
		}

		return $items;
	}

	public static function getAttributesInformationsByProduct($id_product)
	{
		$result = \Db::getInstance()->executeS('
			SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name` as `attribute`, agl.`name` as `group`, pac.`id_product_attribute`
			FROM `'._DB_PREFIX_.'attribute` a
			LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
				ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int)\Context::getContext()->language->id.')
			LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
				ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int)\Context::getContext()->language->id.')
			LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
				ON (a.`id_attribute` = pac.`id_attribute`)
			LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
				ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
			'.\Shop::addSqlAssociation('product_attribute', 'pa').'
			'.\Shop::addSqlAssociation('attribute', 'pac').'
			WHERE pa.`id_product` = '.(int)$id_product);

		return $result;
	}

	static public function getImageUrl($image)
	{
		if(isset($image['id_image'])) {
			$image = new \Image($image['id_image']);
			return _PS_BASE_URL_._THEME_PROD_DIR_.$image->getExistingImgPath().".jpg";
		}

		return '';
	}

	static public function getClosest($search, $arr)
	{
		$closest = null;
		foreach ($arr as $item) {
			if ($closest === null || abs($search - $closest) > abs($item - $search)) {
				$closest = $item;
			}
		}
		return $closest;
	}

	static public function hasEspacement($lang, $id_product) {
		$Product = new \Product($id_product);
	}

	/**
	 * Récupère les caractéristiques du produit pour affichage dans la tooltip
	 */
	static public function getProductDetails($id_product, $id_product_attribute, $language_id)
	{
		$product_obj = new \Product($id_product, false, $language_id);

		if (!\Validate::isLoadedObject($product_obj)) {
			return [];
		}

		$details = [];

		// Caractéristiques (features)
		$features = $product_obj->getFrontFeatures($language_id);
		if (!empty($features)) {
			$grouped_features = [];
			foreach ($features as $feature) {
				$grouped_features[] = [
					'name' => $feature['name'],
					'value' => $feature['value']
				];
			}
			$details['grouped_features'] = $grouped_features;
		}

		return $details;
	}
}
